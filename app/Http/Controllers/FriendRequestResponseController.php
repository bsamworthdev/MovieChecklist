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

    function index(Request $request) {

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
                Friendship::create([
                    "person_A_user_id" => $friendRequest->sender_user_id,
                    "person_B_user_id" => $friendRequest->recipient_user_id
                ]);

                //Update friend request
                $friendRequest->status = 'active';
                $friendRequest->save();

                //Email confirmation
                $sender = User::find($friendRequest->sender_user_id);
                $user = User::find($friendRequest->recipient_user_id);
                Mail::to($sender->email)
                     ->send(new FriendRequestAcceptMailable($user));

                return view('friend-request-accept', [
                    "user" => $sender
                ]);
            }
        }

    }
}
