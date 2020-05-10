<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\FriendRequest;
use App\Friendship;
use App\User;
use App\Mail\FriendRequestMailable;
use App\Mail\FriendRequestAcceptMailable;
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
        if ($recipientUser === null) return 'not found';

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

    function accept(Request $request) {

        $friendRequest = FriendRequest::where('token','=',$request->token)->first();

        if ($friendRequest === null){
            //Not found
        } else{
            if ($friendRequest->status == 'accepted'){
                //already accepted
            } elseif ($friendRequest->status == 'cancelled') { 
                //closed
            } elseif ($friendRequest->status == 'pending') { 
                //accept

                //Add friendship
                $friendship = new Friendship;
                $friendship->person_A_user_id = $friendRequest->sender_user_id;
                $friendship->person_B_user_id = $friendRequest->recipient_user_id;
                $friendship->save();

                //Update friend request
                $friendRequest->status = 'active';
                $friendRequest->save();

                //Email confirmation
                $user = User::find($friendRequest->sender_user_id);
                Mail::to($user->email)
                     ->send(new FriendRequestAcceptMailable($user));

                return view('friend-request-accept', [
                        "user" => $user
                    ]
                );
            }
        }

    }
}
