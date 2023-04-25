<?php

namespace Tests\Feature\Seed\Notification\Buyer;

use App\Job;
use Illuminate\Database\Seeder;

class JobsTableSeeder extends Seeder
{
    public function run()
    {
        $created = new \DateTime("2022-10-26 16:45:00");
        $interval = new \DateInterval("P3D");
        $now = new \DateTimeImmutable();
        $notExpired = $now->add($interval);

        factory(Job::class)->create([
            "id" => 1,
            "user_id" => 1,
            "name" => "Job 1",
            "description" => "Job 1",
            "price" => 10.5,
            "address" => "Address",
            "expire_at" => $notExpired,
            "created_at" => $created,
        ]);

        factory(Job::class)->create([
            "id" => 2,
            "user_id" => 2,
            "name" => "Job 2",
            "description" => "Job 2",
            "price" => 10.5,
            "address" => "Address",
            "expire_at" => $now,
            "created_at" => $created,
        ]);
    }
}
