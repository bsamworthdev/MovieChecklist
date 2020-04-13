<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Movie;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

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
    public function index()
    {
        //  Movie::all();
        $user_id = Auth::user()->id;

        $movies = DB::table('movies')
            ->leftjoin('movie_user', 'movies.id', '=', 'movie_user.movie_id')
            ->where('movie_user.user_id', '=', $user_id)
            ->orWhere('movie_user.user_id', '=', NULL)
            ->orderBy('rank','ASC')
            ->get([
                'movies.*', 
                DB::raw('IF(ISNULL(movie_user.user_id), \'0\', \'1\') as watched')
            ]);
        return view('home', ["movies" => $movies]);
    }
}