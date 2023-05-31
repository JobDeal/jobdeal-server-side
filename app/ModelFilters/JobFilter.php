<?php namespace App\ModelFilters;

use Carbon\Carbon;
use EloquentFilter\ModelFilter;
use MatanYadaev\EloquentSpatial\Objects\Point;
use Illuminate\Support\Facades\Auth;

class JobFilter extends ModelFilter
{
    /**
     * Related Models that have ModelFilters as well as the method on the ModelFilter
     * As [relationMethod => [input_key1, input_key2]].
     *
     * @var array
     */
    public $relations = [];

    public function fromPrice($value)
    {
        return $this->where('price', ">=", $value);
    }

    public function toPrice($value)
    {
        if ($value >= 1001)
            return;

        return $this->where("price", "<=", $value);
    }

    public function fromDistance($value)
    {
        return $this->whereRaw('ST_Distance_Sphere(Point(' . request()->get("currentLocation")["lng"] . ',' . request()->get("currentLocation")["lat"] . '), location) >= ' . $value);
    }

    public function toDistance($value)
    {
        if ($value >= 100000)
            return;

        return $this->whereRaw('ST_Distance_Sphere(Point(' .  request()->get("currentLocation")["lng"] . ',' . request()->get("currentLocation")["lat"] . '), location) <= ' . $value);
    }

    public function location($value)
    {
        return $this->distanceSphere("location", new Point($value["lat"], $value["lng"]), $value["distance"]);
    }

    public function helpOnTheWay($value)
    {
        if ($value == true) {
            return $this->whereRaw('(help_on_the_way is null or DATEDIFF(now(), help_on_the_way) <= 5)');
        }  else if ($value == false) {
            return $this->where('help_on_the_way', '=', null);
        }

    }

    public function categories($values)
    {
        if (!empty($values))
            return $this->whereIn("category_id", $values);
    }


    public function setup()
    {
        if(Auth::check()) {
            return $this->selectRaw('*, ST_Distance_Sphere(Point(' . request()->get("currentLocation")["lng"] . ',' . request()->get("currentLocation")["lat"] . '), location) as distance')
                ->where("expire_at", ">=", Carbon::now()->toDateTimeString())
                ->whereNotIn("id", Auth::user()->reports->pluck("job_id"))
                ->where("is_active", "=", 1);
        } else {
            return $this->selectRaw('*, ST_Distance_Sphere(Point(' . request()->get("currentLocation")["lng"] . ',' . request()->get("currentLocation")["lat"] . '), location) as distance')
                ->where("expire_at", ">=", Carbon::now()->toDateTimeString())
                //->whereNotIn("id", Auth::user()->reports->pluck("job_id"))
                ->where("is_active", "=", 1);
        }
    }


}
