<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TvShowTracking
{
    public function settings(Request $request): array
    {
        $user = $request->user();
        return [
            'watched_table' => $user ? 'movie_user' : 'movie_user_non_auth',
            'watchlist_table' => $user ? 'watch_list' : 'watch_list_non_auth',
            'owner_column' => $user ? 'user_id' : 'session_id',
            'owner' => $user ? $user->getAuthIdentifier() : $request->session()->getId(),
        ];
    }

    public function query(Request $request)
    {
        $settings = $this->settings($request);
        $watched = DB::table($settings['watched_table'])->where('movie_or_tv', 'tv')->where($settings['owner_column'], $settings['owner'])
            ->select('movie_id')->selectRaw('MAX(watched) AS watched, MAX(favourite) AS favourite')->groupBy('movie_id');
        $watchlist = DB::table($settings['watchlist_table'])->where('movie_or_tv', 'tv')->where($settings['owner_column'], $settings['owner'])
            ->select('movie_id')->groupBy('movie_id');
        return DB::table('tv_shows as series')
            ->leftJoinSub($watched, 'progress', 'progress.movie_id', '=', 'series.id')
            ->leftJoinSub($watchlist, 'watchlist', 'watchlist.movie_id', '=', 'series.id')
            ->select('series.*')->selectRaw('COALESCE(progress.watched, 0) AS watched, COALESCE(progress.favourite, 0) AS favourite, CASE WHEN watchlist.movie_id IS NULL THEN 0 ELSE 1 END AS watchlist');
    }

    public function save(Request $request, array $data): void
    {
        $settings = $this->settings($request);
        $key = ['movie_or_tv' => 'tv', 'movie_id' => $data['id'], $settings['owner_column'] => $settings['owner']];
        DB::transaction(function () use ($settings, $key, $data) {
            // Serializes repeated writes even where the legacy table has no unique index.
            DB::table('tv_shows')->where('id', $data['id'])->lockForUpdate()->first();
            if ($data['field'] === 'watchlist') {
                if ($data['value']) DB::table($settings['watchlist_table'])->updateOrInsert($key, ['updated_at' => now()]);
                else DB::table($settings['watchlist_table'])->where($key)->delete();
            } else {
                $query = DB::table($settings['watched_table'])->where($key);
                if ($query->exists()) $query->update([$data['field'] => $data['value'], 'updated_at' => now()]);
                else DB::table($settings['watched_table'])->insert($key + ['watched' => $data['field'] === 'watched' && $data['value'], 'favourite' => $data['field'] === 'favourite' && $data['value'], 'created_at' => now(), 'updated_at' => now()]);
            }
        });
    }
}
