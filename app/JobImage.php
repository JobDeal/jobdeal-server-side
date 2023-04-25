<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class JobImage extends Model
{
    use SoftDeletes;

    protected $table = "job_images";

    public function job(){
        return $this->belongsTo("App\Job");
    }
}
