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

Route::match(['GET', 'POST'], '/tv-series/{path?}', function (\Illuminate\Http\Request $request, $path = null) {
    $query = $request->getQueryString();
    return redirect('/tv-shows'.($path ? '/'.$path : '').($query ? '?'.$query : ''), 308);
})->where('path', '.*');

Route::get('/tv-shows', 'TvSeriesController@index')->name('tv.index');
Route::get('/tv-shows/catalogue', 'TvSeriesController@catalogue')->name('tv.catalogue');
Route::get('/tv-shows/watchlist', 'TvSeriesController@watchlist')->name('tv.watchlist');
Route::post('/tv-shows/progress', 'TvSeriesController@progress')->name('tv.progress');
Route::post('/tv-shows/streaming', 'TvSeriesController@streaming')->middleware('auth')->name('tv.streaming');

Route::group(['middleware' => 'auth'], function () {
    Route::get('/home', 'HomeController@index')->name('home');
    Route::get('/friends/{tag?}', 'FriendshipController@index')->name('friend');

    Route::post('/updatemovieimages', 'MovieController@updateMovieImages');

    Route::post('/saveMovieUser', 'UserController@saveMovieUser');
    Route::post('/setMovieAsFavourite', 'UserController@setMovieAsFavourite');
    Route::post('/setMovieStreamStatus', 'MovieController@setMovieStreamStatus');
    Route::post('/getFriendsStats/{movie_id?}/', 'UserController@getFriendsStats'); 
    Route::post('/toggleMovieInWatchList', 'UserController@toggleMovieInWatchList');
    Route::post('/getMoreMovies/{skip_count?}/{genre?}/{time_period?}/{popular_only?}/{english_only?}/{unwatched_only?}/{favourites_only?}/{search_text?}/{netflix_only?}/{amazon_only?}/{nowtv_only?}/{disney_plus_only?}/{unwatched_by_friends?}/{hbo_max_only?}/{apple_tv_only?}/{paramount_plus_only?}/{bbc_iplayer_only?}/{itvx_only?}/{channel4_only?}/', 'MovieController@getMoreMovies');
    Route::post('/editfriend', 'FriendshipController@edit');
    Route::post('/deletefriend', 'FriendshipController@delete');

    Route::post('/createFriendRequest', 'FriendRequestController@create');  
    Route::post('/findUserByEmail', 'UserController@findUserByEmail'); 

    Route::post('/updateFiltersShown/{show?}', 'UserController@updateFiltersShown');  
});

Route::group(['middleware' => ['auth', 'admin']], function () {
    Route::get('/admin', 'AdminController@index')->name('admin');
    Route::match(['GET', 'POST'], '/admin/tv-series/{path}', function (\Illuminate\Http\Request $request, $path) {
        $query = $request->getQueryString();
        return redirect('/admin/tv-shows/'.$path.($query ? '?'.$query : ''), 308);
    })->where('path', '.*');
    Route::post('/admin/tv-shows/thumbnails', 'AdminTvImportController@thumbnails')->name('admin.tv.thumbnails');
    Route::post('/admin/tv-shows/preview', 'AdminTvImportController@preview')->name('admin.tv.preview');
    Route::post('/admin/tv-shows/apply', 'AdminTvImportController@apply')->name('admin.tv.apply');
    Route::post('/admin/movies/preview', 'AdminMovieImportController@preview')->name('admin.movies.preview');
    Route::post('/admin/movies/apply', 'AdminMovieImportController@apply')->name('admin.movies.apply');
    Route::post('/admin/catalogues/start', 'AdminCatalogueController@start')->name('admin.catalogues.start');
    Route::post('/admin/catalogues/step', 'AdminCatalogueController@step')->name('admin.catalogues.step');
    Route::post('/updatemovies', 'MovieController@updateMovies');
    Route::post('/updatenetflixstatuses', 'MovieController@updateNetflixStatuses');
    Route::post('/updateamazonstatuses', 'MovieController@updateAmazonStatuses');
    Route::post('/updatesavedmovieimages', 'MovieController@updateSavedMovieImages')->name('admin.movies.thumbnails');
});

Route::get('/home_nonauth', 'NonAuthHomeController@show')->name('nonauthhome');
Route::post('/saveMovieUserNonAuth', 'UserNonAuthController@saveMovieUser');
Route::post('/setMovieAsFavouriteNonAuth', 'UserNonAuthController@setMovieAsFavourite');
Route::post('/toggleMovieInWatchListNonAuth', 'UserNonAuthController@toggleMovieInWatchList');

Route::get('/acceptFriendRequest/{token}', 'FriendRequestResponseController@index');
Route::get('/about', 'AboutController@index');


