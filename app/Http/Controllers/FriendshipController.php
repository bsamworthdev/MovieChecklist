<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Friendship;
use App\User;

class FriendshipController extends Controller
{
    public function index()
    {

        $user_id = Auth::user()->id;

        //$friends = Friendship::find($user_id);
        $user = User::find($user_id);
        $friends = $user->friendships()
            ->join('users', 'users.id', '=', 'friendships.person_B_user_id')
            ->get('*');

        return view('friends', [
                "friends" => $friends
            ]
        );
    }

    function add(Request $request) {
        $friendship = new Friendship;
        $friendship->person_A_ = $request->people_id;

        $friendship->save();
        
        return back()->with('message', 'friend added successfully');
    }

    function edit(Request $request) {
        $friendship = Friendship::find($request->id);
        $friendship->people_id = $request->people_id;
        $friendship->friendship_time = $request->friendshiptime;
        $friendship->answered = $request->answered ? 1 : 0;
        $friendship->requires_followup = $request->requiresfollowup ? 1 : 0;
        $friendship->notes = $request->notes;
        
        $friendship->save();
        
        return back()->with('message', 'friend edited successfully');
    }

    function delete(Request $request) {
        $user_id = Auth::user()->id;
        $friend_user_id = $request->id;
        $friendship = Friendship::where('person_a_user_id', $user_id)
            ->where('person_b_user_id', $friend_user_id)
            ->first();

        $friendship->delete();
        
        return back()->with('message', 'friend deleted successfully');
    }
}
