<?php

namespace App\Jobs;

use App\Device;
use App\Http\NotificationConst;
use App\Http\Resources\JobPushResource;
use App\Job;
use App\Notification;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;
use LaravelFCM\Facades\FCM;
use LaravelFCM\Message\OptionsBuilder;
use LaravelFCM\Message\OptionsPriorities;
use LaravelFCM\Message\PayloadDataBuilder;
use LaravelFCM\Message\PayloadNotificationBuilder;

class PushNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $userId;
    protected $action;
    protected $value;
    protected $senderId;
    protected $desc;
    protected $title;
    protected $jobDeal;
    protected $sendNotification;

    public function __construct($userId, $senderId = -1, $action, $value, $jobDeal = [], $desc, $title, $sendNotification = true)
    {
        $this->action = $action;
        $this->userId = $userId;
        $this->senderId = $senderId;
        $this->value = $value;
        $this->desc = $desc;
        $this->title = $title;
        $this->jobDeal = $jobDeal;
        $this->sendNotification = $sendNotification;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $optionBuiler = new OptionsBuilder();
        $optionBuiler->setTimeToLive(60 * 60 * 24);
        $optionBuiler->setContentAvailable(true);
        $optionBuiler->setPriority(OptionsPriorities::high);

        $badge = Notification::where('user_id', '=', $this->userId)->where('shown', '=', 0)->count();

        $payload = array();


        $payload['senderId'] = (string)$this->senderId;
        $payload['action'] = (string)$this->action;
        $payload['value'] = (string)$this->value;
        $payload['title'] = (string)$this->title;
        $payload['badge'] = (string)$badge;
        $payload['body'] = str_limit((string)$this->desc, 300);
        $payload['job'] = $this->jobDeal;


        $notificationBuilder = new PayloadNotificationBuilder(null);
        $notificationBuilder->setBody($this->desc);
        $notificationBuilder->setChannelId(NotificationConst::GENERAL_CHANNEL_ID);
        $notificationBuilder->setTitle($this->title);
        $notificationBuilder->setBadge($badge);
        $notification = $notificationBuilder->build();

        $notificationSilentBuilder = new PayloadNotificationBuilder(null);
        $notificationSilentBuilder->setSound(0);
        $notificationSilent = $notificationSilentBuilder->build();

        $dataBuilder = new PayloadDataBuilder();
        $dataBuilder->addData($payload);

        $option = $optionBuiler->build();
        $data = $dataBuilder->build();


        $device = Device::where('user_id', '=', $this->userId)->first();

        if (!$device || $device->token == null)
            return;

        if ($device->type == 2) {//android
            $downstreamResponse = FCM::sendTo($device->token, $option, null, $data);
        } else {
            $downstreamResponse = FCM::sendTo($device->token, $option, $notification, $data);
        }

        //delete tokens
        if (count($downstreamResponse->tokensToDelete()) > 0)
            Device::whereIn('token', $downstreamResponse->tokensToDelete())->delete();

    }
}
