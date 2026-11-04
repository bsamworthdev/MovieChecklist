<?php

namespace Tests\Feature;

use Illuminate\Auth\GenericUser;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class WatchedMovieTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        Schema::create('movies', function (Blueprint $table) { $table->id(); });
        Schema::create('movie_user', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('movie_id');
            $table->tinyInteger('favourite')->default(0);
            $table->timestamps();
        });
        DB::table('movies')->insert(['id' => 1271]);
    }

    private function addWatchedConstraint(): void
    {
        // Production creates this constraint through the standalone cleanup SQL.
        Schema::table('movie_user', function (Blueprint $table) {
            $table->unique(['user_id', 'movie_id'], 'movie_user_user_movie_unique');
        });
    }

    public function test_repeated_watched_requests_do_not_duplicate_or_reset_favourites(): void
    {
        $this->addWatchedConstraint();
        $this->actingAs(new GenericUser(['id' => 1]));
        $payload = ['movie_id' => 1271, 'watched' => true];
        $this->postJson('/saveMovieUser', $payload)->assertOk()->assertJson(['watched' => true]);
        DB::table('movie_user')->update(['favourite' => 1]);
        foreach (range(1, 4) as $retry) $this->postJson('/saveMovieUser', $payload)->assertOk();
        $this->assertSame(1, DB::table('movie_user')->count());
        $this->assertSame(1, DB::table('movie_user')->value('favourite'));
        $this->actingAs(new GenericUser(['id' => 2]));
        $this->postJson('/saveMovieUser', $payload)->assertOk();
        $this->actingAs(new GenericUser(['id' => 1]));
        $this->postJson('/saveMovieUser', ['movie_id' => 1271, 'watched' => false])->assertOk();
        $this->assertSame(1, DB::table('movie_user')->count());
        $this->assertDatabaseHas('movie_user', ['user_id' => 2, 'movie_id' => 1271]);
    }

    public function test_invalid_or_unauthenticated_requests_cannot_write_history(): void
    {
        $this->postJson('/saveMovieUser', ['movie_id' => 1271, 'watched' => true])->assertUnauthorized();
        $this->actingAs(new GenericUser(['id' => 1]));
        $this->postJson('/saveMovieUser', ['movie_id' => '1271 OR 1=1', 'watched' => true])->assertUnprocessable();
        $this->postJson('/saveMovieUser', ['movie_id' => 1271, 'watched' => 99])->assertUnprocessable();
        $this->postJson('/saveMovieUser', ['movie_id' => 99999, 'watched' => true])->assertUnprocessable();
        $this->assertSame(0, DB::table('movie_user')->count());
    }
}
