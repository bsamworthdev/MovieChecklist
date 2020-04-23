<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Auth::routes();

Route::get('/home/{genre?}/{time_period?}', 'HomeController@index')->name('home');
Route::post('/updatemovies', 'MovieController@updateMovies');
Route::post('/updatemovieimages', 'MovieController@updateMovieImages');
Route::post('/updatesavedmovieimages', 'MovieController@updateSavedMovieImages');
Route::post('/saveMovieUser', 'UserController@saveMovieUser');


