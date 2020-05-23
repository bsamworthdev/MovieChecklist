<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Friendship extends Model
{
    protected $table = 'friendships';

    protected $fillable = ['person_A_user_id', 'person_B_user_id'];

    public function user()
    {
        return $this->belongsTo('User');
    }
}
