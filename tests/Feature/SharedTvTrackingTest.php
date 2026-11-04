<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SharedTvTrackingTest extends TestCase
{
    use \Tests\Concerns\CreatesLegacyTrackingTables;

    public function test_migration_preserves_movie_and_tv_flags_and_can_restore_old_schema(): void
    {
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        $this->createLegacyTrackingTables();
        (require database_path('migrations/2026_11_04_000001_create_tv_series_tables.php'))->up();
        DB::table('tv_series')->insert(['id' => 1, 'imdb_id' => 'tt1234567', 'name' => 'Show']);
        (require database_path('migrations/2026_11_04_000004_rename_tv_series_to_tv_shows.php'))->up();
        DB::table('movie_user')->insert(['movie_id' => 1, 'user_id' => 1, 'favourite' => 1]);
        DB::table('watch_list')->insert(['movie_id' => 1, 'user_id' => 1]);
        DB::table('tv_series_progress')->insert([
            ['tv_series_id' => 1, 'owner' => 'user:1', 'watched' => 0, 'favourite' => 1, 'watchlist' => 1, 'created_at' => '2026-11-04 10:00:00', 'updated_at' => '2026-11-04 11:00:00'],
            ['tv_series_id' => 1, 'owner' => 'guest:session-test', 'watched' => 1, 'favourite' => 1, 'watchlist' => 1, 'created_at' => '2026-11-04 10:00:00', 'updated_at' => '2026-11-04 11:00:00'],
        ]);
        $migration = require database_path('migrations/2026_11_04_000005_move_tv_tracking_to_shared_tables.php');
        $migration->up();
        $this->assertFalse(Schema::hasTable('tv_series_progress'));
        $this->assertDatabaseHas('movie_user', ['movie_or_tv' => 'movie', 'movie_id' => 1, 'user_id' => 1, 'watched' => 1, 'favourite' => 1]);
        $this->assertDatabaseHas('movie_user', ['movie_or_tv' => 'tv', 'movie_id' => 1, 'user_id' => 1, 'watched' => 0, 'favourite' => 1]);
        $this->assertDatabaseHas('movie_user_non_auth', ['movie_or_tv' => 'tv', 'movie_id' => 1, 'session_id' => 'session-test', 'watched' => 1, 'favourite' => 1]);
        $this->assertSame(2, DB::table('watch_list')->count());
        $this->assertDatabaseHas('watch_list_non_auth', ['movie_or_tv' => 'tv', 'session_id' => 'session-test', 'movie_id' => 1]);
        $migration->down();
        $this->assertFalse(Schema::hasColumn('movie_user', 'movie_or_tv'));
        $this->assertSame(1, DB::table('movie_user')->count());
        $this->assertSame(1, DB::table('watch_list')->count());
        $this->assertDatabaseHas('tv_series_progress', ['tv_series_id' => 1, 'owner' => 'user:1', 'watched' => 0, 'favourite' => 1, 'watchlist' => 1]);
        $this->assertDatabaseHas('tv_series_progress', ['tv_series_id' => 1, 'owner' => 'guest:session-test', 'watched' => 1, 'favourite' => 1, 'watchlist' => 1]);
    }
}
