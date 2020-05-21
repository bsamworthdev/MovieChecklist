<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class FriendInvitation extends Model
{
    //
    protected $table = 'friend_invitations';

    protected $fillable = ['user_id', 'email'];
}
