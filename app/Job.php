<?php

namespace App;

use App\Http\Helper;
use App\Http\Resources\ImageResource;
use App\ModelFilters\JobFilter;
use Carbon\Carbon;
use EloquentFilter\Filterable;
use MatanYadaev\EloquentSpatial\Objects\Point;
use MatanYadaev\EloquentSpatial\Traits\HasSpatial;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Mockery\Exception;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;


class Job extends Model implements HasMedia
{
    use InteractsWithMedia, HasSpatial, Filterable;

    protected $spatialFields = ['location'];
    protected $appends = ['bidCount', 'isBookmark', 'isApplied', 'mainImage', 'allImages', 'choosedCount',
        'isChoosed', 'isExpired'];
    protected $casts = [
        'expire_at' => 'datetime',
        'location' => Point::class,
    ];

//    public function getLongitudeAttribute()
//    {
//        return $this->location->getLng();
//    }
//
//    public function getLatitudeAttribute()
//    {
//        return $this->location->getLat();
//    }


    public function user()
    {
        return $this->belongsTo('App\User');
    }

    public function rates()
    {
        return $this->hasMany('App\Rate');
    }

    public function category()
    {
        return $this->belongsTo('App\Category');
    }

    public function payments(){
        return $this->hasMany('App\Payment');
    }

    public function reports(){
        return $this->hasMany('App\Report');
    }

    public function applicants()
    {
        return $this->hasMany('App\Applicant');
    }

    public function images()
    {
        return $this->hasMany(JobImage::class, "job_id", "id");
    }

    public function modelFilter()
    {
        return $this->provideFilter(JobFilter::class);
    }

    public function getBidCountAttribute()
    {

        $bidCount = Applicant::where("job_id", $this->id)->count();

        return $bidCount;
    }

    public function getIsBookmarkAttribute()
    {
        try {
            $id = \Auth::user()->id;
        } catch (\Exception $e) {
            return false;
        }

        $bookmark = Bookmark::where("user_id", "=", \Auth::user()->id)
            ->where("job_id", "=", $this->id)->first();

        if ($bookmark)
            return true;
        else
            return false;
    }

    public function getIsAppliedAttribute()
    {
        try {
            $id = \Auth::user()->id;
        } catch (\Exception $e) {
            return false;
        }

        $applicant = Applicant::where("job_id", $this->id)->where("user_id", \Auth::user()->id)->first();

        if ($applicant)
            return true;
        else
            return false;
    }



    public function getIsChoosedAttribute()
    { //this can be one query with isApplied its almost same query...
        try {
            $id = \Auth::user()->id;
        } catch (\Exception $e) {
            return false;
        }

        $applicant = Applicant::where("job_id", $this->id)->where("user_id", \Auth::user()->id)->where("choosed_at", "!=", null)->first();

        if ($applicant)
            return true;
        else
            return false;
    }

    public function getMainImageAttribute()
    {
        $mainImage = JobImage::where("job_id", "=", $this->id)->orderBy("position", "ASC")->first();

        $url = null;

        try {
            $url = Storage::cloud()->url($mainImage->path);
        } catch (\Exception $e) {

        }

        if ($url)
            return $url;
        else
            return null;
    }

    public function getIsExpiredAttribute()
    {
        if(Carbon::now()->greaterThan($this->expire_at))
            return true;
        else
            return false;
    }

    public function getAllImagesAttribute()
    {
        $allImages = array();
        $images = JobImage::where("job_id", "=", $this->id)->orderBy("position", "ASC")->get();

        foreach ($images as $img) {
            $url = null;

            try {
                $url = Storage::cloud()->url($img->path);

                $s3Image = array();
                $s3Image["id"] = $img->id;
                $s3Image["fullUrl"] = $url;
                $s3Image["position"] = $img->position;

                array_push($allImages, $s3Image);
            } catch (\Exception $e) {
            }
        }

        return $allImages;
    }

    public function getChoosedCountAttribute()
    {
        $choosed = Applicant::where("job_id", $this->id)->where("choosed_at", "!=", null)->count();

        return $choosed;
    }

    public function prepareGeom($model)
    {
        if(isset($model->location_string)) {
            $lon = explode(" ", $model->location_string)[1];
            $lat = explode(" ", $model->location_string)[0];
            $model->location = new Point($lat, $lon);
        }
    }

    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            //$model->prepareGeom($model);
            /*  Log::debug("Place creating...");

              $model->getCityCountryForPlace($model);*/
        });

        static::saving(function ($model) {
            $model->prepareGeom($model);//Da li treba da bude i saving i creating, moramo da vidimo kad se ovo poziva. Msm da je nepotrebno da bude u oba.
        });

        static::updating(function ($model) {
            $model->prepareGeom($model);
        });
    }






}
