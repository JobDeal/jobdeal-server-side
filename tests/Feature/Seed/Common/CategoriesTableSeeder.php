<?php

namespace Tests\Feature\Seed\Common;

use App\Category;
use Illuminate\Database\Seeder;

class CategoriesTableSeeder extends Seeder
{
    public function run()
    {
        factory(Category::class)->create();
    }
}
