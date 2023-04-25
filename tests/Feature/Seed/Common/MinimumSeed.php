<?php

namespace Tests\Feature\Seed\Common;

use Illuminate\Database\Seeder;

class MinimumSeed extends Seeder
{
    public function run()
    {
        $this->call([
            UsersTableSeeder::class,
            CategoriesTableSeeder::class,
            PricesTableSeeder::class,
        ]);
    }
}
