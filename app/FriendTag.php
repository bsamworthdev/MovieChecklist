<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\FriendTagName;

class FriendTag extends Model
{
    protected $table = 'friend_tags';

    public function name()
    {
        return $this->hasOne('App\FriendTagName','id');
    }
}
