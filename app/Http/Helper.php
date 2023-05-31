<?php
/**
 * Created by PhpStorm.
 * User: macbook
 * Date: 8/29/18
 * Time: 7:51 PM
 */

namespace App\Http;


use App\Http\Resources\PriceResource;
use App\Job;
use App\Payment;
use App\Price;
use App\Subscription;
use App\Wishlist;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class Helper
{
    public static function jsonError($message)
    {
        $response = array();
        $response["message"] = $message;

        return json_encode($response);
    }

    public static function getCurrencyByCountry($country)
    {
        if ($country == "rs")
            return "RSD";
        else if ($country == "se")
            return "SEK";
        else
            return "";
    }

    public static function getInfo($user)
    { //get user info like max-min prices etc
        $minPrice = Job::where("is_active", "=", 1)->where("expire_at", ">=", Carbon::now()->toDateTimeString())->where("country", "=", $user->country)->min("price");
        $maxPrice = Job::where("is_active", "=", 1)->where("expire_at", ">=", Carbon::now()->toDateTimeString())->where("country", "=", $user->country)->max("price");

        $res = array();
        $res["minPrice"] = 0;
        $res["maxPrice"] = 1001;
        $res["minAndroidVersion"] = 1;
        $res["minIosVersion"] = 1;
        $res["currency"] = Helper::getCurrencyByCountry($user->country);

        return $res;
    }

    public static function getHintForBankId($hint)
    {
        if ($hint == 'outstandingTransaction') {
            return __("lang.rfa8");
        } else if ($hint == 'certificateErr') {
            return __('lang.rfa16');
        } else if ($hint == 'userCancel') {
            return __('lang.rfa6');
        } else if ($hint == 'cancelled') {
            return __('lang.rfa3');
        } else if ($hint == 'startFailed') {
            return __('lang.rfa17');
        } else {
            return __('lang.error');
        }
    }

    public static function getBodyForNotificationType($type){
        if($type == NotificationConst::DoerBid)
            return __("lang.doerBid_description", [], Auth::user()->locale);
        else if ($type == NotificationConst::BuyerAccepted)
            return __("lang.doerGotJob_description", [], Auth::user()->locale);
        else if ($type == NotificationConst::RateDoer)
            return __("lang.rateDoer_description", [], Auth::user()->locale);
        else if ($type == NotificationConst::RateBuyer)
            return __("lang.rateBuyer_description", [], Auth::user()->locale);
        else if ($type == NotificationConst::WishlistJob)
            return __("lang.wishlistJob_description", [], Auth::user()->locale);
        else if ($type == NotificationConst::PaymentSuccess)
            return __("lang.paymentSubscription_description", [], Auth::user()->locale);
        else if ($type == NotificationConst::PaymentError)
            return __("lang.paymentError_description", [], Auth::user()->locale);

        return null;
    }

    public static function getTitleForNotificationType($type){
        if($type == NotificationConst::DoerBid)
            return __("lang.doerBid_title", [], Auth::user()->locale);
        else if ($type == NotificationConst::BuyerAccepted)
            return __("lang.doerGotJob_title", [], Auth::user()->locale);
        else if ($type == NotificationConst::RateDoer)
            return __("lang.rateDoer_title", [], Auth::user()->locale);
        else if ($type == NotificationConst::RateBuyer)
            return __("lang.rateBuyer_title", [], Auth::user()->locale);
        else if ($type == NotificationConst::WishlistJob)
            return __("lang.wishlistJob_title", [], Auth::user()->locale);
        else if ($type == NotificationConst::PaymentSuccess)
            return __("lang.paymentSubscription_title", [], Auth::user()->locale);
        else if ($type == NotificationConst::PaymentError)
            return __("lang.paymentError_title", [], Auth::user()->locale);

        return null;
    }

    public static function klarnaAuth()
    {
        $merchantId = 'PK07348_1f992e19becc';
        $sharedSecret = 'vvJ7nGA6BjRlELAW';

        $authorization = base64_encode($merchantId . ":" . $sharedSecret);

        return $authorization;
    }

    public static function getPrices()
    {
        $price = Price::where("from_date", "<=", Carbon::now()->toDateTimeString())->orderBy("from_date", "DESC")->first();

        return new PriceResource($price);
    }


    public static function getSwishFee()
    {
        return 3;

    }

    public static function getStatusForSwish($status)
    {
        if ($status == "PAID")
            return "PAID";
        else if ($status == "DEBITED")
            return "PAID";
        else if ($status == "ERROR")
            return "FAILED";
        else
            return "PENDING";
    }

    public static function getKlarnaBody(Payment $payment)
    {
        $paymentAmount = $payment->amount;

        if ($payment->type == PayConst::payBoostSpeedy) {
            $body = [
                "purchase_country" => "se",
                "purchase_currency" => "sek",
                "locale" => "sk-se",
                "order_amount" => $paymentAmount,
                "order_tax_amount" => 0,
                "merchant_reference1" => $payment->id,
                "auto_capture" => false,
                "order_lines" => [
                    [
                        "type" => "digital",
                        "reference" => $payment->id . " boost",
                        "name" => "Boost Job",
                        "quantity" => 1,
                        "unit_price" => Helper::getPrices()["boost"] * 100,
                        "tax_rate" => 0,
                        "total_amount" => Helper::getPrices()["boost"] * 100,
                        "total_tax_amount" => 0
                    ],
                    [
                        "type" => "digital",
                        "reference" => $payment->id . " speedy",
                        "name" => "Speedy Job",
                        "quantity" => 1,
                        "unit_price" => Helper::getPrices()["speedy"] * 100,
                        "tax_rate" => 0,
                        "total_amount" => Helper::getPrices()["speedy"] * 100,
                        "total_tax_amount" => 0
                    ]
                ],
                "merchant_urls" => [
                    "terms" => config("conf.url") . "/terms",
                    "checkout" => config("conf.url") . "/api/payment/klarna/checkout/{checkout.order.id}",
                    "confirmation" => config("conf.url") . "/api/payment/klarna/confirmation/{checkout.order.id}",
                    "push" => config("conf.url") . "/api/payment/klarna/push/{checkout.order.id}"
                ]
            ];

            return $body;
        } else {
            $body = [
                "purchase_country" => "se",
                "purchase_currency" => "sek",
                "locale" => "sk-se",
                "order_amount" => $paymentAmount,
                "order_tax_amount" => 0,
                "merchant_reference1" => $payment->id,
                "auto_capture" => false,
                "order_lines" => [
                    [
                        "type" => "digital",
                        "reference" => $payment->id,
                        "name" => $payment->description,
                        "quantity" => 1,
                        "unit_price" => $paymentAmount,
                        "tax_rate" => 0,
                        "total_amount" => $paymentAmount,
                        "total_tax_amount" => 0
                    ]
                ],
                "merchant_urls" => [
                    "terms" => config("conf.url") . "/terms",
                    "checkout" => config("conf.url") . "/api/payment/klarna/checkout/{checkout.order.id}",
                    "confirmation" => config("conf.url") . "/api/payment/klarna/confirmation/{checkout.order.id}",
                    "push" => config("conf.url") . "/api/payment/klarna/push/{checkout.order.id}"
                ]
            ];

            return $body;
        }
    }

    public static function getKlarnaSubscribeBody(Payment $payment, Subscription $subscription, $autoCapture = false)
    {
        $paymentAmount = $payment->amount;

        $attachment = [
            "subscription" => [
                [
                    "subscription_name" => "Premium member monthly subscription",
                    "start_time" => Carbon::parse($subscription->from_date)->format("Y-m-d\TH:i:s\Z"),
                    "end_time" => Carbon::parse($subscription->to_time)->format("Y-m-d\TH:i:s\Z"),
                    "auto_renewal_of_subscription" => true,
                    "affiliate_name" => ""
                ]
            ]
        ];

        $body = [
            "purchase_country" => "se",
            "purchase_currency" => "sek",
            "locale" => "sk-se",
            "order_amount" => $paymentAmount,
            "order_tax_amount" => 0,
            "merchant_reference1" => $payment->id,
            "auto_capture" => $autoCapture,
            "recurring" => true,
            "recurring_description" => "Premium member monthly subscription",
            "order_lines" => [
                [
                    "type" => "digital",
                    "reference" => $payment->id,
                    "name" => $payment->description,
                    "quantity" => 1,
                    "unit_price" => $paymentAmount,
                    "tax_rate" => 0,
                    "total_amount" => $paymentAmount,
                    "total_tax_amount" => 0
                ]
            ],
            "attachment" => [
                "content_type" => "application/vnd.klarna.internal.emd-v2+json",
                "body" => json_encode($attachment)
            ],
            "merchant_urls" => [
                "terms" => config("conf.url") . "/terms",
                "checkout" => config("conf.url") . "/api/payment/klarna/checkout/{checkout.order.id}",
                "confirmation" => config("conf.url") . "/api/payment/klarna/confirmation/{checkout.order.id}",
                "push" => config("conf.url") . "/api/payment/klarna/push/{checkout.order.id}"
            ]
        ];

        return $body;
    }

    public static function getKlarnaReSubscribeBody(Payment $payment, Subscription $subscription, $autoCapture = false)
    {
        $paymentAmount = $payment->amount;

        $attachment = [
            "subscription" => [
                [
                    "subscription_name" => "Premium member monthly subscription",
                    "start_time" => Carbon::parse($subscription->from_date)->format("Y-m-d\TH:i:s\Z"),
                    "end_time" => Carbon::parse($subscription->to_time)->format("Y-m-d\TH:i:s\Z"),
                    "auto_renewal_of_subscription" => true,
                    "affiliate_name" => ""
                ]
            ]
        ];

        $body = [
            "purchase_country" => "se",
            "purchase_currency" => "sek",
            "locale" => "sk-se",
            "order_amount" => $paymentAmount,
            "order_tax_amount" => 0,
            "merchant_reference1" => $payment->id,
            "auto_capture" => $autoCapture,
            "attachment" => [
                "content_type" => "application/vnd.klarna.internal.emd-v2+json",
                "body" => json_encode($attachment)
            ],
            "customer_token_order_merchant_urls" => [
                "confirmation" => config("conf.url") . "/api/payment/klarna/confirmation/",
                "push" => config("conf.url") . "/api/payment/klarna/push/"
            ],
            "order_lines" => [
                [
                    "type" => "digital",
                    "reference" => $payment->id,
                    "name" => $payment->description,
                    "quantity" => 1,
                    "unit_price" => $paymentAmount,
                    "tax_rate" => 0,
                    "total_amount" => $paymentAmount,
                    "total_tax_amount" => 0
                ]
            ]
        ];

        return $body;
    }

    public static function getDistance($lat1, $lon1, $lat2, $lon2)
    {

        $theta = $lon1 - $lon2;
        $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
        $dist = acos($dist);
        $dist = rad2deg($dist);
        $miles = $dist * 60 * 1.1515;

        return ($miles * 1.609344 * 1000);
    }

    public static function getPaymentType($type, $locale) {
        if ($type == PayConst::payList) {
            return __("lang.listOfDoers", [], $locale);
        } else if ($type == PayConst::payBoost) {
            return __("lang.boostJob", [], $locale);
        } else if ($type == PayConst::paySpeedy) {
            return __("lang.speedyJob", [], $locale);
        } else if ($type == PayConst::payBoostSpeedy) {
            return __("lang.boostSpeedyJob", [], $locale);
        } else if ($type == PayConst::payChoose) {
            return __("lang.chooseDoer", [], $locale);
        } else if ($type == PayConst::payUnderbidder) {
            return __("lang.payUnderbidder", [], $locale);
        }
    }

    public static function getWishList() {
        $response = [];
        $wishlist = Wishlist::where("user_id", "=", \Auth::user()->id)->first();

        if ($wishlist) {

            $response["currentLocation"]["lat"] = (double)$wishlist->location->latitude;
            $response["currentLocation"]["lng"] = (double)$wishlist->location->longitude;
            $response["filter"]["fromPrice"] = (float)$wishlist->from_price;
            $response["filter"]["toPrice"] = (float)$wishlist->to_price;
            $response["filter"]["fromDistance"] = (float)$wishlist->from_distance;
            $response["filter"]["toDistance"] = (float)$wishlist->to_distance;
            $response["filter"]["categories"] = $wishlist->categories;
        } else {
            $response = (object) $response;
        }

        return $response;
    }
}
