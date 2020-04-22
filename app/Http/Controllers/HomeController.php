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
    public function index($genre = 'all')
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
            ->when($genre <> 'all', function ($q) use ($genre) {
                return $q->where('movies.genre', 'LIKE', '%'.$genre.'%');
            })
            ->orderBy('rank','ASC')
            ->take(100)
            ->get([
                'movies.*', 
                DB::raw('IF(ISNULL(movie_user.user_id), \'0\', \'1\') as watched')
            ]);

            $movie_genres = [
                'all' => 'All Movies',
                'action'=>'Action',
                'animation'=>'Animated',
                'comedy'=>'Comedy',
                'crime'=>'Crime',
                'drama'=>'Drama',
                'family'=>'Family',
                'fantasy'=>'Fantasy',
                'history'=>'History',
                'music'=>'Music',
                'sci-fi'=>'Sci-Fi',
                'thriller'=>'Thriller',
                'war'=>'War',
            ];
            $selectedGenre = $genre;

        return view('home', [
            "user" => $user, 
            "movies" => $movies, 
            "genres" => $movie_genres, 
            'selectedGenre' => $selectedGenre
            ]
        );
    }
}