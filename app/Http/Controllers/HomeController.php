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
    public function index($genre = 'all', $time_period = 'all', $english_only = 0, $unwatched_only = 0, $favourites_only = 0, $netflix_only = 0, $amazon_only = 0)
    {

        $user = Auth::user();
        $user_id = $user->id;

        $movies = DB::table('movies')
            ->leftJoin('netflix', function($join)
            {
                $join->on('netflix.movie_id', '=', 'movies.id');
            })
            ->leftJoin('amazon', function($join)
            {
                $join->on('amazon.movie_id', '=', 'movies.id');
            })
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
            ->when($time_period <> 'all', function ($q) use ($time_period) {
                $dates = $this->parseTimePeriod($time_period);
                return $q->whereBetween('movies.year', [$dates['from'], $dates['to']]);
            })
            ->when($english_only == 1, function ($q) {
                return $q->where('movies.language', '=', 'english');
            })
            ->when($favourites_only == 1, function ($q) {
                return $q->where('movie_user.favourite', '=', '1');
            })
            ->when(($netflix_only == 1 && $amazon_only == 1), function ($q) {
                return $q->where(function ($q) {
                    $q->where('netflix.on_netflix', '=', '1')
                    ->orWhere('amazon.on_amazon', '=', '1');
                });
            })
            ->when(($netflix_only == 1 && $amazon_only == 0), function ($q) {
                return $q->where('netflix.on_netflix', '=', '1');
            })
            ->when(($netflix_only == 0 && $amazon_only == 1), function ($q) {
                return $q->where('amazon.on_amazon', '=', '1');
            })
            ->when($unwatched_only == 1, function ($q) {
                return $q->where('movie_user.user_id', '=', NULL);
            })
            ->orderBy('rank','ASC')
            ->take(100)
            ->get([
                'movies.*', 
                DB::raw('IF(ISNULL(netflix.on_netflix), \'0\', netflix.on_netflix) as on_netflix'),
                DB::raw('IF(ISNULL(amazon.on_amazon), \'0\', amazon.on_amazon) as on_amazon'),
                DB::raw('IF(ISNULL(movie_user.user_id), \'0\', \'1\') as watched'),
                DB::raw('IF(ISNULL(movie_user.favourite), \'0\', movie_user.favourite) as favourite')
            ]);
        
            $count = 1;
            foreach ($movies as $movie){
                $movie->index = $count;
                $count++;
            }

            $movie_genres = [
                'all' => 'All Genres',
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
                'sport'=>'Sport',
                'thriller'=>'Thriller',
                'war'=>'War',
            ];
            $selected_genre = $genre;

            $time_periods = [
                'all' => 'All Years',
                'last_50_years' => 'Last 50 Years',
                'last_25_years' => 'Last 25 Years',
                'last_10_years' => 'Last 10 Years',
                '2010s' => '2010s',
                '2000s' => '2000s',
                '90s' => '90s',
                '80s' => '80s',
                '80s' => '80s',
                '70s' => '70s',
                '60s' => '60s',
            ];
            $selected_time_period= $time_period;

            $selected_english_only = $english_only;
            $selected_unwatched_only = $unwatched_only;
            $selected_favourites_only = $favourites_only;
            $selected_netflix_only = $netflix_only;
            $selected_amazon_only = $amazon_only;

        return view('home', [
                "user" => $user, 
                "movies" => $movies, 
                "genres" => $movie_genres, 
                "selectedGenre" => $selected_genre,
                "timePeriods" => $time_periods,
                "selectedTimePeriod" => $selected_time_period,
                "selectedEnglishOnly" => $selected_english_only,
                "selectedUnwatchedOnly" => $selected_unwatched_only,
                "selectedFavouritesOnly" => $selected_favourites_only,
                "selectedNetflixOnly" => $selected_netflix_only,
                "selectedAmazonOnly" => $selected_amazon_only
            ]
        );
    }

    function parseTimePeriod($time_period){
        switch ($time_period) {
            case 'last_50_years':
                $yearFrom = '1970';
                $yearTo = '2020';
                break;
            case 'last_25_years':
                $yearFrom = '1995';
                $yearTo = '2020';
                break;
            case 'last_10_years':
                $yearFrom = '2010';
                $yearTo = '2020';
                break;
            case '2010s':
                $yearFrom = '2010';
                $yearTo = '2019';
                break;
            case '2000s':
                $yearFrom = '2000';
                $yearTo = '2009';
                break;
            case '90s':
                $yearFrom = '1990';
                $yearTo = '1999';
                break;
            case '80s':
                $yearFrom = '1980';
                $yearTo = '1989';
                break;
            case '70s':
                $yearFrom = '1970';
                $yearTo = '1979';
                break;
            case '60s':
                $yearFrom = '1960';
                $yearTo = '1969';
                break;
            default:
                break;
        }
        $dates =[
            'from' => $yearFrom,
            'to' => $yearTo
        ];
        return $dates;
    }
}