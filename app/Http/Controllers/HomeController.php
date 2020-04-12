<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Movie;

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

        // movies = [
        //     {
        //         name:'bambi',
        //         year:1944,
        //     },
        //     {
        //         name:'dumbo',
        //         year: 1948,
        //     }
        // ];

        $movies =  Movie::all();
        return view('home', ["movies" => $movies]);

    }
}
