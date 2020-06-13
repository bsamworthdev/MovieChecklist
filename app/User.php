<?php

namespace App;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Contracts\Auth\CanResetPassword; 
use Illuminate\Support\Facades\Cache;

class User extends Authenticatable
{
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'username', 'name', 'email', 'password',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    private $movie_genres;
    private $movie_time_periods;

    public function __construct()
    {
        $this->movie_genres = MovieGenre::orderBy('label')->get();
        $this->movie_time_periods = MovieTimePeriod::all();
    }

    public function trophies()
    {
        return $this->hasMany('App\Trophy');
    }

    public function movies()
    {
        return $this->hasMany('App\Movie');
    }

    public function friendshipsA()
    {
        return $this->hasMany('App\Friendship','person_a_user_id','id');
    }

    public function friendshipsB()
    {
        return $this->hasMany('App\Friendship','person_b_user_id','id');
    }
    
    public function friendsCount(){
        $friendsACount = $this->friendshipsA()->get()->count();
        $friendsBCount = $this->friendshipsB()->get()->count();
        return ($friendsACount + $friendsBCount);
    }

    public function hasWatchedMovie($movie_id){
        $rows=DB::select("select * from movie_user where user_id=? and movie_id=?",[$this->id, $movie_id]);
        return (count($rows) > 0);
    }

    public function isFavouriteMovie($movie_id){
        $rows=DB::select("select * from movie_user where user_id=? and movie_id=? and favourite=1",[$this->id, $movie_id]);
        return (count($rows) > 0);
    }

    public function getStats(){
        $user_id = $this->id;
        // Log::debug('before getSpecificStats-'.\Carbon\Carbon::now());
        $stats['overall'] = $this->getSpecificStats();

        foreach ($this->movie_genres as $movie_genre){
            $stats['genre'][$movie_genre->label] = $this->getSpecificStats('genre',$movie_genre->genre);
        }

        foreach ($this->movie_time_periods as $movie_time_period){
            $stats['time_period'][$movie_time_period->label] = $this->getSpecificStats('time_period', $movie_time_period->time_period);
        }
        // Log::debug('after getSpecificStats-'.\Carbon\Carbon::now());
        $favouriteMovies =  Movie::select(
                'movies.name', 
                'movies.rank', 
                'movies.imdb_id',
            )
            ->join('movie_user', 'movies.id', '=', 'movie_user.movie_id')
            ->where('movie_user.user_id', $user_id)
            ->where('movie_user.favourite', 1)
            ->orderBy('movies.id')
            ->limit('100')
            ->get();
        $stats['favourites']=$favouriteMovies;

        return $stats;
    }

    public function getTags($subject_user_id){
        $object_user_id = $this->id;
        $friendTags = FriendTag::where('subject_user_id', $subject_user_id)
            ->where('object_user_id', $object_user_id)
            ->get();

        $tags = [];    
        foreach($friendTags as $friendTag){
            $tags[] = FriendTagName::find($friendTag->tag_id)->name;
        }

        return $tags;
    }

    public function getSpecificStats($filterType = NULL, $filterValue  = NULL){
        $user_id = $this->id;

        $filterSQL = '';
        if (isset($filterType)) {
            if ($filterType == 'time_period'){
                $dates = Movie::parseTimePeriod($filterValue);
                $filterSQL = "movies.year BETWEEN ".$dates['from']." AND ".$dates['to']." ";
            } else {
                $filterSQL = "$filterType LIKE '%$filterValue%'";
            }     
        }

        $movies = Cache::get("movies_$filterType"."_$filterValue");
        if (empty($movies)) {
        
            $movies =  Movie:: select('movies.id',
                    'movies.name', 
                    'movies.rank', 
                    'movies.imdb_id',
                )
                ->when($filterType, function($query) use ($filterSQL) {   
                    return $query->whereRaw($filterSQL);
                })
                ->orderBy('movies.id')
                ->limit('100')
                ->get();
            Cache::set("movies_$filterType"."_$filterValue", $movies);
            dd('no');
        }
        
        $max_movie_id = (int)$movies->last()['id'];

        $userMovies =  Movie::select('movies.name', 
                'movies.rank', 
                'movies.imdb_id',
                'movie_user.favourite',
            )
            ->join('movie_user', 'movies.id', '=', 'movie_user.movie_id')
            ->where('movie_user.user_id', $user_id)
            ->where('movie_user.movie_id', '<=', $max_movie_id)
            ->when($filterType, function($query) use ($filterSQL) { 
                return $query->whereRaw($filterSQL);
            })
            ->orderBy('movies.id')
            ->limit('100')
            ->get();
              
        $total_count = $movies->count();
        $watched_count = $userMovies->count();

        $stats['watched'] = $watched_count;
        $stats['unwatched'] = $total_count - $watched_count;

        return $stats;
    }
}
