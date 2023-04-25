<?php
use Faker\Generator as Faker;

$factory->define(\App\Price::class, function(Faker $faker) {
    return [
        "id" => 1,
        "list" => 1,
        "boost" => 1,
        "choose" => 1,
        "difference" => 1,
        "subscribe" => 1,
        "country" => "SE",
        "currency" => "SEK",
        "from_date" => new DateTime()
    ];
});
