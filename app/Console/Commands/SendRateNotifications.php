<?php

namespace App\Console\Commands;

use App\Http\JobConst;
use App\Http\NotificationConst;
use App\Http\Resources\JobLiteResource;
use App\Job;
use App\Jobs\PushNotification;
use App\Notification;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;

class SendRateNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sendRateNotifications';

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
        //get jobs that have chosed doer/s and that are expired 12 hours ago and rate message is not sent already
        $jobs = Job::where("expire_at", "<", Carbon::now()->subMinutes(1)->toDateTimeString())
            ->where("is_rate_sent", "=", 0)->get();

        foreach ($jobs as $job){
            $job->is_rate_sent = 1;
            $job->save();

            $this->info($job->user_id);

            //send to buyer
            foreach ($job->applicants as $doer) {//send notification for each doer that worked on that job
                if($doer->choosed_at == null)
                    continue;

                $notification = new Notification();
                $notification->user_id = $job->user_id;
                $notification->from_id = $doer->user->id;
                $notification->job_id = $job->id;
                $notification->type = NotificationConst::RateDoer;
                $notification->save();

                PushNotification::dispatch($job->user_id, $doer->user->id, NotificationConst::RateDoer, $notification->id, new JobLiteResource($job), __("lang.rateDoer_description", [], $job->user->locale),
                    __("lang.rateDoer_title", [], $job->user->locale), $sendNotification = true);
            }

            //send to doeres
            foreach ($job->applicants as $doer) {
                if($doer->choosed_at == null)
                    continue;

                $notification = new Notification();
                $notification->user_id = $doer->user->id;
                $notification->from_id = $job->user_id;
                $notification->job_id = $job->id;
                $notification->type = NotificationConst::RateBuyer;
                $notification->save();

                PushNotification::dispatch($doer->user->id, $job->user_id, NotificationConst::RateBuyer, $notification->id, new JobLiteResource($job), __("lang.rateBuyer_description", [], $job->user->locale),
                    __("lang.rateBuyer_title", [], $job->user->locale), $sendNotification = true);
            }
        }

        return "OK";
    }
}
