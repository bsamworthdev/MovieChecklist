<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    private const TABLES = ['netflix', 'amazon', 'nowtv', 'disney_plus', 'hbo_max', 'apple_tv', 'paramount_plus', 'bbc_iplayer', 'itvx', 'channel4'];
    private const UNIQUE_TABLES = ['hbo_max', 'apple_tv', 'paramount_plus', 'bbc_iplayer', 'itvx', 'channel4'];

    public function up(): void
    {
        foreach (self::TABLES as $name) {
            // A shared identifier can no longer reference only the movies table.
            foreach (Schema::getForeignKeys($name) as $foreign) {
                if ($foreign['columns'] === ['movie_id']) {
                    Schema::table($name, fn (Blueprint $table) => $table->dropForeign($foreign['name']));
                }
            }
            Schema::table($name, fn (Blueprint $table) => $table->string('movie_or_tv', 5)->default('movie'));
            foreach (Schema::getIndexes($name) as $index) {
                if ($index['unique'] && !$index['primary'] && $index['columns'] === ['movie_id']) {
                    Schema::table($name, fn (Blueprint $table) => $table->dropUnique($index['name']));
                }
            }
            Schema::table($name, function (Blueprint $table) use ($name) {
                if (in_array($name, self::UNIQUE_TABLES, true)) {
                    $table->unique(['movie_or_tv', 'movie_id']);
                } else {
                    // Preserve legacy rows, including any pre-existing duplicates.
                    $table->index(['movie_or_tv', 'movie_id']);
                }
            });
        }
    }

    public function down(): void
    {
        foreach (array_reverse(self::TABLES) as $name) {
            DB::table($name)->where('movie_or_tv', 'tv')->delete();
            Schema::table($name, function (Blueprint $table) use ($name) {
                if (in_array($name, self::UNIQUE_TABLES, true)) {
                    $table->dropUnique(['movie_or_tv', 'movie_id']);
                    $table->unique('movie_id');
                } else {
                    $table->dropIndex(['movie_or_tv', 'movie_id']);
                }
                $table->dropColumn('movie_or_tv');
            });
        }
    }
};
