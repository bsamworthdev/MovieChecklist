<?php

namespace Tests\Feature;

use App\Services\ImdbTop250Parser;
use Illuminate\Auth\GenericUser;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;
use Tests\TestCase;

class AdminMovieImportTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        config(['cache.movie_import_store' => 'array']);
        DB::purge('sqlite');
        Schema::create('movies', function (Blueprint $table) {
            $table->id();
            $table->string('imdb_id')->nullable();
            $table->string('name');
            $table->integer('year');
            $table->integer('rank')->default(1001);
            $table->integer('top250_rank')->default(999);
            $table->decimal('rating')->default(0);
            $table->integer('votes')->default(0);
            $table->decimal('weighted_rating', 5, 4)->default(0);
            $table->string('language');
            $table->string('image_url')->nullable();
            $table->string('image_url_small')->nullable();
            $table->timestamps();
        });
    }

    private function admin(): void
    {
        $this->actingAs(new GenericUser(['id' => 99, 'role' => 'admin']));
    }

    private function chart(): string
    {
        $edges = [];
        foreach (range(1, 250) as $position) {
            $edges[] = ['currentRank' => $position, 'node' => [
                'id' => 'tt'.(1000000 + $position), 'titleText' => ['text' => 'Movie '.$position],
                'releaseYear' => ['year' => 2000], 'ratingsSummary' => ['aggregateRating' => 8.1],
                'primaryImage' => ['url' => 'https://m.media-amazon.com/images/'.$position.'.jpg'],
            ]];
        }
        return '<script id="__NEXT_DATA__" type="application/json">'.json_encode([
            'props' => ['pageProps' => ['pageData' => ['chartTitles' => ['edges' => $edges]]]],
        ]).'</script>';
    }

    private function movie(array $attributes = []): int
    {
        return DB::table('movies')->insertGetId($attributes + [
            'imdb_id' => 'tt1000001', 'name' => 'Original movie name', 'year' => 2000,
            'rank' => 17, 'top250_rank' => 9, 'rating' => 7.3, 'language' => 'english',
            'image_url' => 'https://existing.example/poster.jpg', 'image_url_small' => '/storage/existing.jpg',
        ]);
    }

    private function preview(): array
    {
        return $this->postJson(route('admin.movies.preview'), ['html' => $this->chart()])->assertOk()->json();
    }

    private function decisions(array $preview): array
    {
        return array_fill_keys(array_column($preview['unmatched'], 'position'), '');
    }

    public function test_endpoints_require_an_admin(): void
    {
        Http::fake();
        $this->postJson(route('admin.movies.preview'))->assertUnauthorized();
        $this->postJson(route('admin.movies.thumbnails'))->assertUnauthorized();
        $this->actingAs(new GenericUser(['id' => 8, 'role' => 'user']));
        $this->postJson(route('admin.movies.preview'))->assertForbidden();
        $this->postJson(route('admin.movies.apply'))->assertForbidden();
        $this->postJson('/updatemovies')->assertForbidden();
        $this->postJson(route('admin.movies.thumbnails'))->assertForbidden();
        Http::assertNothingSent();
    }

    public function test_preview_reads_pasted_html_without_writes_or_outbound_requests(): void
    {
        $this->admin();
        Http::fake();
        $id = $this->movie();
        $preview = $this->preview();
        $this->assertSame(1, $preview['matched']);
        $this->assertCount(249, $preview['unmatched']);
        $this->assertSame(1, DB::table('movies')->count());
        $this->assertSame(9, DB::table('movies')->where('id', $id)->value('top250_rank'));
        Http::assertNothingSent();
    }

    public function test_apply_reuses_manual_matches_adds_movies_preserves_history_fields_and_is_idempotent(): void
    {
        $this->admin();
        $id = $this->movie();
        $manual = $this->movie(['imdb_id' => 'tt9999999', 'name' => 'Alternative title', 'year' => 1999, 'rank' => 83, 'image_url' => null]);
        $outside = $this->movie(['imdb_id' => 'tt8888888', 'name' => 'Leaving chart', 'year' => 1980]);
        $preview = $this->preview();
        $decisions = $this->decisions($preview);
        $decisions[2] = 'tt9999999';
        $payload = ['token' => $preview['token'], 'decisions' => $decisions];
        $response = $this->postJson(route('admin.movies.apply'), $payload)->assertOk();
        $response->assertJson(['updated' => 2, 'added' => 248]);
        $this->assertSame(251, DB::table('movies')->count());
        $this->assertSame(250, DB::table('movies')->whereBetween('top250_rank', [1, 250])->count());
        $existing = DB::table('movies')->find($id);
        $this->assertSame(17, $existing->rank);
        $this->assertSame('Original movie name', $existing->name);
        $this->assertSame('https://existing.example/poster.jpg', $existing->image_url);
        $this->assertSame('/storage/existing.jpg', $existing->image_url_small);
        $resolved = DB::table('movies')->find($manual);
        $this->assertSame('tt1000002', $resolved->imdb_id);
        $this->assertSame(2, $resolved->top250_rank);
        $this->assertSame(83, $resolved->rank);
        $this->assertSame('https://m.media-amazon.com/images/2.jpg', $resolved->image_url);
        $this->assertSame(999, DB::table('movies')->find($outside)->top250_rank);
        $this->assertSame(1001, DB::table('movies')->where('imdb_id', 'tt1000003')->value('rank'));
        $this->postJson(route('admin.movies.apply'), $payload)->assertOk()->assertExactJson($response->json());
        $this->assertSame(251, DB::table('movies')->count());
    }

    public function test_invalid_resolution_and_stale_database_cannot_partially_update(): void
    {
        $this->admin();
        $id = $this->movie();
        $preview = $this->preview();
        $decisions = $this->decisions($preview);
        $decisions[2] = 'tt0000000';
        $this->postJson(route('admin.movies.apply'), ['token' => $preview['token'], 'decisions' => $decisions])
            ->assertUnprocessable()->assertJsonValidationErrors('decisions.2');
        $this->assertSame(9, DB::table('movies')->find($id)->top250_rank);
        DB::table('movies')->where('id', $id)->update(['imdb_id' => 'tt3333333']);
        $this->postJson(route('admin.movies.apply'), ['token' => $preview['token'], 'decisions' => $this->decisions($preview)])
            ->assertUnprocessable()->assertJsonValidationErrors('import');
        $this->assertSame(1, DB::table('movies')->count());
    }

    public function test_duplicate_targets_are_rejected_before_writing(): void
    {
        $this->admin();
        $id = $this->movie();
        $preview = $this->preview();
        $decisions = $this->decisions($preview);
        $decisions[2] = 'tt1000001';
        $this->postJson(route('admin.movies.apply'), ['token' => $preview['token'], 'decisions' => $decisions])
            ->assertUnprocessable()->assertJsonValidationErrors('decisions');
        $this->assertSame(9, DB::table('movies')->find($id)->top250_rank);
    }

    public function test_html_is_required_and_partial_charts_do_not_write(): void
    {
        $this->admin();
        Http::fake();
        $this->postJson(route('admin.movies.preview'))->assertUnprocessable()->assertJsonValidationErrors('html');
        $this->postJson(route('admin.movies.preview'), ['html' => '   '])->assertUnprocessable()->assertJsonValidationErrors('html');
        $this->postJson(route('admin.movies.preview'), ['html' => 'https://www.imdb.com/chart/top/'])->assertUnprocessable()->assertJsonValidationErrors('html');
        $this->postJson(route('admin.movies.preview'), ['html' => $this->chart()])->assertOk();
        $this->postJson(route('admin.movies.preview'), ['html' => '<html>No chart</html>'])
            ->assertUnprocessable()->assertJsonValidationErrors('html');
        $this->assertSame(0, DB::table('movies')->count());
        Http::assertNothingSent();
    }

    public function test_compact_html_parses_numeric_titles_accents_and_largest_poster(): void
    {
        $html = '<ul>';
        foreach (range(1, 250) as $position) {
            $title = $position === 1 ? '1917' : 'Léon &amp; friends '.$position;
            $html .= '<li class="ipc-metadata-list-summary-item"><img src="https://m.media-amazon.com/small.jpg" srcset="https://m.media-amazon.com/small.jpg 90w, https://m.media-amazon.com/large.jpg 180w">'
                .'#'.$position.'<a class="ipc-title-link-wrapper" href="/title/tt'.(1000000 + $position).'/">'.$title.'</a><span>2019</span></li>';
        }
        $rows = app(ImdbTop250Parser::class)->parse($html.'</ul>');
        $this->assertSame('1917', $rows[0]['name']);
        $this->assertSame(2019, $rows[0]['year']);
        $this->assertSame('Léon & friends 2', $rows[1]['name']);
        $this->assertSame('https://m.media-amazon.com/large.jpg', $rows[0]['image_url']);
        $this->assertNull($rows[0]['rating']);
    }

    public function test_database_failure_rolls_back_rank_reset_and_all_inserts(): void
    {
        $this->admin();
        $id = $this->movie();
        $preview = $this->preview();
        DB::unprepared("CREATE TRIGGER fail_movie_insert BEFORE INSERT ON movies WHEN NEW.imdb_id = 'tt1000004' BEGIN SELECT RAISE(ABORT, 'test failure'); END");
        $this->postJson(route('admin.movies.apply'), [
            'token' => $preview['token'], 'decisions' => $this->decisions($preview),
        ])->assertServerError();
        $this->assertSame(1, DB::table('movies')->count());
        $movie = DB::table('movies')->find($id);
        $this->assertSame(9, $movie->top250_rank);
        $this->assertEquals(7.3, $movie->rating);
    }

    public function test_duplicate_chart_codes_and_expired_preview_are_rejected(): void
    {
        $this->admin();
        $broken = str_replace('tt1000250', 'tt1000001', $this->chart());
        $this->postJson(route('admin.movies.preview'), ['html' => $broken])
            ->assertUnprocessable()->assertJsonValidationErrors('html');
        $preview = $this->preview();
        $reference = session('movie_import');
        $snapshot = Cache::store('array')->get($reference['cache_key']);
        $snapshot['expires_at'] = time() - 1;
        Cache::store('array')->put($reference['cache_key'], $snapshot, 1800);
        $this->postJson(route('admin.movies.apply'), [
            'token' => $preview['token'], 'decisions' => $this->decisions($preview),
        ])->assertUnprocessable()->assertJsonValidationErrors('token');
        $this->assertSame(0, DB::table('movies')->count());
    }

    public function test_large_preview_is_cached_and_session_stays_small(): void
    {
        $this->admin();
        // Real posters have long URLs; the former base64 session payload exceeded 64 KiB.
        $html = str_replace('https://m.media-amazon.com/images/',
            'https://m.media-amazon.com/images/'.str_repeat('x', 300).'/', $this->chart());
        $response = $this->postJson(route('admin.movies.preview'), ['html' => $html])->assertOk();
        $reference = session('movie_import');
        $this->assertSame(['token', 'cache_key'], array_keys($reference));
        $this->assertLessThan(1024, strlen(base64_encode(serialize(session()->all()))));
        $snapshot = Cache::store('array')->get($reference['cache_key']);
        $this->assertGreaterThan(65535, strlen(base64_encode(serialize($snapshot))));
        $this->assertCount(250, $snapshot['rows']);
        $this->postJson(route('admin.movies.apply'), [
            'token' => $response->json('token'),
            'decisions' => array_fill_keys(array_column($response->json('unmatched'), 'position'), ''),
        ])->assertOk()->assertJson(['added' => 250]);
        $this->assertSame($reference, session('movie_import'));
    }

    public function test_evicted_preview_is_rejected_without_writing(): void
    {
        $this->admin();
        $preview = $this->preview();
        Cache::store('array')->forget(session('movie_import.cache_key'));
        $this->postJson(route('admin.movies.apply'), [
            'token' => $preview['token'], 'decisions' => $this->decisions($preview),
        ])->assertUnprocessable()->assertJsonValidationErrors('token');
        $this->assertSame(0, DB::table('movies')->count());
    }

    public function test_thumbnail_button_generates_missing_images_and_preserves_existing_thumbnails(): void
    {
        $this->admin();
        Storage::fake('public');
        $existing = $this->movie();
        $source = tempnam(sys_get_temp_dir(), 'movie-thumbnail-');
        try {
            file_put_contents($source, (string) Image::canvas(300, 500, '#ffffff')->encode('png'));
            $missing = $this->movie([
                'imdb_id' => 'tt2222222', 'image_url' => 'file://'.$source, 'image_url_small' => null,
            ]);
            $this->movie(['imdb_id' => 'tt3333333', 'image_url' => null, 'image_url_small' => null]);
            $this->postJson(route('admin.movies.thumbnails'))->assertOk()->assertExactJson(['generated' => 1, 'remaining' => 0]);
            $filename = basename($source);
            Storage::disk('public')->assertExists($filename);
            $thumbnail = Image::make(Storage::disk('public')->get($filename));
            $this->assertSame(229, $thumbnail->width());
            $this->assertSame(287, $thumbnail->height());
            $this->assertSame('/storage/'.$filename, DB::table('movies')->find($missing)->image_url_small);
            $this->assertSame('/storage/existing.jpg', DB::table('movies')->find($existing)->image_url_small);
            $this->postJson(route('admin.movies.thumbnails'))->assertOk()->assertExactJson(['generated' => 0, 'remaining' => 0]);
        } finally {
            unlink($source);
        }
    }

    public function test_successful_import_recalculates_all_weighted_ratings_using_updated_ratings(): void
    {
        $this->admin();
        $matched = $this->movie(['votes' => 25000]);
        $outside = $this->movie(['imdb_id' => 'tt8888888', 'name' => 'Outside the chart', 'rating' => 10, 'votes' => 0]);
        $preview = $this->preview();
        $this->assertEquals(0, DB::table('movies')->find($matched)->weighted_rating, 'Preview does not recalculate ratings');
        $this->postJson(route('admin.movies.apply'), [
            'token' => $preview['token'], 'decisions' => $this->decisions($preview),
        ])->assertOk()->assertJsonPath('weighted_ratings_updated', 251);
        $average = (250 * 8.1 + 10) / 251;
        $this->assertEquals(round((8.1 + $average) / 2, 4), DB::table('movies')->find($matched)->weighted_rating);
        $this->assertEquals(round($average, 4), DB::table('movies')->find($outside)->weighted_rating);
        $this->assertEquals(round($average, 4), DB::table('movies')->where('imdb_id', 'tt1000002')->value('weighted_rating'));
    }

    public function test_failed_weighted_recalculation_rolls_back_the_entire_import(): void
    {
        $this->admin();
        $id = $this->movie();
        $preview = $this->preview();
        DB::unprepared("CREATE TRIGGER fail_weighted_update BEFORE UPDATE OF weighted_rating ON movies BEGIN SELECT RAISE(ABORT, 'weighted failure'); END");
        $this->postJson(route('admin.movies.apply'), [
            'token' => $preview['token'], 'decisions' => $this->decisions($preview),
        ])->assertServerError();
        $this->assertSame(1, DB::table('movies')->count());
        $this->assertSame(9, DB::table('movies')->find($id)->top250_rank);
        $this->assertEquals(7.3, DB::table('movies')->find($id)->rating);
    }
}
