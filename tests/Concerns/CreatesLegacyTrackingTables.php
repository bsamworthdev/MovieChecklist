<?php

namespace Tests\Concerns;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

trait CreatesLegacyTrackingTables
{
    private function createLegacyTrackingTables(): void
    {
        foreach (['movie_user', 'movie_user_non_auth', 'watch_list', 'watch_list_non_auth'] as $name) {
            Schema::create($name, function (Blueprint $table) use ($name) {
                $table->id(); $table->unsignedBigInteger('movie_id');
                $owner = str_ends_with($name, '_non_auth') ? 'session_id' : 'user_id';
                if ($owner === 'session_id') $table->string($owner);
                else $table->unsignedBigInteger($owner);
                if (str_starts_with($name, 'movie_user')) $table->boolean('favourite')->default(false);
                $table->timestamps();
                $table->unique([$owner, 'movie_id']);
            });
        }
    }
}
