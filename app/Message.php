<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    public function notification()
    {
        return $this->hasOne('App\Notification');
    }

    public function sender()
    {
        return $this->belongsTo('App\User', 'sender_id','id');
    }

    public function conversation()
    {
        return $this->belongsTo('App\Conversation');
    }

    //message status in conversation
    public function users()
    {
        return $this->belongsToMany('App\User')
            ->withPivot('seen_at')
            ->withTimestamps();
    }
}
