<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Subscription extends Model
{
    //
    use SoftDeletes;

    public function user(){
        return $this->belongsTo("App\User");
    }

    public function payment(){
        return $this->belongsTo("App\Payment");
    }
}
