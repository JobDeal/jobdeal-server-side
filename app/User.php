<?php

namespace App;

use App\Http\JobConst;
use Carbon\Carbon;
use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class User extends Authenticatable
{
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    protected $appends = ['conversationId', 'rate', 'activeJobs', 'subscription', 'avatarImage', 'notificationCount'];

    public function jobs()
    {
        return $this->hasMany('App\Job');
    }

    public function reports()
    {
        return $this->hasMany('App\Report');
    }

    public function participants()
    {
        return $this->hasMany('App\Participant');
    }

    public function applicants()
    {
        return $this->hasMany('App\Applicant');
    }

    public function payments()
    {
        return $this->hasMany('App\Payment');
    }

    public function subscriptions()
    {
        return $this->hasMany('App\Subscription');
    }

    //message status in conversation
    public function messages()
    {
        return $this->belongsToMany('App\Message')
            ->withPivot('seen_at')
            ->withTimestamps();
    }

    public function getConversationIdAttribute()
    {
        if (!Auth::check())
            return null;

        foreach ($this->participants as $participant) {
            $conversations[] = $participant->conversation_id;
        }

        if (!empty($conversations)) {
            $conversation = Participant::whereIn("conversation_id", $conversations)
                ->where('user_id', '=', Auth::user()->id)
                ->first();
        }

        if (isset($conversation))
            return $conversation->conversation_id;

        return null;
    }

    public function getRateAttribute()
    {
        if (!\Auth::user() || \Auth::user()->id == null)
            return null;

        $avgBuyer = Rate::where("buyer_id", "=", $this->id)->avg("rate");
        $avgDoer = Rate::where("doer_id", "=", $this->id)->avg("rate");
        $countBuyer = Rate::where("buyer_id", "=", $this->id)->count();
        $countDoer = Rate::where("doer_id", "=", $this->id)->count();

        $res = array();
        $res["avgBuyer"] = $avgBuyer;
        $res["avgDoer"] = $avgDoer;
        $res["countBuyer"] = $countBuyer;
        $res["countDoer"] = $countDoer;

        if ($res["avgBuyer"] == null)
            $res["avgBuyer"] = 0;
        else
            $res["avgBuyer"] = round($res["avgBuyer"], 1);

        if ($res["avgDoer"] == null)
            $res["avgDoer"] = 0;
        else
            $res["avgDoer"] = round($res["avgDoer"], 1);

        return $res;
    }

    public function getActiveJobsAttribute()
    {
        if (!\Auth::user() || \Auth::user()->id == null)
            return 0;

        return Job::where("user_id", "=", Auth::user()->id)->where("is_active", "=", 1)->where("expire_at", ">=", Carbon::now()->toDateTimeString())->count();
    }

    public function getSubscriptionAttribute()
    {
        if (!\Auth::user() || \Auth::user()->id == null)
            return null;

        $subscription = Subscription::where("user_id", "=", $this->id)->where("to_date", ">=", Carbon::now()->toDateTimeString())->where("is_paid", "=", 1)->where("is_canceled", "=", 0)->orderBy("to_date", "DESC")->first();

        if ($subscription)
            return Carbon::parse($subscription->to_date)->toDateString();
        else
            return null;
    }

    public function getAvatarImageAttribute()
    {
        if ($this->avatar == null) {
            return null;
        }

        try {
            return Storage::cloud()->url($this->avatar);
        } catch (\Exception $e) {
            \Log::error($e->getMessage());
            return null;
        }
    }

    public function getNotificationCountAttribute(){

        if (!\Auth::user() || \Auth::user()->id == null)
            return 0;

        $notificationCount =  Notification::where("user_id", "=",  Auth::user()->id)->where("shown", "=", 0)->count();

        return $notificationCount;
    }
}
