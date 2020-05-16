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

            $friendInvitation = new FriendInvitation;
            $friendInvitation->user_id = $user_id;
            $friendInvitation->email = $request->email;
            $friendInvitation->save();

            //Send email
            Mail::to($request->email)
                ->send(new FriendInvitationMailable($userObj));

            return back()->with('message', 'friend invitation sent');
        } else {
            //Cancel existing friend requests
            $friendRequest = FriendRequest::where('sender_user_id', '=', $user_id)
                ->where('recipient_user_id', '=', $recipientUser->id)
                ->where('status', '!=', 'cancelled')
                ->update(array('status' => 'cancelled'));

            //Create new friend request
            $token = Str::uuid();
            $friendRequest = new FriendRequest;
            $friendRequest->sender_user_id = $user_id;
            $friendRequest->recipient_user_id = $recipientUser->id;
            $friendRequest->token = $token;
            $friendRequest->status = 'pending';
            $friendRequest->save();

            //Send email
            Mail::to($request->email)
                ->send(new FriendRequestMailable($userObj, $token));

            return back()->with('message', 'friend request sent');
        }
    }
}
