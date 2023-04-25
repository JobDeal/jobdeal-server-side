<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends Model
{
    use SoftDeletes;

    public function job() {
        return $this->belongsTo('App\Job');
    }

    public function user() {
        return $this->belongsTo('App\User');
    }
}
