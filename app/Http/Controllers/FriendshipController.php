<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Friendship;
use App\FriendTag;
use App\FriendTagName;
use App\User;

class FriendshipController extends Controller
{

    public function __construct()
    {
        $this->middleware('auth');
    }
    
    public function index($friendTagNameId = "0")
    {

        $user_id = Auth::user()->id;
        $friends = Friendship::find($user_id);
        $user = User::find($user_id);

        if ($friendTagNameId <> 0) {
            $friendTagName = FriendTagName::find($friendTagNameId);
        }

        //Get friend IDs
        $friendsA = $user->friendshipsA()
            ->join('users', 'users.id', '=', 'friendships.person_B_user_id');

        $friends = $user->friendshipsB()
            ->join('users', 'users.id', '=', 'friendships.person_A_user_id')
            ->union($friendsA)
            ->orderBy('name')
            ->get('*');
        
        $filteredFriends = [];
        foreach($friends as &$friend){
            $friendUser = User::find($friend->id);
            $friend->name = $friendUser->name;
            //Get friends' stats
            $friend->stats = $friendUser->getStats();
            //Get friends' tags
            $friend->tags = $friendUser->getTags($user_id);

            //Filter by selected tag
            if ($friendTagNameId <> 0){
                $matchFound = false;
                foreach ($friend->tags as $tag){
                    if ($friendTagName->name == $tag){
                        $matchFound=true;
                        break;
                    }
                }
            } else {
                $matchFound=true;
            }
            $friend->matchesTagFilter = $matchFound;
        }


        $friendTagNames = [];
        $friendTags = FriendTag::where('subject_user_id', $user_id)->get();
        foreach ($friendTags as $friendTag) {
            $friendTagName = FriendTagName::find($friendTag->tag_id);
            if(!in_array($friendTagName, $friendTagNames)){
                $friendTagNames[] = $friendTagName;
            }
        }
        usort($friendTagNames,function($a,$b) {return strnatcasecmp($a['name'],$b['name']);});

        $selectedFriendTagNameId = $friendTagNameId;

        return view('friends', [
                "friends" => $friends,
                "friendTagNames" => $friendTagNames,
                "selectedFriendTagNameId" => $selectedFriendTagNameId
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
        $user_id = Auth::user()->id;

        // $friendTag = FriendTag::where('subject_user_id', $user_id)
        // ->where('object_user_id', $request->id)
        // ->get();

        // $friendship->tags = $request->tags;
        
        // dd($request);
        $tags = json_decode($request->tags);

        //Remove existing friend tags
        FriendTag::where('subject_user_id', $user_id)
            ->where('object_user_id', $request->id)
            ->delete();

        foreach ($tags as $tag){
            $friendTagName = FriendTagName::where('name',$tag)->first();

            //Add tag name if it doesn't already exist
            if (!$friendTagName){
                $friendTagName = new FriendTagName;
                $friendTagName->name = $tag;
                $friendTagName->save();
                $friendTagName = FriendTagName::where('name',$tag)->first();
            }

            $friendTag = new FriendTag;
            $friendTag->subject_user_id = $user_id;
            $friendTag->object_user_id = $request->id;
            $friendTag->tag_id = $friendTagName->id;

            $friendTag->save();
        }
        
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
