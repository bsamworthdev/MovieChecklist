<?php

namespace Tests\Feature;

use App\Http\Controllers\AdminController;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AdminPageTest extends TestCase
{
    public function test_admin_user_list_uses_one_query_and_only_counts_distinct_top_100_movies(): void
    {
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            foreach (['name', 'username', 'email', 'password', 'remember_token', 'created_at'] as $column) {
                $table->string($column);
            }
        });
        Schema::create('movies', function (Blueprint $table) {
            $table->id();
            $table->integer('top250_rank');
        });
        Schema::create('movie_user', function (Blueprint $table) {
            $table->integer('movie_id');
            $table->integer('user_id');
        });
        foreach (range(1, 50) as $id) {
            DB::table('users')->insert([
                'id' => $id, 'name' => sprintf('User %02d', $id), 'username' => "user$id",
                'email' => "user$id@example.com", 'password' => 'secret',
                'remember_token' => 'secret-token', 'created_at' => '2026-11-04 12:00:00',
            ]);
        }
        DB::table('movies')->insert([
            ['id' => 1, 'top250_rank' => 100], ['id' => 2, 'top250_rank' => 101],
        ]);
        DB::table('movie_user')->insert([
            ['user_id' => 1, 'movie_id' => 1], ['user_id' => 1, 'movie_id' => 1],
            ['user_id' => 1, 'movie_id' => 2], ['user_id' => 2, 'movie_id' => 1],
        ]);
        DB::enableQueryLog();
        DB::flushQueryLog();
        $users = (new AdminController())->index()->getData()['users'];
        $this->assertCount(1, DB::getQueryLog());
        DB::disableQueryLog();
        $this->assertCount(50, $users);
        $this->assertSame(1, $users[0]->watchedCount);
        $this->assertSame(1, $users[1]->watchedCount);
        $this->assertSame(0, $users[2]->watchedCount);
        $this->assertSame('2026-11-04 12:00', $users[0]->created_at_tidy);
        $this->assertFalse(property_exists($users[0], 'password'));
        $this->assertFalse(property_exists($users[0], 'remember_token'));
    }
}
