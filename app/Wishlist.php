<?php

namespace App;

use MatanYadaev\EloquentSpatial\Objects\Point;
use MatanYadaev\EloquentSpatial\Traits\HasSpatial;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Wishlist extends Model
{
    use SoftDeletes, HasSpatial;

    protected $primaryKey = "user_id";
    protected $table = "wishlists";
    protected $casts = [
        'categories' => 'array',
        'location' => Point::class,
    ];
    protected $spatialFields = ['location'];
    protected $guarded = [];

    public function user(){
        $this->belongsTo("App\User");
    }

    /*public function setCategoriesAttribute($value)
    {
        $categories = [];

        foreach ($value as $array_item) {
            if (!is_null($array_item['key'])) {
                $categories[] = $array_item;
            }
        }

        $this->attributes['categories'] = json_encode($categories);
    }*/

}
