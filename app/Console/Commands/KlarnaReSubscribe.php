<?php

namespace App\Console\Commands;

use App\Http\Helper;
use App\Http\NotificationConst;
use App\Http\PayConst;
use App\Http\Resources\JobLiteResource;
use App\Http\Resources\KlarnaResource;
use App\Jobs\PushNotification;
use App\Notification;
use App\Payment;
use App\Subscription;
use App\User;
use App\Wishlist;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class KlarnaReSubscribe extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'klarnaSubscribe';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        //TODO foreach subscription, for user with klarna_token, find latest payment and create order. Pay with klarna_token

        $users = User::where("klarna_token", "!=", null)->get();

        $this->info("Need to subcribe count: " . count($users));

        foreach ($users as $user) {
            $user = User::where("id", "=", $user->id)->first();

            $subscription = $user->subscriptions()->where("is_paid", "=", 1)->where("is_canceled", "=", 0)->orderBy("to_date", "DESC")->first();

            if (Carbon::parse($subscription->to_date) < Carbon::now()) {
                $this->info("Need to do subscribe! $user->klarna_token");
                $lastPayment = $subscription->payment;

                $payment = new Payment();
                $payment->job_id = $lastPayment->job_id;
                $payment->doer_id = $lastPayment->doer_id;
                $payment->user_id = $user->id;
                $payment->amount = $lastPayment->amount;
                $payment->currency = Helper::getPrices()["currency"];
                $payment->status = "PENDING";
                $payment->provider = "Klarna";
                $payment->type = PayConst::paySubscribe;
                $payment->description = "JobDeal Premium Member Subscription";
                $payment->ref_id = "";
                $payment->save();

                $subscription = new Subscription();
                $subscription->user_id = $user->id;
                $subscription->from_date = Carbon::now()->toDateTimeString();
                $subscription->to_date = Carbon::now()->addMonth()->toDateTimeString();
                $subscription->payment_id = $payment->id;
                $subscription->is_paid = 1;
                $subscription->save();

                $client = new Client();
                try {
                    $res = $client->request("post", "https://api.playground.klarna.com/customer-token/v1/tokens/" . $user->klarna_token . "/order", [
                        "json" => Helper::getKlarnaReSubscribeBody($payment, $subscription, false),
                        'headers' => [
                            'Authorization' => 'Basic ' . Helper::klarnaAuth()
                        ]
                    ]);
                } catch (\Exception $e) {
                    $payment->status = "FAILED";
                    $payment->error = "Guzzle Request";
                    $payment->error_message = $e->getMessage();
                    $payment->save();

                    $this->info("Fail to make payment!");
                    $this->info($e->getMessage());

                    $notification = new Notification();
                    $notification->user_id = $user->id;
                    $notification->from_id = -1;
                    $notification->job_id = -1;
                    $notification->type = NotificationConst::PaymentError;
                    $notification->save();

                    $wishlist = Wishlist::where("user_id", "=", $user->id)->first();
                    if($wishlist){
                        $wishlist->is_active = 0;
                        $wishlist->save();
                    }

                    PushNotification::dispatch($user->id, -1, NotificationConst::PaymentError, $notification->id, [], __("lang.paymentError_description"),
                        __("lang.paymentError_title"), $sendNotification = true);

                    return "OK";
                }

                if ($res->getStatusCode() < 300) {
                    $result = json_decode($res->getBody(), false);
                    $payment->ref_id = $result->order_id;
                    $payment->save();

                    $notification = new Notification();
                    $notification->user_id = $user->id;
                    $notification->from_id = -1;
                    $notification->job_id = -1;
                    $notification->type = NotificationConst::PaymentSuccess;
                    $notification->save();

                    //check if wishlist exists and active
                    $wishlist = Wishlist::where("user_id", "=", $user->id)->first();

                    if($wishlist){
                        $wishlist->is_active = 1;
                        $wishlist->save();
                    }

                    PushNotification::dispatch($user->id, -1, NotificationConst::PaymentSuccess, $notification->id, [], __("lang.paymentSubscription_description"),
                        __("lang.paymentSubscription_title"), $sendNotification = true);

                    $this->info("Payment saved! Everthing is OK!");
                } else {
                    $payment->status = "FAILED";
                    $payment->error = $res->getStatusCode();
                    $payment->error_message = $res->getBody()->getContents();
                    $payment->save();

                    $notification = new Notification();
                    $notification->user_id = $user->id;
                    $notification->from_id = -1;
                    $notification->job_id = -1;
                    $notification->type = NotificationConst::PaymentError;
                    $notification->save();

                    $wishlist = Wishlist::where("user_id", "=", $user->id)->first();
                    if($wishlist){
                        $wishlist->is_active = 0;
                        $wishlist->save();
                    }

                    PushNotification::dispatch($user->id, -1, NotificationConst::PaymentError, $notification->id, [], __("lang.paymentError_description"),
                        __("lang.paymentError_title"), $sendNotification = true);

                    $this->info("Payment FAILED!");
                }
            }
        }

        return "OK";
    }
}
