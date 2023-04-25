<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Rate extends Model
{

    public function user(){
        return $this->belongsTo('App\User');
    }

    public function buyer(){
        return $this->belongsTo('App\User');
    }

    public function doer(){
        return $this->belongsTo('App\User');
    }

    public function job(){
        return $this->belongsTo('App\Job');
    }
}
