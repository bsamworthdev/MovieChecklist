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
})->middleware('guest');;

Auth::routes();

Route::group(['middleware' => 'auth'], function () {
    Route::get('/home/{genre?}/{time_period?}/{english_only?}/{unwatched_only?}/{favourites_only?}/{netflix_only?}/{amazon_only?}/{nowtv_only?}/{search_text?}', 'HomeController@index')->name('home');
    Route::get('/friends/{tag?}', 'FriendshipController@index')->name('friend');

    Route::post('/updatemovies', 'MovieController@updateMovies');
    Route::post('/updatemovieimages', 'MovieController@updateMovieImages');
    Route::post('/updatesavedmovieimages', 'MovieController@updateSavedMovieImages');
    Route::post('/updatenetflixstatuses', 'MovieController@updateNetflixStatuses');
    Route::post('/updateamazonstatuses', 'MovieController@updateAmazonStatuses');

    Route::post('/saveMovieUser', 'UserController@saveMovieUser');
    Route::post('/setMovieAsFavourite', 'UserController@setMovieAsFavourite');
    Route::post('/setMovieStreamStatus', 'MovieController@setMovieStreamStatus');
    Route::post('/getFriendsStats/{movie_id?}/', 'UserController@getFriendsStats'); 
    Route::post('/toggleMovieInWatchList', 'UserController@toggleMovieInWatchList');
    Route::post('/getMoreMovies/{skip_count?}/{genre?}/{time_period?}/{english_only?}/{unwatched_only?}/{favourites_only?}/{netflix_only?}/{amazon_only?}/{nowtv_only?}/{search_text?}', 'MovieController@getMoreMovies');

    Route::post('/editfriend', 'FriendshipController@edit');
    Route::post('/deletefriend', 'FriendshipController@delete');

    Route::post('/createFriendRequest', 'FriendRequestController@create');  
    Route::post('/findUserByEmail', 'UserController@findUserByEmail'); 
});

Route::group(['middleware' => 'admin'], function () {
    Route::get('/admin', 'AdminController@index')->name('admin');
});

Route::get('/home_nonauth/{genre?}/{time_period?}/{english_only?}/{unwatched_only?}/{favourites_only?}/{netflix_only?}/{amazon_only?}/{nowtv_only?}/{search_text?}', 'NonAuthHomeController@show')->name('nonauthhome');
Route::post('/saveMovieUserNonAuth', 'UserNonAuthController@saveMovieUser');
Route::post('/setMovieAsFavouriteNonAuth', 'UserNonAuthController@setMovieAsFavourite');
Route::post('/toggleMovieInWatchListNonAuth', 'UserNonAuthController@toggleMovieInWatchList');

Route::get('/acceptFriendRequest/{token}', 'FriendRequestResponseController@index');
Route::get('/about', 'AboutController@index');


