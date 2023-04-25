<?php
use Faker\Generator as Faker;

$factory->define(\App\Notification::class, function(Faker $faker) {
    return [
        "from_id" => -1,
        "created_at" => new DateTime(),
        "updated_at" => new DateTime(),
    ];
});
