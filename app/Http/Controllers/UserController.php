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
        $data = $request->validate([
            'movie_id' => 'required|integer|exists:movies,id',
            'watched' => 'required|boolean',
        ]);

        if ($data['watched']) {
            // The catalogue/user/movie unique index makes repeated requests safe.
            DB::table('movie_user')->insertOrIgnore([
                'movie_or_tv' => 'movie', 'user_id' => $user_id, 'movie_id' => $data['movie_id'], 'favourite' => 0,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        } else {
            DB::table('movie_user')->where('movie_user.movie_or_tv', 'movie')->where('user_id', $user_id)->where('movie_id', $data['movie_id'])->delete();
        }

        return response()->json(['watched' => (bool) $data['watched']]);
    }

    public function setMovieAsFavourite(Request $request)
    {
        $data = $request->validate(['movie_id' => 'required|integer|exists:movies,id', 'favourite' => 'required|boolean']);
        DB::table('movie_user')->where('movie_user.movie_or_tv', 'movie')->where('user_id', $request->user()->id)->where('movie_id', $data['movie_id'])
            ->update(['favourite' => $data['favourite'], 'updated_at' => now()]);
    }

    public function toggleMovieInWatchList(Request $request)
    {
        $data = $request->validate(['movie_id' => 'required|integer|exists:movies,id', 'onWatchList' => 'required|boolean']);
        $key = ['movie_or_tv' => 'movie', 'user_id' => $request->user()->id, 'movie_id' => $data['movie_id']];
        if ($data['onWatchList']) DB::table('watch_list')->updateOrInsert($key, ['updated_at' => now()]);
        else DB::table('watch_list')->where($key)->delete();
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

    public function updateFiltersShown(Request $request, $show = 0){
        $user_id = Auth::user()->id;

        User::where('id', $user_id)
           ->update([
                'filters_shown' => $show
           ]);
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
