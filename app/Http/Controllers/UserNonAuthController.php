<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserNonAuthController extends Controller
{
    public function saveMovieUser(Request $request)
    {
        
        $session = $request->session();
        $session_id = $session->getId();

        $movie_id = $request->movie_id;
        
        if ($request->watched){
            DB::statement("insert into movie_user_non_auth (session_id,movie_id, created_at, updated_at) values ('$session_id', $movie_id, NOW(), NOW())");
        } else {
            DB::statement("delete from movie_user_non_auth where session_id='$session_id' and movie_id=$movie_id");
        }
    }

    public function setMovieAsFavourite(Request $request)
    {
        $session = $request->session();
        $session_id = $session->getId();

        $movie_id = $request->movie_id;

        if ($request->favourite){
            DB::update("update movie_user_non_auth set favourite=1, updated_at=NOW() where session_id=? and movie_id=?", [$session_id, $movie_id]);
        } else {
            DB::update("update movie_user_non_auth set favourite=0, updated_at=NOW() where session_id=? and movie_id=?", [$session_id, $movie_id]);
        }
    }

    public function toggleMovieInWatchList(Request $request)
    {
        $session = $request->session();
        $session_id = $session->getId();

        $movie_id = $request->movie_id;
        
        if ($request->onWatchList){
            DB::statement("insert into watch_list_non_auth (session_id, movie_id, created_at, updated_at) values ('$session_id', $movie_id, NOW(), NOW())");
        } else {
            DB::statement("delete from watch_list_non_auth where session_id='$session_id' and movie_id=$movie_id");
        }
    }
}
