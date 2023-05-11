<?php

namespace App\Http\Controllers;

use App\Applicant;
use App\Http\Helper;
use App\Http\PayConst;
use App\Http\Resources\BankIdCollectResource;
use App\Http\Resources\BankIdResource;
use App\Http\Resources\KlarnaResource;
use App\Http\Resources\PaymentResource;
use App\Http\Resources\UserResource;
use App\Job;
use App\Jobs\CheckForInvoice;
use App\Jobs\CreateAndSendInvoice;
use App\Jobs\SubscriptionInvoice;
use App\Payment;
use App\PaymentMethod;
use App\Subscription;
use App\User;
use App\Wishlist;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Klarna\Rest\Transport as Klarna;
use Klarna\Rest\Payments as KlarnaPayments;
use Klarna\Rest\OrderManagement as KlarnaManagement;


class PaymentController extends Controller
{
    public function getPaymentById($id)
    {
        $payment = Payment::where("id", "=", $id)->first();

        if (!$payment)
            return response(Helper::jsonError("Payment not found!"), 404);

        if ($payment->user_id != Auth::user()->id)
            return response(Helper::jsonError("Not your payment!"), 400);

        return response(new PaymentResource($payment));
    }

    /*---------------------------------SWISH--------------------------------------*/

    /**
     * @SWG\Post(
     *     path="/payment/swish/pay/job/{type}",
     *     summary="Pay job with Swish",
     *     security={{"bearer_token":{}}},
     *     @SWG\Parameter(
     *         in="path",
     *         name="type",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         in="body",
     *         name="body",
     *         required=true,
     *         @SWG\Schema(
     *             @SWG\Property(property="id", type="integer")
     *         )
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="OK"
     *     )
     * )
     */
    public function swishJobPay(Request $request, $type)
    {
        $payment = new Payment();
        $payment->job_id = $request->json("id");
        $payment->user_id = Auth::user()->id;
        $payment->currency = Helper::getPrices()["currency"];
        $payment->status = "PENDING";
        $payment->provider = "Swish";
        $payment->ref_id = "";

        $job = Job::where("id", "=", $request->json("id"))->first();

        if (!$job)
            return response(Helper::jsonError("Job not found!"), 404);

        if ($type == PayConst::payBoost) {
            $payment->amount = Helper::getPrices()["boost"]  + Helper::getPrices()["swish_fee"];
            $payment->description = "Boost Job";
            $payment->type = PayConst::payBoost;
        } else if ($type == PayConst::paySpeedy) {
            $payment->amount = Helper::getPrices()["speedy"]  + Helper::getPrices()["swish_fee"];
            $payment->description = "Speedy Job";
            $payment->type = PayConst::paySpeedy;
        } else if ($type == PayConst::payList) {
            $payment->amount =  ($job->price * (Helper::getPrices()["list"] / 100)) + Helper::getPrices()["swish_fee"];
            $payment->description = "List Job";
            $payment->type = PayConst::payList;
        } else if ($type == PayConst::payBoostSpeedy) {
            $payment->amount = (Helper::getPrices()["speedy"] + Helper::getPrices()["boost"]) + Helper::getPrices()["swish_fee"];
            $payment->description = "Boosted Speedy Job";
            $payment->type = PayConst::payBoostSpeedy;
        } else if ($type == PayConst::payUnderbidder){
            $payment->amount =  ($job->price * (Helper::getPrices()["list_underbidder"] / 100)) + Helper::getPrices()["swish_fee"];
            $payment->description = "List Underbidders";
            $payment->type = PayConst::payUnderbidder;
        }

        $payment->save();

        //Log::debug("swishJobPay: " . json_encode($request->json()->all()));


        $client = new Client();
        $body = [
            "payeePaymentReference" => $payment->id,
            "amount" => $payment->amount,
            "payeeAlias" => "1234060273",
            "currency" => $payment->currency,
            "message" => "ID: " . $payment->job_id,
            "callbackUrl" => "https://dev.jobdeal.com/api/payment/swish/callback"
        ];

        try {
            $res = $client->request("post", "https://cpc.getswish.net/swish-cpcapi/api/v1/paymentrequests", [
                "cert" => [storage_path("app/swish2021/swish_certificate_202103081611.pem"), "jobdeal2306"],
                'ssl_key' => [storage_path("app/swish2021/swish_certificate_202103081611.key"), 'jobdeal2306'],
                "json" => $body,
            ]);
        } catch (\Exception $e) {
            $payment->status = "FAILED";
            $payment->error = "Guzzle Request";
            $payment->error_message = $e->getMessage();
            $payment->save();

            Log::error("SWISH ERROR: " . $e->getMessage());

            return response(Helper::jsonError($e->getMessage()), 500);
        }

        if ($res->getStatusCode() == 201) {

            Log::debug("SWISH HEADERS: " . json_encode($res->getHeaders()));
            Log::debug("SWISH RES: " . json_encode($res->getBody()->getContents()));
            $swishRef =  $res->getHeader("PaymentRequestToken")[0];

            $swishId = $res->getHeader("Location")[0];
            Log::debug("Swish ID: " . $swishId);
            $swishIdArr = explode( "/", (string) $swishId);
            $swishId = $swishIdArr[count($swishIdArr) - 1];
            $payment->ref_id = $swishRef;
            $payment->swish_id = $swishId;
            $payment->save();

            $result = array();
            $result["refId"] = basename($swishRef);
            $result["paymentId"] = $payment->id;

            return response($result);
        } else {
            $payment->status = "FAILED";
            $payment->error = $res->getStatusCode();
            $payment->error_message = $res->getBody()->getContents();
            $payment->save();

            return response(Helper::jsonError("Error"), 400);
        }
    }

    /**
     * @SWG\Post(
     *     path="/payment/swish/pay/choose",
     *     summary="Choose swish pay",
     *     security={{"bearer_token":{}}},
     *     @SWG\Parameter(
     *         in="path",
     *         name="type",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         in="body",
     *         name="body",
     *         required=true,
     *         @SWG\Schema(
     *             @SWG\Property(property="price", type="string"),
     *             @SWG\Property(
     *                 property="user",
     *                 type="object",
     *                 @SWG\Property(property="id", type="integer")
     *             )
     *         )
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="OK"
     *     )
     * )
     */
    public function swishChoosePay(Request $request)
    {//request is applicant resource

        $job = Job::where("id", "=", $request->json("job")["id"])->first();

        if (!$job)
            return response(Helper::jsonError("Job not found!"), 404);

        $payment = new Payment();
        $payment->job_id = $job->id;
        $payment->doer_id = $request->json("user")["id"];
        $payment->user_id = Auth::user()->id;
        $payment->amount = $job->price * (Helper::getPrices()["choose"] / 100);
        $payment->currency = Helper::getPrices()["currency"];
        $payment->status = "PENDING";
        $payment->provider = "Swish";
        $payment->type = PayConst::payChoose;
        $payment->ref_id = "";

        if ($job->price > $request->json("price")) {//if bid price is lower then buyer set, buyer pay fee for diff in price
            $diff = $job->price - $request->json("price");
            $payment->amount = $payment->amount + ($diff * (Helper::getPrices()["difference"] / 100));
        }

        $payment->save();

        $client = new Client();
        $body = [
            "payeePaymentReference" => $payment->id,
            "amount" => $payment->amount,
            "payeeAlias" => "1234060273",
            "currency" => $payment->currency,
            "message" => __("lang.swishBoostMessage", ["id" => $payment->job_id]),
            "callbackUrl" => "https://dev.jobdeal.com/api/payment/swish/callback"
        ];

        try {
            $res = $client->request("post", "https://cpc.getswish.net/swish-cpcapi/api/v1/paymentrequests", [
                "cert" => [storage_path("app/swish2021/swish_certificate_202103081611.pem"), "jobdeal2306"],
                'ssl_key' => [storage_path("app/swish2021/swish_certificate_202103081611.key"), 'jobdeal2306'],
                "json" => $body,
            ]);
        } catch (\Exception $e) {
            $payment->status = "FAILED";
            $payment->error = "Guzzle Request";
            $payment->error_message = $e->getMessage();
            $payment->save();

            return response(Helper::jsonError($e->getMessage()), 500);
        }

        if ($res->getStatusCode() == 201) {
            $swishRef =  $res->getHeader("PaymentRequestToken")[0];

            $swishId = $res->getHeader("Location")[0];
            Log::debug("Swish ID: " . $swishId);
            $swishIdArr = explode( "/", (string) $swishId);
            $swishId = $swishIdArr[count($swishIdArr) - 1];
            $payment->ref_id = $swishRef;
            $payment->swish_id = $swishId;
            $payment->save();

            $result = array();
            $result["refId"] = basename($swishRef);
            $result["paymentId"] = $payment->id;

            return response($result);
        } else {
            $payment->status = "FAILED";
            $payment->error = $res->getStatusCode();
            $payment->error_message = $res->getBody()->getContents();
            $payment->save();

            return response(Helper::jsonError("Error"), 400);
        }
    }

    /**
     * @SWG\Post(
     *     path="/payment/swish/callback",
     *     summary="Choose swish pay",
     *     @SWG\Parameter(
     *         in="path",
     *         name="type",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         in="body",
     *         name="body",
     *         required=true,
     *         @SWG\Schema(
     *             @SWG\Property(property="status", type="string"),
     *             @SWG\Property(property="payeePaymentReference", type="string"),
     *             @SWG\Property(property="message", type="string"),
     *             @SWG\Property(property="errorMessage", type="string")
     *         )
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="OK"
     *     )
     * )
     */
    public function swishCallback(Request $request)
    {
        Log::debug("SWISH CALLBACK: " . json_encode($request->json()->all()));
        $payment = Payment::where("id", "=", $request->json("payeePaymentReference"))->first();

        if ($payment) {
            $job = Job::where('id', '=', $payment->job_id)->first();
            if (Helper::getStatusForSwish($request->json("status")) == "PAID") {
                $payment->status = Helper::getStatusForSwish($request->json("status"));
                $payment->save();
            } else {
                $payment->status = "FAILED";
                $payment->error = $request->json("message");
                $payment->error_message = $request->json("errorMessage");
                $payment->save();
            }

            if (Helper::getStatusForSwish($request->json("status")) == "PAID") {
                if ($payment->type == PayConst::payBoostSpeedy) {
                    $job->is_speedy = 1;
                    $job->is_boost = 1;
                    $job->save();
                } else if ($payment->type == PayConst::payBoost) {
                    $job->is_boost = 1;
                    $job->save();
                } else if ($payment->type == PayConst::paySpeedy) {
                    $job->is_speedy = 1;
                    $job->save();
                } else if ($payment->type == PayConst::payChoose) {
                    $applicant = Applicant::where("job_id", "=", $payment->job->id)->where("user_id", "=", $payment->doer_id)->first();

                    if (!$applicant)
                        Log::debug("SWISH COMPLETE: Applican not found!");

                    $applicant->choosed_at = Carbon::now()->toDateTimeString();
                    $applicant->save();
                } else if ($payment->type == PayConst::payList) {
                    $job->is_listed = 1;
                    $job->save();
                } else if ($payment->type == PayConst::payUnderbidder){
                    $job->is_underbidder_listed = 1;
                    $job->save();
                }
            }
        }

        if ($payment->status == "PAID") {
            CreateAndSendInvoice::dispatch($payment->job_id, $payment->type, PayConst::paymentProcessorSwish);
        }

        return response("OK");
    }

    /**
     * @SWG\Get(
     *     path="/payment/swish/complete/{orderId}",
     *     summary="Choose swish pay",
     *     security={{"bearer_token":{}}},
     *     @SWG\Parameter(
     *         in="path",
     *         name="orderId",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="OK"
     *     )
     * )
     */
    public function swishComplete($orderId)
    {
        $client = new Client();

        $payment = Payment::where("ref_id", $orderId)->first();

        try {
            $res = $client->request("get", "https://cpc.getswish.net/swish-cpcapi/api/v1/paymentrequests/$payment->swish_id", [
                "cert" => [storage_path("app/swish2021/swish_certificate_202103081611.pem"), "jobdeal2306"],
                'ssl_key' => [storage_path("app/swish2021/swish_certificate_202103081611.key"), 'jobdeal2306'],
            ]);
        } catch (\Exception $e) {
            return response(Helper::jsonError($e->getMessage()), 500);
        }

        $result = json_decode((string)$res->getBody());

        Log::debug("Swish Complete Response: " . json_encode($result));

        $payment = Payment::where("id", "=", $result->payeePaymentReference)->first();

        if ($payment) {
            $job = Job::where('id', '=', $payment->job_id)->first();
            if (Helper::getStatusForSwish($result->status) == "PAID") {
                $payment->status = Helper::getStatusForSwish($result->status);
                $payment->save();
            } else if (Helper::getStatusForSwish($result->status) == "PENDING") {
                $payment->status = Helper::getStatusForSwish($result->status);
                $payment->save();
            } else {
                $payment->status = "FAILED";
                $payment->error = $result->message;
                $payment->error_message = $result->errorMessage;
                $payment->save();
            }

            if($payment->status == "PAID") {
                if ($payment->type == PayConst::payBoostSpeedy) {
                    $job->is_speedy = 1;
                    $job->is_boost = 1;
                    $job->save();
                } else if ($payment->type == PayConst::payBoost) {
                    $job->is_boost = 1;
                    $job->save();
                } else if ($payment->type == PayConst::paySpeedy) {
                    $job->is_speedy = 1;
                    $job->save();
                } else if ($payment->type == PayConst::payChoose) {
                    $applicant = Applicant::where("job_id", "=", $payment->job->id)->where("user_id", "=", $payment->doer_id)->first();

                    if (!$applicant)
                        Log::debug("SWISH COMPLETE: Applican not found!");

                    $applicant->choosed_at = Carbon::now()->toDateTimeString();
                    $applicant->save();
                } else if ($payment->type == PayConst::payList) {
                    $job->is_listed = 1;
                    $job->save();
                }  else if ($payment->type == PayConst::payUnderbidder) {
                    $job->is_underbidder_listed = 1;
                    $job->save();
                }
            }
        }

        sleep(3);

        return response(new PaymentResource($payment));
    }

    /*---------------------------------KLARNA--------------------------------------*/

    //method for pay boost, speedy, boosted speedy, list doers
    /**
     * @SWG\Post(
     *     path="/payment/klarna/pay/job/{type}",
     *     summary="Pay job with Klarna",
     *     security={{"bearer_token":{}}},
     *     @SWG\Parameter(
     *         in="path",
     *         name="type",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         in="body",
     *         name="body",
     *         required=true,
     *         @SWG\Schema(
     *             @SWG\Property(property="id", type="integer")
     *         )
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="OK"
     *     )
     * )
     */
    public function klarnaJobPay(Request $request, $type)
    {
        $payment = new Payment();
        $payment->job_id = $request->json("id");
        $payment->user_id = Auth::user()->id;
        $payment->currency = Helper::getPrices()["currency"];
        $payment->status = "PENDING";
        $payment->provider = "Klarna";
        $payment->ref_id = "";

        $job = Job::where("id", "=", $request->json("id"))->first();

        if (!$job)
            return response(Helper::jsonError("Job not found!"), 404);

        if ($type == PayConst::payBoost) {
            $payment->amount = Helper::getPrices()["boost"] * 100;//KLARNA PRICE FACTOR
            $payment->description = "Boost Job";
            $payment->type = PayConst::payBoost;
        } else if ($type == PayConst::paySpeedy) {
            $payment->amount = Helper::getPrices()["speedy"] * 100;//KLARNA PRICE FACTOR
            $payment->description = "Speedy Job";
            $payment->type = PayConst::paySpeedy;
        } else if ($type == PayConst::payList) {
            $payment->amount = ($job->price * (Helper::getPrices()["list"] / 100)) * 100;//KLARNA PRICE FACTOR
            $payment->description = "List Job";
            $payment->type = PayConst::payList;
        } else if ($type == PayConst::payBoostSpeedy) {
            $payment->amount = (Helper::getPrices()["speedy"] + Helper::getPrices()["boost"]) * 100;//KLARNA PRICE FACTOR
            $payment->description = "Boosted Speedy Job";
            $payment->type = PayConst::payBoostSpeedy;
        } else if ($type == PayConst::payUnderbidder) {
            $payment->amount = ($job->price * (Helper::getPrices()["list_underbidder"] / 100)) * 100;//KLARNA PRICE FACTOR
            $payment->description = "List Underbidders";
            $payment->type = PayConst::payUnderbidder;
        }

        $payment->save();

        $client = new Client();
        try {
            $res = $client->request("post", "https://api.playground.klarna.com/checkout/v3/orders", [
                "json" => Helper::getKlarnaBody($payment),
                'headers' => [
                    'Authorization' => 'Basic ' . Helper::klarnaAuth()
                ]
            ]);
        } catch (\Exception $e) {
            $payment->status = "FAILED";
            $payment->error = "Guzzle Request";
            $payment->error_message = $e->getMessage();
            $payment->save();

            return response(Helper::jsonError($e->getMessage()), 500);
        }

        if ($res->getStatusCode() < 300) {
            $result = json_decode($res->getBody(), false);
            $payment->ref_id = $result->order_id;
            $payment->save();

            return response(new KlarnaResource($result));
        } else {
            $payment->status = "FAILED";
            $payment->error = $res->getStatusCode();
            $payment->error_message = $res->getBody()->getContents();
            $payment->save();

            return response(Helper::jsonError("Error"), 400);
        }
    }

    /**
     * @SWG\Post(
     *     path="/payment/klarna/pay/choose",
     *     summary="Choose klarna pay",
     *     security={{"bearer_token":{}}},
     *     @SWG\Parameter(
     *         in="body",
     *         name="body",
     *         required=true,
     *         @SWG\Schema(
     *             @SWG\Property(property="price", type="string"),
     *             @SWG\Property(
     *                 property="job",
     *                 type="object",
     *                 @SWG\Property(property="id", type="integer")
     *             ),
     *             @SWG\Property(
     *                 property="user",
     *                 type="object",
     *                 @SWG\Property(property="id", type="integer")
     *             )
     *         )
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="OK"
     *     )
     * )
     */
    public function klarnaChoosePay(Request $request)
    {
        $job = Job::where("id", "=", $request->json("job")["id"])->first();

        if (!$job)
            return response(Helper::jsonError("Job not found!"), 404);

        $payment = new Payment();
        $payment->job_id = $job->id;
        $payment->doer_id = $request->json("user")["id"];
        $payment->user_id = Auth::user()->id;
        $payment->amount = ($job->price * (Helper::getPrices()["choose"] / 100)) * 100;
        $payment->currency = Helper::getPrices()["currency"];
        $payment->status = "PENDING";
        $payment->provider = "Klarna";
        $payment->description = "Choose doer for your job";
        $payment->type = PayConst::payChoose;
        $payment->ref_id = "";

        if ($job->price > $request->json("price")) {//if bid price is lower then buyer set, buyer pay fee for diff in price
            $diff = ($job->price - $request->json("price")) * 100;
            $payment->amount = $payment->amount + ($diff * (Helper::getPrices()["difference"] / 100));
        }

        $payment->save();

        $client = new Client();
        try {
            $res = $client->request("post", "https://api.playground.klarna.com/checkout/v3/orders", [
                "json" => Helper::getKlarnaBody($payment),
                'headers' => [
                    'Authorization' => 'Basic ' . Helper::klarnaAuth()
                ]
            ]);
        } catch (\Exception $e) {
            $payment->status = "FAILED";
            $payment->error = "Guzzle Request";
            $payment->error_message = $e->getMessage();
            $payment->save();

            return response(Helper::jsonError($e->getMessage()), 500);
        }

        if ($res->getStatusCode() < 300) {
            $result = json_decode($res->getBody(), false);
            $payment->ref_id = $result->order_id;
            $payment->save();

            return response(new KlarnaResource($result));
        } else {
            $payment->status = "FAILED";
            $payment->error = $res->getStatusCode();
            $payment->error_message = $res->getBody()->getContents();
            $payment->save();

            return response(Helper::jsonError("Error"), 400);
        }
    }

    /**
     * @SWG\Post(
     *     path="/payment/klarna/pay/subscribe",
     *     summary="Subscribe klarna pay",
     *     security={{"bearer_token":{}}},
     *     @SWG\Response(
     *         response=200,
     *         description="OK"
     *     )
     * )
     */
    public function klarnaSubscriptionPay(Request $request)
    {
        $user = User::where("id", "=", Auth::user()->id)->first();

        if (!$user)
            return response(Helper::jsonError("User not found!"), 404);


        $payment = new Payment();
        $payment->job_id = null;
        $payment->doer_id = null;
        $payment->user_id = Auth::user()->id;
        $payment->amount = Helper::getPrices()["subscribe"] * 100;
        $payment->currency = Helper::getPrices()["currency"];
        $payment->status = "PENDING";
        $payment->provider = "Klarna";
        $payment->type = PayConst::paySubscribe;
        $payment->description = "JobDeal Premium Member Subscription";
        $payment->ref_id = "";
        $payment->save();

        $subscription = new Subscription();
        $subscription->user_id = Auth::user()->id;
        $subscription->from_date = Carbon::now()->toDateTimeString();
        $subscription->to_date = Carbon::now()->addMonth()->toDateTimeString();
        $subscription->payment_id = $payment->id;
        $subscription->is_paid = 0;
        $subscription->save();

        $client = new Client();
        try {
            $res = $client->request("post", "https://api.playground.klarna.com/checkout/v3/orders", [
                "json" => Helper::getKlarnaSubscribeBody($payment, $subscription),
                'headers' => [
                    'Authorization' => 'Basic ' . Helper::klarnaAuth()
                ]
            ]);
        } catch (\Exception $e) {
            $payment->status = "FAILED";
            $payment->error = "Guzzle Request";
            $payment->error_message = $e->getMessage();
            $payment->save();

            $wishlist = Wishlist::where("user_id", "=", $user->id)->first();
            if($wishlist){
                $wishlist->is_active = 0;
                $wishlist->save();
            }

            return response(Helper::jsonError($e->getMessage()), 500);
        }

        Log::debug((string)$res->getBody());


        if ($res->getStatusCode() < 300) {
            $result = json_decode($res->getBody(), false);
            $payment->ref_id = $result->order_id;
            $payment->save();

            $wishlist = Wishlist::where("user_id", "=", $user->id)->first();
            if($wishlist){
                $wishlist->is_active = 1;
                $wishlist->save();
            }

            try {
                $res = $client->request("get", "https://api.playground.klarna.com/checkout/v3/orders/" . $result->order_id, [
                    'headers' => [
                        'Authorization' => 'Basic ' . Helper::klarnaAuth()
                    ]
                ]);
            } catch (\Exception $e) {
                return response(Helper::jsonError($e->getMessage()), 500);
            }

            Log::debug("ORDER: " . (string) $res->getBody());

            return response(new KlarnaResource($result));
        } else {
            $payment->status = "FAILED";
            $payment->error = $res->getStatusCode();
            $payment->error_message = $res->getBody()->getContents();
            $payment->save();

            $wishlist = Wishlist::where("user_id", "=", $user->id)->first();
            if($wishlist){
                $wishlist->is_active = 0;
                $wishlist->save();
            }

            return response(Helper::jsonError("Error"), 400);
        }
    }

    /**
     * @SWG\Get(
     *     path="/payment/klarna/complete/{orderId}",
     *     summary="Choose klarna pay",
     *     security={{"bearer_token":{}}},
     *     @SWG\Parameter(
     *         in="path",
     *         name="orderId",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="OK"
     *     )
     * )
     */
    public function klarnaComplete($orderId)
    {
        $payment = Payment::where("ref_id", "=", $orderId)->first();

        if (!$payment)
            return response(Helper::jsonError("Payment doesn't exists!"), 404);

        $client = new Client();

        if($payment->type == PayConst::paySubscribe) {
            try {
                $res = $client->request("get", "https://api.playground.klarna.com/checkout/v3/orders/" . $orderId, [
                    'headers' => [
                        'Authorization' => 'Basic ' . Helper::klarnaAuth()
                    ]
                ]);

                //Log::debug("ORDER COMPLETE: " . (string) $res->getBody());
            } catch (\Exception $e) {
                Log::debug($e->getMessage());
            }

            $result = json_decode($res->getBody(), false);

            $subscription = Subscription::where("payment_id", "=", $payment->id)->first();
            $subscription->is_paid = 1;
            $subscription->save();

            $user = User::where("id", "=", $payment->user_id)->first();
            $user->klarna_token = $result->recurring_token;
            $user->save();

            //SubscriptionInvoice::dispatch($user->id)->delay(now()->addMinutes(2));
        }

        try {
            $res = $client->request("get", "https://api.playground.klarna.com/checkout/v3/orders/" . $orderId, [
                'headers' => [
                    'Authorization' => 'Basic ' . Helper::klarnaAuth()
                ]
            ]);

            $result = json_decode($res->getBody(), false);
            $payment->html_snippet = $result->html_snippet;
            //Log::debug("ORDER COMPLETE: " . (string) $res->getBody());
        } catch (\Exception $e) {
            Log::debug($e->getMessage());
        }



        try {
            $res = $client->request("get", "https://api.playground.klarna.com/ordermanagement/v1/orders/$orderId", [
                'headers' => [
                    'Authorization' => 'Basic ' . Helper::klarnaAuth()
                ]
            ]);
           // Log::debug("ORDER MENAGEMENT COMPLETE: " . (string)$res->getBody());
        } catch (\Exception $e) {
            return response(Helper::jsonError($e->getMessage()), 500);
        }

        $result = json_decode($res->getBody(), false);

        if ($result->fraud_status == "PENDING" || $result->fraud_status == "ACCEPTED") {
            if ($result->status == "AUTHORIZED" || $result->status == 'PART_CAPTURED' || $result->status == 'CAPTURED') { //TODO PROVERITI DA LI JE PART CAPTURE OK
                $payment->status = "PAID";
                $payment->save();

                if ($payment->type == PayConst::payBoostSpeedy) {
                    $payment->job->is_speedy = 1;
                    $payment->job->is_boost = 1;
                    $payment->job->save();
                } else if ($payment->type == PayConst::payBoost) {
                    $payment->job->is_boost = 1;
                    $payment->job->save();
                } else if ($payment->type == PayConst::paySpeedy) {
                    $payment->job->is_speedy = 1;
                    $payment->job->save();
                } else if ($payment->type == PayConst::payChoose) {

                    //check if invoice shall be sent
                    CheckForInvoice::dispatch($payment->job->id);

                    $applicant = Applicant::where("job_id", "=", $payment->job->id)->where("user_id", "=", $payment->doer_id)->first();

                    if (!$applicant)
                        Log::debug("KLARNA COMPLETE: Applican not found!");

                    $applicant->choosed_at = Carbon::now()->toDateTimeString();
                    $applicant->save();
                } else if ($payment->type == PayConst::payList) {
                    $payment->job->is_listed = 1;
                    $payment->job->save();
                    $payment->save();
                } else if ($payment->type == PayConst::paySubscribe) {

                } else if ($payment->type == PayConst::payUnderbidder) {
                    $payment->job->is_underbidder_listed = 1;
                    $payment->job->save();
                    $payment->save();
                }

            } else {
                $payment->error = "FAILED";
                $payment->status = "FAILED";
                $payment->error_message = "Status is $result->status!";
                $payment->save();
            }
        } else {
            $payment->error = "FAILED";
            $payment->error_message = "Fraud status is REJECTED!";
            $payment->save();
        }



        if ($payment->status == "PAID") {
            CreateAndSendInvoice::dispatch($payment->job_id, $payment->type, PayConst::paymentProcessorKlarna);
        }

        return response(new PaymentResource($payment));

    }

    /**
     * @SWG\Post(
     *     path="/payment/klarna/subscribe/cancel",
     *     summary="Cancel klarna subscription",
     *     security={{"bearer_token":{}}},
     *     @SWG\Response(
     *         response=200,
     *         description="OK"
     *     )
     * )
     */
    public function cancelSubscription(){
        $user = User::where("id", "=", Auth::user()->id)->first();

        $subscription = Subscription::where("user_id", "=", $user->id)->where("to_date", ">=", Carbon::now()->toDateTimeString())->where("is_paid", "=", 1)->where("is_canceled", "=", 0)->orderBy("to_date", "DESC")->first();

        if(!$subscription){
            return response(new UserResource($user));
        }

        $subscription->is_canceled = 1;
        $subscription->save();

        $user->klarna_token = null;
        $user->save();

        $user = User::where("id", "=", $user->id)->first();

        return response(new UserResource($user));
    }

    //KLARNA EVENTS
    public function klarnaCheckoutEvent(Request $request, $order_id)
    {
        Log::debug("Klarna Checkout Event: " . $order_id);
    }

    /**
     * @SWG\Post(
     *     path="/payment/klarna/push/{order_id}",
     *     summary="Push klarna event",
     *     security={{"bearer_token":{}}},
     *     @SWG\Parameter(
     *         in="path",
     *         name="order_id",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="OK"
     *     )
     * )
     */
    public function klarnaPushEvent(Request $request, $order_id)
    {
        Log::debug("Klarna Push Event: " . $order_id);
        $payment = Payment::where("ref_id", "=", $order_id)->first();
        $client = new Client();

        try {
            $res = $client->request("get", "https://api.playground.klarna.com/ordermanagement/v1/orders/$order_id", [
                'headers' => [
                    'Authorization' => 'Basic ' . Helper::klarnaAuth()
                ]
            ]);
        } catch (\Exception $e) {
            Log::debug("CAN'T GET ORDER IN PUSH");
        }

        Log::debug("PUSH GET ORDER: " . (string) $res->getBody());


        try {
            $res = $client->request("post", "https://api.playground.klarna.com/ordermanagement/v1/orders/$order_id/acknowledge", [
                'headers' => [
                    'Authorization' => 'Basic ' . Helper::klarnaAuth()
                ]
            ]);
        } catch (\Exception $e) {
            Log::debug("Acknowledge ERROR!");
        }

        Log::debug("Acknowledge OK!");

        //check if succesffuly


        //catch payment

        $body = [
            'captured_amount' => $payment->amount
        ];

        try {
            $res = $client->request("post", "https://api.playground.klarna.com/ordermanagement/v1/orders/$order_id/captures", [
                'headers' => [
                    'Authorization' => 'Basic ' . Helper::klarnaAuth()
                ],
                'json' => $body
            ]);
        } catch (\Exception $e) {
            Log::debug("CAPTURE PAYMENT ERROR");
        }

        Log::debug("CAPTURE PAYMENT OK");



        return response("OK", 200);
    }

    /**
     * @SWG\Get(
     *     path="/payment/klarna/confirmation/{order_id}",
     *     summary="Cancel klarna event",
     *     security={{"bearer_token":{}}},
     *     @SWG\Parameter(
     *         in="path",
     *         name="order_id",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="OK"
     *     )
     * )
     */
    public function klarnaConfirmationEvent($order_id)
    {
        Log::debug("Klarna Confirmation Event: " . $order_id);

        $client = new Client();
        try {
            $res = $client->request("get", "https://api.playground.klarna.com/checkout/v3/orders/" . $order_id, [
                'headers' => [
                    'Authorization' => 'Basic ' . Helper::klarnaAuth()
                ]
            ]);

            $result = json_decode($res->getBody(), false);

            Log::debug(\GuzzleHttp\json_encode($result));
        } catch (\Exception $e) {
            Log::debug($e->getMessage());
        }


        return view("welcome");
    }

    public function klarnaValidateEvent(Request $request)
    {
        Log::debug("klarnaValidateEvent!");

        return response("ok", 200);
    }

    //KLARNA PAYMENTS FOR SUBSCRIPTION - OLD
    public function payKlarna() //pay with klarna payments with token
    {
        $merchantId = 'PK07348_1f992e19becc';
        $sharedSecret = 'vvJ7nGA6BjRlELAW';

        $apiEndpoint = Klarna\ConnectorInterface::EU_TEST_BASE_URL;

        $connector = Klarna\Connector::create(
            $merchantId,
            $sharedSecret,
            $apiEndpoint
        );

        $order = [
            "purchase_country" => "se",
            "purchase_currency" => "sek",
            "locale" => "sk-se",
            "order_amount" => 50,
            "order_tax_amount" => 0,
            "order_lines" => [
                [
                    "type" => "digital",
                    "reference" => "123050",
                    "name" => "Tomatoes",
                    "quantity" => 1,
                    "unit_price" => 50,
                    "tax_rate" => 0,
                    "total_amount" => 50,
                    "total_tax_amount" => 0
                ]
            ]
        ];

        $session = new KlarnaPayments\Sessions($connector);
        $session->create($order);
        // Store session id
        $sessionId = $session->getId();
        $client_token = $session["client_token"];
        $payment_methods = $session["payment_method_categories"];


        return view("payment.klarna", ["token" => $client_token, "session" => $sessionId]);
    }

    public function finishKlarnaPayment($token)
    {
        $merchantId = 'PK07348_1f992e19becc';
        $sharedSecret = 'vvJ7nGA6BjRlELAW';

        $apiEndpoint = Klarna\ConnectorInterface::EU_TEST_BASE_URL;

        $connector = Klarna\Connector::create(
            $merchantId,
            $sharedSecret,
            $apiEndpoint
        );

        $orderData = [
            "purchase_country" => "se",
            "purchase_currency" => "sek",
            "locale" => "sk-se",
            "order_amount" => 50,
            "order_tax_amount" => 0,
            "auto_capture" => true,
            "order_lines" => [
                [
                    "type" => "digital",
                    "reference" => "123050",
                    "name" => "Tomatoes",
                    "quantity" => 1,
                    "unit_price" => 50,
                    "tax_rate" => 0,
                    "total_amount" => 50,
                    "total_tax_amount" => 0
                ]
            ]
        ];

        $payOrder = new KlarnaPayments\Orders($connector, $token);
        $data = $payOrder->create($orderData);
        var_dump($data["order_id"]);


        /*$order = new KlarnaManagement\Order($connector, $data["order_id"]);
        $order->createCapture([
            "captured_amount" => 50,
            "description" => "Naplaceno",
            "order_lines" => [
                [
                    "type" => "digital",
                    "reference" => "123050",
                    "name" => "Tomatoes",
                    "quantity" => 1,
                    "unit_price" => 50,
                    "tax_rate" => 0,
                    "total_amount" => 50,
                    "total_tax_amount" => 0
                ]
            ]
        ]);*/

        var_dump("OK");
    }

    /**
     * @SWG\Post(
     *     path="/price/calculate",
     *     summary="Calculate price",
     *     @SWG\Parameter(
     *         in="body",
     *         name="body",
     *         required=true,
     *         @SWG\Schema(
     *             @SWG\Property(property="type", type="string"),
     *             @SWG\Property(
     *                 property="applicant",
     *                 type="object",
     *                 @SWG\Property(property="id", type="integer")
     *             ),
     *             @SWG\Property(
     *                 property="job",
     *                 type="object",
     *                 @SWG\Property(property="id", type="integer"),
     *                 @SWG\Property(property="price", type="string")
     *             )
     *         )
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="OK"
     *     )
     * )
     */
    public function calculatePrice(Request $request) {

        $type = $request->json('type');
        $applicant = $request->json('applicant');
        $job = $request->json('job');
        $prices = Helper::getPrices();
        $swishFee = $prices['swish_fee'];

        $currency = $prices['currency'];
        $price = 0;
        $description = "";

        $prices = Helper::getPrices();

        if ($type == PayConst::payList) {

            $price = round($job['price'] * ($prices['list']  / 100) + $swishFee, 2);
            $description = __("lang.listPaymentDescription", ["swishFee" => $swishFee], Auth::user()->locale);
        } else if ($type == PayConst::payBoost) {
            $price = round($prices['boost'] + $swishFee, 2);
            $description = __("lang.boostPaymentDescription", ["swishFee" => $swishFee], Auth::user()->locale);
        } else if ($type == PayConst::paySpeedy) {
            $price = round($prices['speedy'] + $swishFee, 2);
            $description = __("lang.speedyPaymentDescription", ["swishFee" => $swishFee], Auth::user()->locale);
        } else if ($type == PayConst::payBoostSpeedy) {
            $price = round($prices['speedy'] + $prices['boost'] + $swishFee, 2);
            $description = __("lang.speedyBoostPaymentDescription", ["swishFee" => $swishFee], Auth::user()->locale);
        }
        else if ($type == PayConst::payUnderbidder) {
            $price = round($job['price'] * ($prices['list_underbidder']  / 100) + $swishFee, 2);
            $description = __("lang.listUnderbidderPaymentDescription", ["swishFee" => $swishFee], Auth::user()->locale);
        }
        else if ($type == PayConst::payChoose) {
            $applicantModel = Applicant::where('id', '=', $applicant['id'])->first();

            if (!$applicantModel) {
                return response(Helper::jsonError("Applicant not found"), 404);
            }


            if ($job['price'] <= $applicantModel->price) {
                $diff = 0;
                if ($prices['choose'] > 0) {
                    $description = __("lang.choosePaymentDescription", ["swishFee" => $swishFee, "choosePerc" => $prices['choose'], "diffPerc" => $prices['difference']], Auth::user()->locale);
                } else {
                    $description = __("lang.choosePaymentDescriptionNoChoose", ["swishFee" => $swishFee, "choosePerc" => $prices['choose'], "diffPerc" => $prices['difference']], Auth::user()->locale);
                }

            } else {
                $diff = ($job['price'] - $applicantModel->price) * ($prices['difference'] / 100);
                if ($prices['choose'] > 0) {
                    $description = __("lang.choosePaymentDescriptionDiff", ["swishFee" => $swishFee, "choosePerc" => $prices['choose'], "diffPerc" => $prices['difference']], Auth::user()->locale);
                } else {
                    $description = __("lang.choosePaymentDescriptionDiffNoChoose", ["swishFee" => $swishFee, "choosePerc" => $prices['choose'], "diffPerc" => $prices['difference']], Auth::user()->locale);
                }

            }
            $price = round($job['price'] * ($prices['choose'] / 100) + $diff + $swishFee, 2);
        }


        $obj = [];
        $obj['price'] = $price;
        $obj['currency'] = $currency;
        $obj['descriptionText'] = $description;
        $obj['swishFee'] = $swishFee;
        $obj['paymentOptions'] = PaymentMethod::all();

        return response($obj);



    }
}
