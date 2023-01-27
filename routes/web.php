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
    Route::get('/home', 'HomeController@index')->name('home');
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
    Route::post('/getMoreMovies/{skip_count?}/{genre?}/{time_period?}/{english_only?}/{unwatched_only?}/{favourites_only?}/{search_text?}/{netflix_only?}/{amazon_only?}/{nowtv_only?}/{disney_plus_only?}/{unwatched_by_friends?}/', 'MovieController@getMoreMovies');

    Route::post('/editfriend', 'FriendshipController@edit');
    Route::post('/deletefriend', 'FriendshipController@delete');

    Route::post('/createFriendRequest', 'FriendRequestController@create');  
    Route::post('/findUserByEmail', 'UserController@findUserByEmail'); 

    Route::post('/updateFiltersShown/{show?}', 'UserController@updateFiltersShown');  
});

Route::group(['middleware' => 'admin'], function () {
    Route::get('/admin', 'AdminController@index')->name('admin');
});

Route::get('/home_nonauth', 'NonAuthHomeController@show')->name('nonauthhome');
Route::post('/saveMovieUserNonAuth', 'UserNonAuthController@saveMovieUser');
Route::post('/setMovieAsFavouriteNonAuth', 'UserNonAuthController@setMovieAsFavourite');
Route::post('/toggleMovieInWatchListNonAuth', 'UserNonAuthController@toggleMovieInWatchList');

Route::get('/acceptFriendRequest/{token}', 'FriendRequestResponseController@index');
Route::get('/about', 'AboutController@index');


