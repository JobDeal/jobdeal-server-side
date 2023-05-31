<?php

namespace App\Jobs;

use App\Http\Helper;
use App\Http\NotificationConst;
use App\Http\Resources\JobLiteResource;
use App\Job;
use App\Notification;
use App\Wishlist;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class CheckWishlist implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $jobdeal;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($jobModel)
    {
        $this->jobdeal = $jobModel;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $wishlists = Wishlist::where("user_id", "!=", $this->jobdeal->user_id)
            ->where("is_active", "=", 1)->where("country", "=", $this->jobdeal->country)
            ->get();

        foreach ($wishlists as $wishlist){
            $isRequirementsOk = true;

            if($wishlist->from_price != null && $wishlist->from_price > 0){
                if($wishlist->from_price > $this->jobdeal->price){
                    $isRequirementsOk = false;
                    //\Log::info("From price not OK!");
                }
            }

            if($wishlist->to_price != null && $wishlist->to_price < 1001){ //if to price is 1001 that meen that to price doesn't have limit
                if($this->jobdeal->price > $wishlist->to_price){
                    $isRequirementsOk = false;
                    //\Log::info("To price not OK!");
                }
            }

            if(count($wishlist->categories) > 0){
                $categories = collect($wishlist->categories);

                if(!$categories->contains($this->jobdeal->category_id)){
                    $isRequirementsOk = false;
                    //\Log::info("Categories not OK!");
                }
            }

            $lat = $wishlist->location->latitude;
            $lng = $wishlist->location->longitude;

            if($lat > 0 && $lng > 0 && $wishlist->from_distance > 0){
                if(Helper::getDistance($lat, $lng, $this->jobdeal->location->latitude, $this->jobdeal->location->longitude) < $wishlist->from_distance){
                    $isRequirementsOk = false;
                    //\Log::info("Distance from not OK!");
                }
            }

            if($lat > 0 && $lng > 0 && $wishlist->to_distance < 100001){ //less that 100km otherwise don't look to distance
                if(Helper::getDistance($lat, $lng, $this->jobdeal->location->latitude, $this->jobdeal->location->longitude) > $wishlist->to_distance){
                    $isRequirementsOk = false;
                    //\Log::info("Distance to not OK!");
                }
            }

            if($isRequirementsOk){
                //\Log::debug("USER ID: " . $wishlist->user_id);

                //add notification do user that doer bid
                $notification = new Notification();
                $notification->user_id = $wishlist->user_id;
                $notification->from_id = -1;
                $notification->job_id = $this->jobdeal->id;
                $notification->type = NotificationConst::WishlistJob;
                $notification->save();

                PushNotification::dispatch($wishlist->user_id, -1, NotificationConst::WishlistJob, $notification->id,
                    new JobLiteResource($this->jobdeal), __("lang.wishlistJob_description", []),
                    __("lang.wishlistJob_title", []), $sendNotification = true);
            }
        }
    }
}
