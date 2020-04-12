<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    //

    public function saveUserMovies(Request $request)
    {
        $user_id = Auth::user()->id;
        
        return back()->with('message', 'Updated list successfully');
    }
}
