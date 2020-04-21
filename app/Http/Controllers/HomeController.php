<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Movie;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index($genre = NULL)
    {

        //  Movie::all();
        $user = Auth::user();
        $user_id = $user->id;

        $movies = DB::table('movies')
            ->leftJoin('movie_user', function($join) use ($user_id)
                {
                    $join->on('movie_user.movie_id', '=', 'movies.id');
                    $join->on('movie_user.user_id', '=', DB::raw("$user_id"));
                })
            ->where(function ($q) use ($user_id) {
                return $q->where('movie_user.user_id', '=', $user_id)
                        ->orWhere('movie_user.user_id', '=', NULL);
            })
            ->when(!empty($genre), function ($q) use ($genre) {
                return $q->where('movies.genre', '=', $genre);
            })
            ->orderBy('rank','ASC')
            ->take(100)
            ->get([
                'movies.*', 
                DB::raw('IF(ISNULL(movie_user.user_id), \'0\', \'1\') as watched')
            ]);

        return view('home', ["user" => $user, "movies" => $movies]);
    }
}