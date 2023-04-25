<?php

namespace Tests\Feature\Seed\Common;

use App\Price;
use Illuminate\Database\Seeder;

class PricesTableSeeder extends Seeder
{
    public function run()
    {
        factory(Price::class)->create([
            "id" => 1
        ]);
    }
}
