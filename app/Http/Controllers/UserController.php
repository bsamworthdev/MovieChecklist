<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\User;

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function saveUserMovies(Request $request)
    {
        $user_id = Auth::user()->id;
        
        return back()->with('message', 'Updated list successfully');
    }

    public function saveMovieUser(Request $request)
    {
        $user_id = Auth::user()->id;
        $movie_id = $request->movie_id;
        
        if ($request->watched){
            DB::statement("insert into movie_user (user_id,movie_id, created_at, updated_at) values ($user_id, $movie_id, NOW(), NOW())");
            // DB::insert('insert into users (email, votes) values (?, ?)', ['john@example.com', '0']);
        } else {
            DB::statement("delete from movie_user where user_id=$user_id and movie_id=$movie_id");
        }
    }

    public function setMovieAsFavourite(Request $request)
    {
        $user_id = Auth::user()->id;
        $movie_id = $request->movie_id;

        if ($request->favourite){
            DB::update("update movie_user set favourite=1, updated_at=NOW() where user_id=? and movie_id=?", [$user_id, $movie_id]);
            // DB::insert('insert into users (email, votes) values (?, ?)', ['john@example.com', '0']);
        } else {
            DB::update("update movie_user set favourite=0, updated_at=NOW() where user_id=? and movie_id=?", [$user_id, $movie_id]);
        }
    }

    public function toggleMovieInWatchList(Request $request)
    {
        $user_id = Auth::user()->id;
        $movie_id = $request->movie_id;
        
        if ($request->onWatchList){
            DB::statement("insert into watch_list (user_id,movie_id, created_at, updated_at) values ($user_id, $movie_id, NOW(), NOW())");
        } else {
            DB::statement("delete from watch_list where user_id=$user_id and movie_id=$movie_id");
        }
    }

    public function getFriendsStats(Request $request)
    {
        $user_id = Auth::user()->id;
        $movie_id = $request->movie_id;

        $friendsA = User::find($user_id)->friendshipsA()->get();
        $friendsB = User::find($user_id)->friendshipsB()->get();

        $friendsStats = [];
        foreach ($friendsA as $friend) {
            $thisFriend = User::find($friend->person_B_user_id);
            $thisFriend['hasWatched'] = $thisFriend->hasWatchedMovie($movie_id) ? 1 : 0;
            $thisFriend['isFavourite'] = $thisFriend->isFavouriteMovie($movie_id) ? 1 : 0;
            $friendsStats[] = $thisFriend;
        }
        foreach ($friendsB as $friend) {
            $thisFriend = User::find($friend->person_A_user_id);
            $thisFriend['hasWatched'] = $thisFriend->hasWatchedMovie($movie_id) ? 1 : 0;
            $thisFriend['isFavourite'] = $thisFriend->isFavouriteMovie($movie_id) ? 1 : 0;
            $friendsStats[] = $thisFriend;
        }

        //sort alphabetically
        usort($friendsStats,function($a,$b) {return strnatcasecmp($a['name'],$b['name']);});

        return $friendsStats;
    }

    public function findUserByEmail(Request $request){

        $user_id = Auth::user()->id;
        $user_email = User::find($user_id)->email;
        $email = $request->email;

        if ($user_email == $email) return 0;

        $userCount = User::where('email', '=', $email)->count();
        
        return ($userCount > 0);
    }
}
