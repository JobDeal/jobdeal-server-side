<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Bookmark extends Model
{
    use SoftDeletes;

    public function user(){
        return $this->hasOne("App\User", "id", "user_id");
    }

    public function job(){
        return $this->hasOne("App\Job", "id", "job_id");
    }
}
