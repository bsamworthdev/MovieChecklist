<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\User;

class AdminController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(){
       $users = User::orderBy('name')->get();

        foreach ($users as &$user){           
            $user->stats = $user->getSpecificStats();
            $user->watchedCount = $user->stats['watched'];
            $user->created_at_tidy = date('Y-m-d H:i', strtotime($user->created_at));
        }

        return view(
            'admin-settings', [
                "users" => $users,
            ]
        );
    }
}
