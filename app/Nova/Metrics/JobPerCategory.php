<?php

namespace App\Nova\Metrics;

use App\Category;
use App\Job;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Laravel\Nova\Metrics\Partition;

class JobPerCategory extends Partition
{
    /**
     * Calculate the value of the metric.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return mixed
     */
    public function calculate(Request $request)
    {
        //return $this->count($request, Place::class, 'sport');
        return $this->count($request, Job::orderBy('aggregate', 'desc'), 'category_id')
            ->label(function ($value) use ($request){
                $category = Category::where("id", "=", $value)->first();

                if($category)
                    return $category->name;
                return $value;
            });

    }

    /**
     * Get the URI key for the metric.
     *
     * @return string
     */
    public function uriKey()
    {
        return 'job-per-category';
    }
}
