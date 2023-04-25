<?php

namespace App\Http\Controllers;

use App\Http\Helper;
use App\Subscription;
use App\User;
use App\Wishlist;
use Carbon\Carbon;
use Grimzy\LaravelMysqlSpatial\Types\Point;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class WishlistController extends Controller
{
    public function addUpdateWishlist(Request $request){


        $validator = Validator::make($request->json()->all(), [
            'currentLocation' => 'required',
            'filter' => 'required'
        ]);

        //chekc is subscription exists
        $subscription = Subscription::where("user_id", "=", \Auth::user()->id)
            ->where("to_date", ">=", Carbon::now()->toDateTimeString())
            ->where("is_paid", "=", 1)
            ->where("is_canceled", "=", 0)
            ->orderBy("to_date", "DESC")->first();

// COMMENTED BC THERE ARE NO MORE SUBSCRIPTIONS
//        if(!$subscription)
//            return response(Helper::jsonError(__("lang.wishlist_subscription_not_exists")), 470);

        $wishlist = Wishlist::where("user_id", "=", \Auth::user()->id)->first();

        if(!$wishlist)
            $wishlist = new Wishlist();


        $wishlist->user_id = \Auth::user()->id;
        $wishlist->is_active = 1;
        $wishlist->from_price = $request->json("filter")["fromPrice"];
        $wishlist->to_price = $request->json("filter")["toPrice"];
        $wishlist->from_distance = $request->json("filter")["fromDistance"];
        $wishlist->to_distance = $request->json("filter")["toDistance"];
        $wishlist->categories = $request->json("filter")["categories"];
        $wishlist->location = new Point($request->json("currentLocation")["lat"], $request->json("currentLocation")["lng"]);
        $wishlist->country = \Auth::user()->country;
        $wishlist->save();


        \Log::warning("savedWishList    ".json_encode($wishlist));

        $response = [];
        $response["currentLocation"]["lat"] = (double) $wishlist->location->getLat();
        $response["currentLocation"]["lng"] = (double) $wishlist->location->getLng();
        $response["filter"]["fromPrice"] = (float) $wishlist->from_price;
        $response["filter"]["toPrice"] = (float) $wishlist->to_price;
        $response["filter"]["fromDistance"] = (float) $wishlist->from_distance;
        $response["filter"]["toDistance"] = (float) $wishlist->to_distance;
        $response["filter"]["categories"] = $wishlist->categories;


        return response($response);
    }

    public function getWishlist(){
        $wishlist = Wishlist::where("user_id", "=", \Auth::user()->id)->first();

        if(!$wishlist) {
            $response = [];
            $response["currentLocation"]["lat"] = (double) 0;
            $response["currentLocation"]["lng"] = (double) 0;
            $response["filter"]["fromPrice"] = (float) 0;
            $response["filter"]["toPrice"] = (float) 3000;
            $response["filter"]["fromDistance"] = (float) 0;
            $response["filter"]["toDistance"] = (float) 100000;
            $response["filter"]["categories"] = [];

            return response($response);
        }


        $response = [];
        $response["currentLocation"]["lat"] = (double) $wishlist->location->getLat();
        $response["currentLocation"]["lng"] = (double) $wishlist->location->getLng();
        $response["filter"]["fromPrice"] = (float) $wishlist->from_price;
        $response["filter"]["toPrice"] = (float) $wishlist->to_price;
        $response["filter"]["fromDistance"] = (float) $wishlist->from_distance;
        $response["filter"]["toDistance"] = (float) $wishlist->to_distance;
        $response["filter"]["categories"] = $wishlist->categories;

        return response($response);
    }
}
