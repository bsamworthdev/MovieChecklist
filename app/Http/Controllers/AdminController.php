<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(){
        $catalogue = session('catalogue', 'movie');
        if ($catalogue === 'tv') {
            return view('admin-settings', [
                'catalogue' => 'tv', 'users' => collect(),
                'tvCount' => DB::table('tv_shows')->count(),
                'tvRankedCount' => DB::table('tv_shows')->whereNotNull('top250_rank')->count(),
            ]);
        }
        $watched = DB::table('movie_user')->where('movie_user.movie_or_tv', 'movie')
            ->join('movies', 'movies.id', '=', 'movie_user.movie_id')
            ->where('movies.top250_rank', '<=', 100)
            ->select('movie_user.user_id')
            ->selectRaw('COUNT(DISTINCT movie_user.movie_id) AS watched_count')
            ->groupBy('movie_user.user_id');

        // Avoid hydrating every User: its constructor also queries movie metadata.
        // Select only the fields displayed by the admin table, never credentials.
        $users = DB::table('users')
            ->leftJoinSub($watched, 'watched', 'watched.user_id', '=', 'users.id')
            ->select('users.id', 'users.name', 'users.username', 'users.email', 'users.created_at')
            ->selectRaw('COALESCE(watched.watched_count, 0) AS watchedCount')
            ->orderBy('users.name')
            ->get();

        foreach ($users as &$user){           
            $user->watchedCount = (int) $user->watchedCount;
            $user->created_at_tidy = date('Y-m-d H:i', strtotime($user->created_at));
        }

        return view(
            'admin-settings', [
                "users" => $users,
                'catalogue' => 'movie',
            ]
        );
    }
}
