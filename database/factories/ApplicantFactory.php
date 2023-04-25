<?php
use Faker\Generator as Faker;

$factory->define(\App\Applicant::class, function(Faker $faker) {
    return [
        "created_at" => new DateTime(),
        "updated_at" => new DateTime(),
    ];
});
