<?php

namespace Tests\Feature\Seed\Notification\Doer;

use App\Job;
use Illuminate\Database\Seeder;

class JobsTableSeeder extends Seeder
{
    public function run()
    {
        $created = new \DateTime("2022-10-26 16:45:00");
        $interval = new \DateInterval("P3D");
        $now = new \DateTime();
        $notExpired = $now->add($interval);

        for ($i = 1; $i < 3; $i++) {
            factory(Job::class)->create([
                "id" => $i,
                "user_id" => 1,
                "name" => "Job ".$i,
                "description" => sprintf("Job %d description", $i),
                "price" => 10.5,
                "address" => "Address ".$i,
                "expire_at" => $notExpired,
                "created_at" => $created,
            ]);
        }

        // Expired job
        $i = 3;
        factory(Job::class)->create([
            "id" => $i,
            "user_id" => 1,
            "name" => "Job ".$i,
            "description" => sprintf("Job %d description", $i),
            "price" => 10.5,
            "address" => "Address ".$i,
            "expire_at" => $created,
            "created_at" => $created,
        ]);

        $i = 4;
        factory(Job::class)->create([
            "id" => $i,
            "user_id" => 1,
            "name" => "Job ".$i,
            "description" => sprintf("Job %d description", $i),
            "price" => 10.5,
            "address" => "Address ".$i,
            "expire_at" => $notExpired,
            "created_at" => $created,
        ]);

    }
}
