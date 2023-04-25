<?php

namespace App;

use App\Http\NotificationConst;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Notification extends Model
{

    protected $appends = ['unreadCount', 'rate'];
    public function user() {
        return $this->hasOne('App\User', 'id', 'user_id');
    }

    public function sender() {
        return $this->hasOne('App\User', 'id', 'from_id');
    }

    public function job()
    {
        return $this->belongsTo('App\Job');
    }

    public function getUnreadCountAttribute(){
        return Notification::where('user_id', $this->user_id)->where('shown', '=', 0)->count();
    }

    public function getRateAttribute(){
        if($this->type == NotificationConst::RateBuyer || $this->type == NotificationConst::RateDoer) {
            try {
                $id = \Auth::user()->id;
            } catch (\Exception $e) {
                return null;
            }

            return Rate::where('job_id', $this->job->id)->where("user_id", "=", Auth::user()->id)->first();
        } else {
            return null;
        }
    }


}
