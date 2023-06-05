<?php

namespace App\Http\Controllers;


use App\Http\Helper;
use App\Http\NotificationConst;
use App\Http\Resources\JobLiteResource;
use App\Http\Resources\RateResource;
use App\Job;
use App\Jobs\PushNotification;
use App\Notification;
use App\Rate;


use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use Illuminate\Support\Facades\Validator;


class RateController extends Controller
{
    /**
     * @OA\Post(
     *     tags={"Rate"},
     *     path="/rate/add",
     *     summary="Add a rate",
     *     security={{"bearer_token":{}}},
     *     @OA\RequestBody(
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 @OA\Property(property="rate", type="string"),
     *                 @OA\Property(property="comment", type="string"),
     *                 @OA\Property(
     *                     property="buyer",
     *                     type="object",
     *                     @OA\Property(property="id", type="integer")
     *                 ),
     *                 @OA\Property(
     *                     property="job",
     *                     type="object",
     *                     @OA\Property(property="id", type="integer")
     *                 ),
     *                 @OA\Property(
     *                     property="doer",
     *                     type="object",
     *                     @OA\Property(property="id", type="integer")
     *                 )
     *             )
     *         ),
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="OK"
     *     )
     * )
     */
    public function addRate(Request $request)
    {
        $validator = Validator::make($request->json()->all(), [
            'rate' => 'required'
        ]);

        if ($validator->fails()) {
            return response($validator->messages()->first(), 400);
        }

        $job = Job::where("id", "=", $request->json('job')["id"])->first();

        if(!$job)
            return response(Helper::jsonError("Job not found!"), 404);


        $rate = new Rate();
        $rate->user_id = Auth::user()->id;
        if($request->json('buyer')){//rate buyer
            $rate->buyer_id = $request->json('buyer')["id"];

            $notification = Notification::where("from_id", "=", $request->json('buyer')["id"])
                ->where("user_id", "=", Auth::user()->id)
                ->where("type", "=", NotificationConst::RateBuyer)
                ->where("job_id", "=", $job->id)
                ->delete();

            $existRate = Rate::where("user_id", "=", Auth::user()->id)
                ->where("buyer_id", "=", $request->json('buyer')["id"])
                ->first();

            if($existRate){
                return response(new RateResource($existRate));
            }


           /* $notification = new Notification();
            $notification->user_id = $request->json('buyer')["id"];
            $notification->from_id = Auth::user()->id;
            $notification->job_id = $job->id;
            $notification->type = NotificationConst::RateBuyer;
            $notification->save();

            PushNotification::dispatch($request->json('buyer')["id"], Auth::user()->id, NotificationConst::RateBuyer, $notification->id, new JobLiteResource($job), __("lang.rateBuyer_description"),
                __("lang.rateBuyer_title"), $sendNotification = true);*/
        }
        if($request->json('doer')){//rate doer
            $rate->doer_id = $request->json('doer')["id"];

            $notification = Notification::where("from_id", "=", $request->json('doer')["id"])
                ->where("user_id", "=", Auth::user()->id)
                ->where("type", "=", NotificationConst::RateDoer)
                ->where("job_id", "=", $job->id)
                ->delete();

            $existRate = Rate::where("user_id", "=", Auth::user()->id)
                ->where("doer_id", "=", $request->json('doer')["id"])
                ->first();

            if($existRate){
                return response(new RateResource($existRate));
            }

           /* $notification = new Notification();
            $notification->user_id = $request->json('doer')["id"];
            $notification->from_id = Auth::user()->id;
            $notification->job_id = $job->id;
            $notification->type = NotificationConst::RateDoer;
            $notification->save();

            PushNotification::dispatch($request->json('doer')["id"], Auth::user()->id, NotificationConst::RateDoer, $notification->id, new JobLiteResource($job), __("lang.rateDoer_description"),
                __("lang.rateDoer_title"), $sendNotification = true);*/

        }
        $rate->job_id = $request->json('job')["id"];
        $rate->rate = (float)$request->json('rate');
        $rate->comment = $request->json('comment');
        $rate->save();

        return response(new RateResource($rate));
    }

    /**
     * @OA\Get(
     *     tags={"Rate"},
     *     path="/rate/byBuyerId/{userId}/{page}",
     *     summary="Get user rates",
     *     security={{"bearer_token":{}}},
     *     @OA\Parameter(
     *         ref="#/components/parameters/userId"
     *     ),
     *     @OA\Parameter(
     *         ref="#/components/parameters/page"
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="OK"
     *     )
     * )
     */
    public function getRateByBuyerId($buyerId, $page)
    {
        $rates = Rate::where("buyer_id", "=", $buyerId)->limit(20)->offset($page * 20)->get();

        return response(RateResource::collection($rates));
    }

    /**
     * @OA\Get(
     *     tags={"Rate"},
     *     path="/rate/byDoerId/{userId}/{page}",
     *     summary="Get doer rates",
     *     security={{"bearer_token":{}}},
     *     @OA\Parameter(
     *         ref="#/components/parameters/userId"
     *     ),
     *     @OA\Parameter(
     *         ref="#/components/parameters/page"
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="OK"
     *     )
     * )
     */
    public function getRateByDoerId($buyerId, $page)
    {
        $rates = Rate::where("doer_id", "=", $buyerId)->limit(20)->offset($page * 20)->get();

        return response(RateResource::collection($rates));
    }

}
