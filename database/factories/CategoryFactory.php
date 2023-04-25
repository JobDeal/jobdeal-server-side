<?php
use Faker\Generator as Faker;

$factory->define(\App\Category::class, function(Faker $faker) {
    return [
        "id" => 1,
        "name" => "Category",
        "color" => "X",
        "image" => "x.jpg",
    ];
});
