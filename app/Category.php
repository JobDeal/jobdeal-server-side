<?php

namespace App;

use App\Http\Resources\CategoryResource;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Translatable\HasTranslations;


class Category extends Model
{
    use SoftDeletes, HasTranslations;

    public $translatable = ['name','description'];
    protected $appends = ["totalSubCategoryCount",'translatedName'];

    public function jobs(){
        return $this->hasMany('App\Job');
    }

    public function subCategory(){
        return $this->hasMany("App\Category", "parent_id", "id");
    }

    public function getTotalSubCategoryCountAttribute(){
        $totalSubs = 0;
        $totalSubs = $totalSubs + $this->subCategory()->count();
        foreach ($this->subCategory as $category){
            $totalSubs = $totalSubs + $category->subCategory()->count();
        }

        return $totalSubs;
    }

    public function getTranslatedNameAttribute(){

    }
}
