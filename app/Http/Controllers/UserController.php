<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{

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
            DB::statement("insert into movie_user (user_id,movie_id) values ($user_id, $movie_id)");
            // DB::insert('insert into users (email, votes) values (?, ?)', ['john@example.com', '0']);
        } else {
            DB::statement("delete from movie_user where user_id=$user_id and movie_id=$movie_id");
        }
    }
}
