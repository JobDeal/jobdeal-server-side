<?php

namespace Tests\Feature\Seed\Common;

use App\User;
use Illuminate\Database\Seeder;

class UsersTableSeeder extends Seeder
{
    public function run()
    {
        factory(User::class)->create([
            'id' => 1,
            'email' => 'alice@foobar.com',
            'name' => "Alice",
            'surname' => 'sur',
            'mobile' => '070000',
            'address' => 'Address',
            'zip' => '11111',
            'city' => 'Stockholm',
            'country' => 'Sweden',
            'locale' => 'sv-SE'
        ]);

        factory(User::class)->create([
            'id' => 2,
            'email' => 'bob@foobar.com',
            'name' => "Bob",
            'surname' => 'sur',
            'mobile' => '070000',
            'address' => 'Address',
            'zip' => '11111',
            'city' => 'Stockholm',
            'country' => 'Sweden',
            'locale' => 'sv-SE'
        ]);

        factory(User::class)->create([
            'id' => 3,
            'email' => 'carol@foobar.com',
            'name' => "Carol",
            'surname' => 'sur',
            'mobile' => '070000',
            'address' => 'Address',
            'zip' => '11111',
            'city' => 'Stockholm',
            'country' => 'Sweden',
            'locale' => 'sv-SE'
        ]);
    }
}
