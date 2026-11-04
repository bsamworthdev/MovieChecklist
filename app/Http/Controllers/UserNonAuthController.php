<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserNonAuthController extends Controller
{
    public function saveMovieUser(Request $request)
    {
        $data = $request->validate(['movie_id' => 'required|integer|exists:movies,id', 'watched' => 'required|boolean']);
        $key = ['movie_or_tv' => 'movie', 'session_id' => $request->session()->getId(), 'movie_id' => $data['movie_id']];
        if ($data['watched']) DB::table('movie_user_non_auth')->updateOrInsert($key, ['updated_at' => now()]);
        else DB::table('movie_user_non_auth')->where($key)->delete();
    }

    public function setMovieAsFavourite(Request $request)
    {
        $data = $request->validate(['movie_id' => 'required|integer|exists:movies,id', 'favourite' => 'required|boolean']);
        DB::table('movie_user_non_auth')->where('movie_user_non_auth.movie_or_tv', 'movie')->where('session_id', $request->session()->getId())->where('movie_id', $data['movie_id'])
            ->update(['favourite' => $data['favourite'], 'updated_at' => now()]);
    }

    public function toggleMovieInWatchList(Request $request)
    {
        $data = $request->validate(['movie_id' => 'required|integer|exists:movies,id', 'onWatchList' => 'required|boolean']);
        $key = ['movie_or_tv' => 'movie', 'session_id' => $request->session()->getId(), 'movie_id' => $data['movie_id']];
        if ($data['onWatchList']) DB::table('watch_list_non_auth')->updateOrInsert($key, ['updated_at' => now()]);
        else DB::table('watch_list_non_auth')->where($key)->delete();
    }
}
