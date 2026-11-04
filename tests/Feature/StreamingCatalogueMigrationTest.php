<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class StreamingCatalogueMigrationTest extends TestCase
{
    public function test_existing_movie_rows_survive_and_tv_can_share_the_same_id(): void
    {
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        foreach (config('catalogues.services') as $service) {
            Schema::create($service['table'], function (Blueprint $table) use ($service) {
                $table->id(); $table->unsignedBigInteger('movie_id');
                if (!in_array($service['table'], ['netflix', 'amazon', 'nowtv', 'disney_plus'], true)) $table->unique('movie_id');
                $table->boolean($service['column']); $table->timestamps();
            });
            DB::table($service['table'])->insert(['movie_id' => 1, $service['column'] => 1]);
        }
        $migration = require database_path('migrations/2026_11_04_000002_scope_streaming_tables_by_catalogue.php');
        $migration->up();
        foreach (config('catalogues.services') as $service) {
            $this->assertDatabaseHas($service['table'], ['movie_id' => 1, 'movie_or_tv' => 'movie', $service['column'] => 1]);
            DB::table($service['table'])->insert(['movie_id' => 1, 'movie_or_tv' => 'tv', $service['column'] => 0]);
            $this->assertSame(2, DB::table($service['table'])->count());
        }
        $migration->down();
        foreach (config('catalogues.services') as $service) {
            $this->assertFalse(Schema::hasColumn($service['table'], 'movie_or_tv'));
            $this->assertSame(1, DB::table($service['table'])->count());
            $this->assertSame(1, DB::table($service['table'])->value($service['column']));
        }
    }
}
