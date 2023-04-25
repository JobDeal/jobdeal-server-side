<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Payment extends Model
{
    use SoftDeletes;
    public $html_snippet;

    public function user(){
        return $this->belongsTo("App\User");
    }

    public function doer(){
        return $this->belongsTo("App\User");
    }

    public function job(){
        return $this->belongsTo("App\Job");
    }
}
