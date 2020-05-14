<?php

namespace App;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Auth\CanResetPassword; 

class User extends Authenticatable
{
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password',
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

        $userMovies = DB::select("select movies.name,movies.imdb_id, 
                IFNULL(movie_user.user_id,0)>0 as watched 
            from movies 
            left join movie_user on 
                (movies.id=movie_user.movie_id 
                and movie_user.user_id=?) 
            where 
                (movie_user.user_id=? 
                or movie_user.user_id IS NULL) 
            order by movies.id",[$user_id, $user_id]);
        
        $stats['overall']['watched'] = 0;
        $stats['overall']['unwatched'] = 0;
        //$stats_categorised = [];
        foreach ($userMovies as $userMovie){
            if (($stats['overall']['watched'] + $stats['overall']['unwatched']) < 100){
                if ($userMovie->watched == 1) {
                    $stats['overall']['watched'] += 1;
                } else {
                    $stats['overall']['unwatched'] += 1;
                }
            }
        }

        return $stats;
    }
}
