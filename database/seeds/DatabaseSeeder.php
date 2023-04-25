<?php

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->call([
            \Tests\Feature\Seed\Notification\Buyer\NotificationFilterSeeder::class
        ]);
    }
}
