<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\FriendRequest;
use App\Friendship;
use App\User;
use App\Mail\FriendRequestAcceptMailable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class FriendRequestResponseController extends Controller
{
    //Warning: this controller is deliberately not authenticated, so be careful with it.

    function accept(Request $request) {

        $friendRequest = FriendRequest::where('token','=',$request->token)->first();

        if ($friendRequest === null){
            //Not found
            return view('friend-request-not-found');
        } else{
            if ($friendRequest->status == 'active'){
                //already accepted
                return view('friend-request-void', ["status" => 'accepted']);

            } elseif ($friendRequest->status == 'cancelled') { 
                //cancelled
                return view('friend-request-void', ["status" => 'cancelled']);

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
                ]);
            }
        }

    }
}
