<?php

namespace Tests\Feature\Seed\Notification\Doer;

use App\Http\NotificationConst;
use App\Notification;
use Illuminate\Database\Seeder;

class NotificationsTableSeeder extends Seeder
{
    public function run()
    {
        foreach ($this->getNotifications() as $notification) {
            factory(Notification::class)->create($notification);
        }
    }

    private function getNotifications(): array
    {
        return [
            // Notifications for Alice
            [
                "id" => 1,
                "user_id" => 1,
                "job_id" => 1,
                "type" => NotificationConst::DoerBid,
            ],
            // Notifications for Bob
            [
                "id" => 2,
                "user_id" => 2,
                "job_id" => 1,
                "type" => NotificationConst::WishlistJob
            ],
            [
                "id" => 3,
                "user_id" => 2,
                "job_id" => 1,
                "type" => NotificationConst::BuyerAccepted
            ],
            [
                "id" => 4,
                "user_id" => 2,
                "job_id" => 2,
                "type" => NotificationConst::RateBuyer
            ],
            [
                "id" => 5,
                "user_id" => 2,
                "job_id" => 3, // Expired job
                "type" => NotificationConst::BuyerAccepted
            ],
            [
                "id" => 6,
                "user_id" => 2,
                "job_id" => 3,
                "type" => NotificationConst::RateBuyer
            ],
            [
                "id" => 7,
                "user_id" => 2,
                "job_id" => 4,
                "type" => NotificationConst::WishlistJob
            ]
        ];
    }
}
