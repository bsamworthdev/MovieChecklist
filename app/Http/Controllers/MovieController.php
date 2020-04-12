<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Movie;
use App\Http\Requests;
use GuzzleHttp\Client;
//use GuzzleHttp\Message\Request;
use GuzzleHttp\Message\Response;

class MovieController extends Controller
{
    //
    function updateMovies(){

        // $client = new Client();
        // $api_response = $client->get('https://api.envato.com/v1/discovery/search/search/item?site=themeforest.net&category=wordpress&sort_by=relevance&access_token=TOKEN');
        // $response = json_decode($api_response);

        //$movies =  Movie::all();
        return $response;
    }
}
