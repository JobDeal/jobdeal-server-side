<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Conversation extends Model
{
    protected $appends = ["lastReceiver", "latestMessage", "seen", "unread"];

    public function messages()
    {
        return $this->hasMany('App\Message');
    }

    public function participants(){
        return $this->hasMany('App\Participant');
    }

    public function sender()
    {
        return $this->belongsTo('App\User', 'sender_id','id');
    }

    public function getUnreadAttribute(){
        $participant = $this->participants->where('user_id','=',Auth::user()->id)->first();

        if(isset($participant))
            return $participant->unread;

        return null;
    }

    public function getLastReceiverAttribute()
    {

        $lastUser = $this->participants()->where("conversation_id", "=", $this->id)
            ->where("user_id", "!=", Auth::user()->id)
            ->orderBy("created_at", "DESC")->first();

        if($lastUser) {
            $user = User::where("id", "=", $lastUser->user_id)->first();

            if ($user)
                return $user;
        }

        return null;
    }

    public function getLatestMessageAttribute(){
        return $this->messages()->orderBy("created_at", "DESC")->first();
    }

    public function getSeenAttribute(){

        $seen = $this->messages()->orderBy("created_at", "DESC")->first()->users()->first();
        if($seen)
            return $seen->pivot->seen_at;

        return null;
    }
}
