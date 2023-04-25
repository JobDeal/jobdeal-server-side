<?php

use App\Job;
use Faker\Generator as Faker;

$factory->define(Job::class, function(Faker $faker) {
    $interval = new \DateInterval("P3D");
//    $interval->d = 3;
    $now = new \DateTime();
    $date = $now->add($interval);
    return [
        "id" => 1,
        "user_id" => 1,
        "name" => "Job ",
        "description" => "Job %d description",
        "price" => 10.5,
        "address" => "Address",
        "category_id" => 1,
        "location_string" => "17.944844663143 59.3629051017",
        "country" => "SE",
        "expire_at" => $date,
        "created_at" => $now
    ];
});
