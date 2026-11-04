<?php

namespace Tests\Concerns;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

trait CreatesAccountTables
{
    private function createAccountTables(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id(); $table->string('username')->unique(); $table->string('name')->default('');
            $table->string('email')->unique(); $table->string('password'); $table->string('role')->default('user');
            $table->boolean('filters_shown')->default(false); $table->rememberToken(); $table->timestamps();
        });
        Schema::create('genres', function (Blueprint $table) {$table->id(); $table->string('genre'); $table->string('label');});
        Schema::create('time_periods', function (Blueprint $table) {$table->id(); $table->string('time_period'); $table->string('label');});
    }

    private function account(int $id): \App\User
    {
        DB::table('users')->insert(['id' => $id, 'username' => 'user'.$id, 'name' => 'User '.$id, 'email' => 'user'.$id.'@example.com', 'password' => Hash::make('correct-password')]);
        return \App\User::findOrFail($id);
    }
}
