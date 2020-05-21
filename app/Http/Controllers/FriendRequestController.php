<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\FriendRequest;
use App\FriendInvitation;
use App\Friendship;
use App\User;
use App\Mail\FriendRequestMailable;
use App\Mail\FriendInvitationMailable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class FriendRequestController extends Controller
{
    
    public function __construct()
    {
        $this->middleware('auth');
    }

    function create(Request $request) {

        $user = Auth::user();
        $user_id = $user->id;
        $userObj = User::find($user_id);

        //Find recipient user_id by email
        $recipientUser = User::where('email', '=', $request->email)->first();

        if ($recipientUser === null) {

            FriendInvitation::create([
                "user_id" => $user_id,
                "email" => $request->email
            ]);

            //Send email
            Mail::to($request->email)
                ->send(new FriendInvitationMailable($userObj));

            return back()->with('message', 'friend invitation sent');
        } else {

            //Check friendship does not already exist
            $friendshipExists = Friendship::where([
                ['person_A_user_id', '=', $user_id],
                ['person_B_user_id', '=', $recipientUser->id]
            ])
            ->orWhere([                        
                ['person_A_user_id', '=', $recipientUser->id],
                ['person_B_user_id', '=', $user_id]
            ])->count() > 0;

            if ($friendshipExists) {
                return back()->with('message', 'Already friends with this person');
            }

            //Cancel existing friend requests
            $friendRequest = FriendRequest::where('sender_user_id', '=', $user_id)
                ->where('recipient_user_id', '=', $recipientUser->id)
                ->where('status', '!=', 'cancelled')
                ->update(array('status' => 'cancelled'));

            //Create new friend request
            $token = Str::uuid();
            FriendRequest::create([
                "sender_user_id" => $user_id,
                "recipient_user_id" => $recipientUser->id,
                "token" => $token,
                "status" => 'pending',
            ]);

            //Send email
            Mail::to($request->email)
                ->send(new FriendRequestMailable($userObj, $token));

            return back()->with('message', 'friend request sent');
        }
    }
}
