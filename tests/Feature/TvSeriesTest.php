<?php

namespace Tests\Feature;

use Illuminate\Auth\GenericUser;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TvSeriesTest extends TestCase
{
    use \Tests\Concerns\CreatesLegacyTrackingTables;
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:', 'cache.movie_import_store' => 'array']);
        DB::purge('sqlite');
        (require database_path('migrations/2026_11_04_000001_create_tv_series_tables.php'))->up();
        (require database_path('migrations/2026_11_04_000003_add_tv_show_votes.php'))->up();
        (require database_path('migrations/2026_11_04_000004_rename_tv_series_to_tv_shows.php'))->up();
        $this->createLegacyTrackingTables();
        (require database_path('migrations/2026_11_04_000005_move_tv_tracking_to_shared_tables.php'))->up();
        (require database_path('migrations/2026_11_04_000006_add_tv_show_small_images.php'))->up();
        foreach (config('catalogues.services') as $service) {
            \Illuminate\Support\Facades\Schema::create($service['table'], function (\Illuminate\Database\Schema\Blueprint $table) use ($service) {
                $table->id(); $table->integer('movie_id');
                $table->string('movie_or_tv')->default('movie');
                $table->boolean($service['column'])->default(false); $table->timestamps();
                $table->unique(['movie_or_tv', 'movie_id']);
            });
        }
    }

    private function series(array $values = []): int
    {
        return DB::table('tv_shows')->insertGetId($values + ['imdb_id' => 'tt1234567', 'name' => 'Test Series', 'year' => 2020, 'top250_rank' => 1]);
    }

    public function test_guest_can_browse_and_filter_series(): void
    {
        $this->series();
        $this->series(['imdb_id' => 'tt2345678', 'name' => 'Another Show', 'top250_rank' => 2]);
        $this->get('/tv-series')->assertOk()->assertSee('TV Shows');
        $this->getJson('/tv-series/catalogue?search=Another')->assertOk()->assertJsonPath('total', 1)->assertJsonPath('data.0.name', 'Another Show');
    }

    public function test_progress_is_idempotent_isolated_and_preserves_other_flags(): void
    {
        $id = $this->series();
        $first = new GenericUser(['id' => 10, 'role' => 'user']);
        $second = new GenericUser(['id' => 11, 'role' => 'user']);
        $this->actingAs($first);
        foreach (['watched', 'watched', 'favourite', 'watchlist'] as $field) {
            $this->postJson('/tv-series/progress', ['id' => $id, 'field' => $field, 'value' => true])->assertOk();
        }
        $this->assertSame(1, DB::table('movie_user')->where('movie_or_tv', 'tv')->count());
        $this->getJson('/tv-series/catalogue?status=favourites')->assertJsonPath('total', 1);
        $this->postJson('/tv-series/progress', ['id' => $id, 'field' => 'watched', 'value' => false])->assertOk();
        $this->getJson('/tv-series/catalogue?status=watchlist')->assertJsonPath('total', 1)->assertJsonPath('data.0.watched', 0);
        $this->actingAs($second)->getJson('/tv-series/catalogue?status=favourites')->assertJsonPath('total', 0);
    }

    public function test_invalid_progress_and_non_admin_import_are_rejected(): void
    {
        $this->postJson('/tv-series/progress', ['id' => 999, 'field' => 'name', 'value' => true])->assertUnprocessable();
        $this->actingAs(new GenericUser(['id' => 1, 'role' => 'user']))
            ->postJson('/admin/tv-series/preview', ['html' => 'bad'])->assertForbidden();
    }

    public function test_import_preserves_artwork_and_history_and_can_be_retried(): void
    {
        $id = $this->series(['imdb_id' => 'tt1000001', 'image_url' => 'existing-art']);
        DB::table('movie_user')->insert(['movie_id' => $id, 'movie_or_tv' => 'tv', 'user_id' => 1, 'watched' => true]);
        $outside = $this->series(['imdb_id' => 'tt9999999', 'top250_rank' => 2]);
        $this->actingAs(new GenericUser(['id' => 1, 'role' => 'admin']));
        $edges = [];
        foreach (range(1, 250) as $rank) {
            $edges[] = ['currentRank' => $rank, 'node' => ['id' => 'tt'.(1000000 + $rank), 'titleText' => ['text' => "Series $rank"], 'releaseYear' => ['year' => 2000], 'ratingsSummary' => ['aggregateRating' => 8.5], 'primaryImage' => ['url' => 'https://m.media-amazon.com/images/test.jpg']]];
        }
        $html = '<script id="__NEXT_DATA__" type="application/json">'.json_encode(['chartTitles' => ['edges' => $edges]]).'</script>';
        $preview = $this->postJson('/admin/tv-series/preview', ['html' => $html])->assertOk()->assertJsonPath('new', 249);
        $token = $preview->json('token');
        $this->postJson('/admin/tv-series/apply', ['token' => $token])->assertOk()->assertJsonPath('added', 249);
        $this->postJson('/admin/tv-series/apply', ['token' => $token])->assertOk()->assertJsonPath('added', 249);
        $this->assertSame(251, DB::table('tv_shows')->count());
        $this->assertSame('existing-art', DB::table('tv_shows')->where('id', $id)->value('image_url'));
        $this->assertNull(DB::table('tv_shows')->where('id', $outside)->value('top250_rank'));
        $this->assertSame(1, (int) DB::table('movie_user')->where('movie_or_tv', 'tv')->value('watched'));
    }

    public function test_incomplete_import_does_not_change_catalogue(): void
    {
        $this->series();
        $this->actingAs(new GenericUser(['id' => 1, 'role' => 'admin']))
            ->postJson('/admin/tv-series/preview', ['html' => '<html></html>'])->assertUnprocessable();
        $this->assertSame(1, DB::table('tv_shows')->count());
    }

    public function test_tv_year_ranges_are_parsed_from_chart_html(): void
    {
        $html = '<ul>';
        foreach (range(1, 250) as $rank) {
            $html .= '<li class="ipc-metadata-list-summary-item"><a class="ipc-title-link-wrapper" href="/title/tt'.(1000000 + $rank).'/">'.$rank.'. Series '.$rank.'</a><span>2008–2013</span></li>';
        }
        $rows = app(\App\Services\ImdbTop250Parser::class)->parse($html.'</ul>');
        $this->assertCount(250, $rows);
        $this->assertSame(2008, $rows[0]['year']);
    }

    public function test_guest_progress_is_separate_from_account_progress(): void
    {
        $id = $this->series();
        $this->postJson('/tv-series/progress', ['id' => $id, 'field' => 'watched', 'value' => true])->assertOk();
        $this->withCredentials()->withCookie(config('session.cookie'), app('session')->getId());
        $this->getJson('/tv-series/catalogue?status=watched')->assertJsonPath('total', 1);
        $this->assertSame(app('session')->getId(), DB::table('movie_user_non_auth')->value('session_id'));
        $this->actingAs(new GenericUser(['id' => 1, 'role' => 'user']))
            ->getJson('/tv-series/catalogue?status=watched')->assertJsonPath('total', 0);
    }

    public function test_combined_filters_summary_and_independent_watchlist(): void
    {
        $id = $this->series(['genre' => 'Drama, Crime', 'language' => 'English', 'year' => 2008]);
        $other = $this->series(['imdb_id' => 'tt2345678', 'genre' => 'Comedy', 'language' => 'French', 'year' => 2015]);
        DB::table('movie_user')->insert([
            ['user_id' => 1, 'movie_id' => $id, 'movie_or_tv' => 'tv', 'watched' => 1, 'favourite' => 1],
            ['user_id' => 1, 'movie_id' => $other, 'movie_or_tv' => 'tv', 'watched' => 0, 'favourite' => 0],
        ]);
        DB::table('watch_list')->insert([
            ['user_id' => 1, 'movie_id' => $other, 'movie_or_tv' => 'tv'],
            ['user_id' => 2, 'movie_id' => $id, 'movie_or_tv' => 'tv'],
        ]);
        $this->actingAs(new GenericUser(['id' => 1, 'role' => 'user']));
        $this->getJson('/tv-series/catalogue?genre=Drama&decade=2000&english=1&favourites=1')
            ->assertOk()->assertJsonPath('total', 1)->assertJsonPath('summary.watched', 1)
            ->assertJsonPath('summary.total', 1)->assertJsonPath('summary.watchlist', 1)
            ->assertJsonPath('filters.has_languages', true);
        $this->getJson('/tv-series/catalogue?genre=Drama&unwatched=1')->assertJsonPath('total', 0);
        $this->getJson('/tv-series/watchlist?genre=Drama')->assertOk()->assertJsonCount(1)->assertJsonPath('0.id', $other);
    }

    public function test_streaming_services_use_or_and_ignore_movie_availability(): void
    {
        $one = $this->series();
        $two = $this->series(['imdb_id' => 'tt2345678']);
        $three = $this->series(['imdb_id' => 'tt3456789']);
        DB::table('netflix')->insert([
            ['movie_id' => $one, 'movie_or_tv' => 'tv', 'on_netflix' => 1],
            ['movie_id' => $three, 'movie_or_tv' => 'movie', 'on_netflix' => 1],
        ]);
        DB::table('amazon')->insert(['movie_id' => $two, 'movie_or_tv' => 'tv', 'on_amazon' => 1]);
        $this->getJson('/tv-series/catalogue?services[]=netflix&services[]=prime')->assertOk()->assertJsonPath('total', 2);
        $this->getJson('/tv-series/catalogue')->assertJsonPath('total', 3)->assertJsonCount(10, 'services');
        $this->getJson('/tv-series/catalogue?services[]=netflix')->assertJsonPath('total', 1)->assertJsonPath('data.0.id', $one);
        $this->getJson('/tv-series/catalogue?services[]=invalid')->assertUnprocessable();
    }

    public function test_editing_tv_availability_preserves_movie_and_requires_editor(): void
    {
        $id = $this->series();
        DB::table('netflix')->insert(['movie_id' => $id, 'movie_or_tv' => 'movie', 'on_netflix' => 1]);
        $this->actingAs(new GenericUser(['id' => 1, 'role' => 'user']))
            ->postJson('/tv-series/streaming', ['id' => $id, 'service' => 'netflix', 'available' => false])->assertForbidden();
        $this->actingAs(new GenericUser(['id' => 1, 'role' => 'admin']))
            ->postJson('/tv-series/streaming', ['id' => $id, 'service' => 'netflix', 'available' => false])->assertOk();
        $this->assertDatabaseHas('netflix', ['movie_id' => $id, 'movie_or_tv' => 'movie', 'on_netflix' => 1]);
        $this->assertDatabaseHas('netflix', ['movie_id' => $id, 'movie_or_tv' => 'tv', 'on_netflix' => 0]);
    }

    public function test_tv_admin_only_shows_tv_controls_and_keeps_toggle_on_admin(): void
    {
        $this->series();
        $this->actingAs(new GenericUser(['id' => 1, 'role' => 'admin', 'name' => 'Admin', 'username' => 'admin', 'trophies' => collect()]));
        $this->get('/admin?catalogue=tv')->assertOk()->assertSee('TV Shows Admin Settings')
            ->assertSee('Preview TV Shows')->assertSee('data-media="tv"', false)
            ->assertDontSee('Fetch latest movies from IMDB')->assertSee('generate-tv-thumbnails-button')
            ->assertDontSee('<users-table', false)->assertSee('/admin?catalogue=movie', false)
            ->assertSessionHas('catalogue', 'tv');
    }

    public function test_movies_admin_hides_tv_controls(): void
    {
        \Illuminate\Support\Facades\Schema::create('movies', function (\Illuminate\Database\Schema\Blueprint $table) {$table->id(); $table->integer('top250_rank');});
        \Illuminate\Support\Facades\Schema::create('users', function (\Illuminate\Database\Schema\Blueprint $table) {
            $table->id(); $table->string('name'); $table->string('username'); $table->string('email'); $table->timestamps();
        });
        $this->actingAs(new GenericUser(['id' => 1, 'role' => 'admin', 'name' => 'Admin', 'username' => 'admin', 'trophies' => collect()]));
        $this->withSession(['catalogue' => 'tv'])->get('/admin?catalogue=movie')->assertOk()
            ->assertSee('Movies Admin Settings')->assertSee('Fetch latest movies from IMDB')
            ->assertSee('data-media="movie"', false)->assertDontSee('Preview TV Shows')
            ->assertSessionHas('catalogue', 'movie');
    }

    public function test_english_and_popular_filters_combine_and_use_imdb_vote_threshold(): void
    {
        $id = $this->series(['language' => 'english', 'votes' => 250001]);
        $this->series(['imdb_id' => 'tt2345678', 'language' => 'en', 'votes' => 250000]);
        $this->series(['imdb_id' => 'tt3456789', 'language' => 'ja', 'votes' => 800000]);
        $this->getJson('/tv-series/catalogue?popular=1')->assertOk()->assertJsonPath('total', 2);
        $this->getJson('/tv-series/catalogue?english=1')->assertOk()->assertJsonPath('total', 2);
        $this->getJson('/tv-series/catalogue?english=1&popular=1')->assertOk()->assertJsonPath('total', 1)->assertJsonPath('data.0.id', $id);
    }

    public function test_compact_imdb_vote_counts_are_parsed(): void
    {
        $html = '<ul>';
        foreach (range(1, 250) as $rank) {
            $html .= '<li class="ipc-metadata-list-summary-item"><a class="ipc-title-link-wrapper" href="/title/tt'.(1000000 + $rank).'/">'.$rank.'. Show '.$rank.'</a><span>2008</span><span class="ipc-rating-star--voteCount">&nbsp;(2.7M)</span></li>';
        }
        $rows = app(\App\Services\ImdbTop250Parser::class)->parse($html.'</ul>');
        $this->assertSame(2700000, $rows[0]['votes']);
    }

    public function test_movie_and_tv_tracking_with_same_id_are_independent(): void
    {
        $id = $this->series();
        \Illuminate\Support\Facades\Schema::create('movies', function (\Illuminate\Database\Schema\Blueprint $table) {$table->id();});
        DB::table('movies')->insert(['id' => $id]);
        $this->actingAs(new GenericUser(['id' => 1, 'role' => 'user']));
        $this->postJson('/saveMovieUser', ['movie_id' => $id, 'watched' => true])->assertOk();
        foreach (['watched', 'favourite', 'watchlist'] as $field) {
            $this->postJson('/tv-series/progress', ['id' => $id, 'field' => $field, 'value' => true])->assertOk();
        }
        $this->postJson('/toggleMovieInWatchList', ['movie_id' => $id, 'onWatchList' => true])->assertOk();
        $this->postJson('/saveMovieUser', ['movie_id' => $id, 'watched' => false])->assertOk();
        $this->postJson('/toggleMovieInWatchList', ['movie_id' => $id, 'onWatchList' => false])->assertOk();
        $this->getJson('/tv-series/catalogue')->assertJsonPath('data.0.watched', 1)->assertJsonPath('data.0.favourite', 1)->assertJsonPath('summary.watchlist', 1);
        $this->assertSame(0, DB::table('movie_user')->where('movie_or_tv', 'movie')->count());
    }

    public function test_tv_thumbnails_use_movie_dimensions_and_preserve_existing_images(): void
    {
        \Illuminate\Support\Facades\Storage::fake('public');
        $source = tempnam(sys_get_temp_dir(), 'tv-thumbnail-');
        try {
            file_put_contents($source, (string) \Intervention\Image\Facades\Image::canvas(300, 500, '#ffffff')->encode('jpg'));
            $id = $this->series(['image_url' => $source]);
            $existing = $this->series(['imdb_id' => 'tt2345678', 'image_url' => $source, 'image_url_small' => '/storage/existing.jpg']);
            \Illuminate\Support\Facades\Storage::disk('public')->put('existing.jpg', file_get_contents($source));
            $this->postJson(route('admin.tv.thumbnails'))->assertUnauthorized();
            $this->actingAs(new GenericUser(['id' => 1, 'role' => 'user']))->postJson(route('admin.tv.thumbnails'))->assertForbidden();
            $this->actingAs(new GenericUser(['id' => 1, 'role' => 'admin']))->postJson(route('admin.tv.thumbnails'))->assertOk()->assertExactJson(['generated' => 1, 'remaining' => 0]);
            $this->assertSame('/storage/tv-shows/tt1234567.jpg', DB::table('tv_shows')->where('id', $id)->value('image_url_small'));
            $this->assertSame('/storage/existing.jpg', DB::table('tv_shows')->where('id', $existing)->value('image_url_small'));
            $image = \Intervention\Image\Facades\Image::make(\Illuminate\Support\Facades\Storage::disk('public')->get('tv-shows/tt1234567.jpg'));
            $this->assertSame(229, $image->width());
            $this->assertSame(287, $image->height());
            $this->postJson(route('admin.tv.thumbnails'))->assertOk()->assertExactJson(['generated' => 0, 'remaining' => 0]);
            $this->getJson('/tv-series/catalogue')->assertOk()->assertJsonPath('data.0.image_url_small', '/storage/tv-shows/tt1234567.jpg')->assertJsonPath('data.0.image_url_small_extended', '/storage/tv-shows/tt1234567.jpg');
            \Illuminate\Support\Facades\Storage::disk('public')->delete('existing.jpg');
            $this->getJson('/tv-series/catalogue?search=Test')->assertOk()->assertJsonPath('data.1.image_url_small_extended', null);
            $this->postJson(route('admin.tv.thumbnails'))->assertOk()->assertExactJson(['generated' => 1, 'remaining' => 0]);
            $this->assertSame('/storage/tv-shows/tt2345678.jpg', DB::table('tv_shows')->where('id', $existing)->value('image_url_small'));
        } finally {unlink($source);}
    }

    public function test_random_picker_respects_filters_excludes_watched_and_searches_beyond_first_page(): void
    {
        $this->actingAs(new GenericUser(['id' => 1, 'role' => 'user']));
        foreach (range(1, 60) as $rank) {
            $id = $this->series(['imdb_id' => 'tt'.(1000000 + $rank), 'top250_rank' => $rank, 'language' => 'en', 'votes' => 500000]);
            DB::table('netflix')->insert(['movie_id' => $id, 'movie_or_tv' => 'tv', 'on_netflix' => 1]);
            if ($rank < 60) DB::table('movie_user')->insert(['movie_id' => $id, 'movie_or_tv' => 'tv', 'user_id' => 1, 'watched' => true]);
        }
        $this->series(['imdb_id' => 'tt9999999', 'language' => 'ja', 'votes' => 500000]);
        $this->getJson('/tv-series/catalogue?random=1&english=1&popular=1&services[]=netflix')->assertOk()->assertJsonPath('show.top250_rank', 60)->assertJsonPath('show.watched', 0);
        DB::table('movie_user')->insert(['movie_id' => $id, 'movie_or_tv' => 'tv', 'user_id' => 1, 'watched' => true]);
        $this->getJson('/tv-series/catalogue?random=1&english=1&popular=1&services[]=netflix')->assertOk()->assertExactJson(['show' => null]);
    }
}
