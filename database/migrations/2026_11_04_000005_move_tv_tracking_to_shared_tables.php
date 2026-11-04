<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    private const TABLES = ['movie_user' => 'user_id', 'movie_user_non_auth' => 'session_id', 'watch_list' => 'user_id', 'watch_list_non_auth' => 'session_id'];

    public function up(): void
    {
        foreach (self::TABLES as $name => $owner) {
            foreach (Schema::getForeignKeys($name) as $foreign) {
                if ($foreign['columns'] === ['movie_id']) Schema::table($name, fn (Blueprint $table) => $table->dropForeign($foreign['name']));
            }
            Schema::table($name, function (Blueprint $table) use ($name) {
                $table->string('movie_or_tv', 5)->default('movie');
                if (str_starts_with($name, 'movie_user')) $table->boolean('watched')->default(true);
            });
            // Keep user foreign keys supported while replacing a user/movie unique index.
            Schema::table($name, fn (Blueprint $table) => $table->index($owner, $name.'_tracking_owner_index'));
            $hadUnique = false;
            foreach (Schema::getIndexes($name) as $index) {
                if ($index['unique'] && !$index['primary'] && count($index['columns']) === 2 && in_array('movie_id', $index['columns'], true) && in_array($owner, $index['columns'], true)) {
                    Schema::table($name, fn (Blueprint $table) => $table->dropUnique($index['name']));
                    $hadUnique = true;
                }
            }
            Schema::table($name, function (Blueprint $table) use ($owner, $hadUnique) {
                if ($hadUnique) $table->unique(['movie_or_tv', $owner, 'movie_id']);
                else $table->index(['movie_or_tv', $owner, 'movie_id']);
            });
        }
        DB::transaction(function () {
            foreach (DB::table('tv_series_progress')->orderBy('id')->get() as $row) {
                $user = str_starts_with($row->owner, 'user:');
                $owner = $user ? 'user_id' : 'session_id';
                $id = substr($row->owner, $user ? 5 : 6);
                $key = ['movie_or_tv' => 'tv', 'movie_id' => $row->tv_series_id, $owner => $id];
                if ($row->watched || $row->favourite) {
                    DB::table($user ? 'movie_user' : 'movie_user_non_auth')->updateOrInsert($key, [
                        'watched' => $row->watched, 'favourite' => $row->favourite,
                        'created_at' => $row->created_at, 'updated_at' => $row->updated_at,
                    ]);
                }
                if ($row->watchlist) {
                    DB::table($user ? 'watch_list' : 'watch_list_non_auth')->updateOrInsert($key, [
                        'created_at' => $row->created_at, 'updated_at' => $row->updated_at,
                    ]);
                }
            }
        });
        Schema::drop('tv_series_progress');
    }

    public function down(): void
    {
        Schema::create('tv_series_progress', function (Blueprint $table) {
            $table->id(); $table->foreignId('tv_series_id')->constrained('tv_shows')->cascadeOnDelete();
            $table->string('owner', 128); $table->boolean('watched')->default(false);
            $table->boolean('favourite')->default(false); $table->boolean('watchlist')->default(false);
            $table->timestamps(); $table->unique(['owner', 'tv_series_id']);
        });
        foreach (self::TABLES as $name => $owner) {
            foreach (DB::table($name)->where('movie_or_tv', 'tv')->get() as $row) {
                $key = ['owner' => ($owner === 'user_id' ? 'user:' : 'guest:').$row->$owner, 'tv_series_id' => $row->movie_id];
                DB::table('tv_series_progress')->updateOrInsert($key, (str_starts_with($name, 'movie_user')
                    ? ['watched' => $row->watched, 'favourite' => $row->favourite] : ['watchlist' => true])
                    + ['created_at' => $row->created_at, 'updated_at' => $row->updated_at]);
            }
            DB::table($name)->where('movie_or_tv', 'tv')->delete();
            foreach (Schema::getIndexes($name) as $index) {
                if ($index['columns'] === ['movie_or_tv', $owner, 'movie_id']) {
                    Schema::table($name, function (Blueprint $table) use ($index, $owner) {
                        if ($index['unique']) {$table->dropUnique($index['name']); $table->unique([$owner, 'movie_id']);}
                        else $table->dropIndex($index['name']);
                    });
                }
            }
            Schema::table($name, function (Blueprint $table) use ($name) {
                $table->dropColumn(str_starts_with($name, 'movie_user') ? ['movie_or_tv', 'watched'] : ['movie_or_tv']);
                $table->dropIndex($name.'_tracking_owner_index');
            });
        }
    }
};
