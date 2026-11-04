<?php

namespace Tests\Integration;

use App\Http\Controllers\MovieController;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MovieCatalogueMysqlTest extends TestCase
{
    use \Tests\Concerns\CreatesAccountTables;
    use \Tests\Concerns\CreatesLegacyTrackingTables;

    protected function setUp(): void
    {
        parent::setUp();
        if (getenv('RUN_MYSQL_TESTS') !== '1') $this->markTestSkipped('Enable RUN_MYSQL_TESTS=1 with a dedicated MYSQL_TEST_DATABASE.');
        $database = getenv('MYSQL_TEST_DATABASE') ?: '';
        if (!preg_match('/^moviechecklist_test_[a-zA-Z0-9_]+$/', $database)) throw new \RuntimeException('Integration tests require a dedicated moviechecklist_test_* database.');
        $connection = config('database.connections.mysql');
        foreach (['host', 'port', 'username', 'password'] as $key) {
            $value = getenv('MYSQL_TEST_'.strtoupper($key));
            if ($value !== false) $connection[$key] = $value;
        }
        $connection['url'] = null;
        $connection['database'] = $database;
        config(['database.default' => 'mysql_integration', 'database.connections.mysql_integration' => $connection]);
        DB::purge('mysql_integration');
        Schema::dropAllTables();
        $this->createAccountTables();
        $this->createLegacyTrackingTables();
        (require database_path('migrations/2026_11_04_000001_create_tv_series_tables.php'))->up();
        (require database_path('migrations/2026_11_04_000003_add_tv_show_votes.php'))->up();
        (require database_path('migrations/2026_11_04_000004_rename_tv_series_to_tv_shows.php'))->up();
        (require database_path('migrations/2026_11_04_000005_move_tv_tracking_to_shared_tables.php'))->up();
        (require database_path('migrations/2026_11_04_000006_add_tv_show_small_images.php'))->up();
        Schema::create('movies', function (Blueprint $table) {
            $table->id(); $table->string('name'); $table->string('imdb_id'); $table->integer('rank'); $table->integer('top250_rank');
            $table->integer('year'); $table->string('genre'); $table->string('language'); $table->integer('votes');
            $table->decimal('rating', 3, 1)->default(8); $table->string('image_url')->nullable(); $table->string('image_url_small')->nullable();
        });
        foreach (config('catalogues.services') as $service) {
            Schema::create($service['table'], function (Blueprint $table) use ($service) {
                $table->id(); $table->unsignedBigInteger('movie_id'); $table->boolean($service['column']); $table->timestamps();
                if (!in_array($service['table'], ['amazon', 'netflix', 'nowtv', 'disney_plus'], true)) $table->unique('movie_id');
            });
        }
        (require database_path('migrations/2026_11_04_000002_scope_streaming_tables_by_catalogue.php'))->up();
        Schema::create('friendships', function (Blueprint $table) {$table->id(); $table->integer('person_A_user_id'); $table->integer('person_B_user_id');});
        Schema::create('trophies', function (Blueprint $table) {$table->id(); $table->integer('user_id');});
        Schema::create('info_message', function (Blueprint $table) {$table->id(); $table->dateTime('start_date'); $table->dateTime('end_date');});
        $this->actingAs($this->account(1));
    }

    private function movie(int $rank, array $attributes = []): int
    {
        return DB::table('movies')->insertGetId($attributes + ['name' => 'Film '.$rank, 'imdb_id' => 'tt'.(1000000 + $rank), 'rank' => $rank, 'top250_rank' => $rank, 'year' => 2020, 'genre' => 'Drama', 'language' => 'english', 'votes' => 300000]);
    }

    private function seedCatalogue(): void
    {
        $this->account(2); $this->account(3);
        $this->movie(1); $this->movie(2); $this->movie(3, ['language' => 'japanese', 'genre' => 'Comedy', 'year' => 1990]);
        $this->movie(4, ['votes' => 250000]); $this->movie(5);
        DB::table('netflix')->insert([
            ['movie_id' => 1, 'movie_or_tv' => 'movie', 'on_netflix' => 1], ['movie_id' => 3, 'movie_or_tv' => 'movie', 'on_netflix' => 1],
            ['movie_id' => 4, 'movie_or_tv' => 'movie', 'on_netflix' => 1], ['movie_id' => 5, 'movie_or_tv' => 'tv', 'on_netflix' => 1],
        ]);
        DB::table('amazon')->insert(['movie_id' => 2, 'movie_or_tv' => 'movie', 'on_amazon' => 1]);
        DB::table('movie_user')->insert([
            ['user_id' => 1, 'movie_id' => 1, 'movie_or_tv' => 'movie', 'favourite' => 1],
            ['user_id' => 1, 'movie_id' => 5, 'movie_or_tv' => 'tv', 'favourite' => 1],
            ['user_id' => 3, 'movie_id' => 5, 'movie_or_tv' => 'movie', 'favourite' => 1],
            ['user_id' => 2, 'movie_id' => 3, 'movie_or_tv' => 'movie', 'favourite' => 0],
            ['user_id' => 2, 'movie_id' => 2, 'movie_or_tv' => 'tv', 'favourite' => 0],
        ]);
        DB::table('watch_list')->insert([
            ['user_id' => 1, 'movie_id' => 1, 'movie_or_tv' => 'movie'], ['user_id' => 1, 'movie_id' => 5, 'movie_or_tv' => 'tv'],
            ['user_id' => 3, 'movie_id' => 5, 'movie_or_tv' => 'movie'],
        ]);
    }

    private function query(array $overrides = [])
    {
        $filters = array_replace(['genre' => 'all', 'time_period' => 'all', 'english_only' => 0, 'popular_only' => 0, 'unwatched_only' => 0, 'favourites_only' => 0, 'search_text' => '', 'netflix_only' => 0, 'amazon_only' => 0, 'nowtv_only' => 0, 'disney_plus_only' => 0, 'unwatched_by_friends' => '', 'skip_count' => 0, 'hbo_max_only' => 0, 'apple_tv_only' => 0, 'paramount_plus_only' => 0, 'bbc_iplayer_only' => 0, 'itvx_only' => 0, 'channel4_only' => 0], $overrides);
        return app(MovieController::class)->getMovies(...array_values($filters));
    }

    public function test_real_query_combines_streaming_or_with_language_genre_popularity_and_watched_filters(): void
    {
        $this->seedCatalogue();
        $filters = ['genre' => 'Drama', 'time_period' => '2020s', 'english_only' => 1, 'popular_only' => 1, 'netflix_only' => 1, 'amazon_only' => 1];
        $this->assertSame([1, 2], $this->query($filters)->pluck('id')->all());
        $this->assertSame([2], $this->query($filters + ['unwatched_only' => 1])->pluck('id')->all());
        $this->assertSame([1], $this->query($filters + ['favourites_only' => 1])->pluck('id')->all());
        $this->assertSame([1, 2, 3, 4, 5], $this->query()->pluck('id')->all());
    }

    public function test_actual_movie_cards_do_not_inherit_another_users_or_tv_state(): void
    {
        $this->seedCatalogue();
        $movie = $this->query(['search_text' => 'Film 5'])->sole();
        $this->assertSame(0, (int) $movie->watched);
        $this->assertSame(0, (int) $movie->favourite);
        $this->assertSame(0, (int) $movie->on_watch_list);
        $this->assertSame(0, (int) $movie->on_netflix);
        $this->get('/home')->assertOk()->assertViewHas('watchList', fn ($list) => $list->pluck('id')->all() === [1]);
    }

    public function test_unwatched_by_friends_only_excludes_their_movie_history(): void
    {
        $this->seedCatalogue();
        $this->assertSame([1, 2, 4, 5], $this->query(['unwatched_by_friends' => '2'])->pluck('id')->all());
    }

    public function test_show_more_endpoint_returns_next_100_without_repeating_first_batch(): void
    {
        foreach (range(1, 105) as $rank) $this->movie($rank);
        $first = $this->query();
        $this->assertCount(100, $first);
        $next = $this->postJson('/getMoreMovies', ['skip_count' => 100, 'genre' => 'all', 'time_period' => 'all', 'search_text' => 'null', 'unwatched_by_friends' => ''])->assertOk()->assertJsonCount(5)->assertJsonPath('0.index', 101)->json();
        $this->assertSame([101, 102, 103, 104, 105], array_column($next, 'id'));
        $this->assertSame([], array_intersect($first->pluck('id')->all(), array_column($next, 'id')));
    }

    public function test_guest_catalogue_and_watchlist_do_not_leak_other_sessions_or_tv_rows(): void
    {
        $this->seedCatalogue();
        $this->app['auth']->forgetGuards();
        $this->withSession(['guest' => true]); $session = app('session')->getId();
        DB::table('movie_user_non_auth')->insert([
            ['session_id' => $session, 'movie_id' => 1, 'movie_or_tv' => 'movie', 'favourite' => 1],
            ['session_id' => $session, 'movie_id' => 2, 'movie_or_tv' => 'tv', 'favourite' => 1],
            ['session_id' => 'other', 'movie_id' => 3, 'movie_or_tv' => 'movie', 'favourite' => 1],
        ]);
        DB::table('watch_list_non_auth')->insert([
            ['session_id' => $session, 'movie_id' => 1, 'movie_or_tv' => 'movie'],
            ['session_id' => $session, 'movie_id' => 2, 'movie_or_tv' => 'tv'],
        ]);
        $this->withCookie(config('session.cookie'), $session)->get('/home_nonauth?filters=favourites:1')->assertOk()
            ->assertViewHas('movies', fn ($movies) => $movies->pluck('id')->all() === [1])
            ->assertViewHas('watchList', fn ($list) => $list->pluck('id')->all() === [1]);
    }
}
