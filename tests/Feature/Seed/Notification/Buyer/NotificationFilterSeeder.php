<?php

namespace Tests\Feature\Seed\Notification\Buyer;

use Illuminate\Database\Seeder;
use Tests\Feature\Seed\Common\MinimumSeed;

class NotificationFilterSeeder extends Seeder
{
    public function run()
    {
        $this->call([
            MinimumSeed::class,
            JobsTableSeeder::class,
            NotificationsTableSeeder::class,
        ]);
    }
}
