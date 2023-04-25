<?php

namespace Tests\Feature\Seed\Notification\Buyer;

use App\Http\NotificationConst;
use App\Notification;
use Illuminate\Database\Seeder;

class NotificationsTableSeeder extends Seeder
{
    public function run()
    {
        foreach ($this->notificationList() as $notification) {
            factory(Notification::class)->create($notification);
        }
    }

    private function notificationList()
    {
        return [
            // Notifications for Alice
            [
                "user_id" => 1,
                "job_id" => 1,
                "type" => NotificationConst::DoerBid
            ],
            [
                "user_id" => 1,
                "job_id" => 1,
                "type" => NotificationConst::RateDoer
            ],
            [
                "user_id" => 1,
                "job_id" => 2,
                "type" => NotificationConst::DoerBid
            ],
            [
                "user_id" => 1,
                "job_id" => 2,
                "type" => NotificationConst::RateDoer
            ],
        ];
    }
}
