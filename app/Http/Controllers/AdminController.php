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
        }

        return view(
            'admin-settings', [
                "users" => $users,
            ]
        );
    }
}
