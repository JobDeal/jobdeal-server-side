<?php

namespace Tests\Feature;

use App\User;
use Tests\Feature\Seed\Notification\Doer\NotificationFilterSeeder;

class DoerNotificationTest extends DatabaseTestCase
{
    public function testNotificationListFilter()
    {
        // Seed from here, so you can customize seeds for each test
        $this->seed(NotificationFilterSeeder::class);

        $user = User::find(2);
        $jwt = $this->login($user);

        $response = $this->actingAs($user)
            ->withHeader("Authorization", "Bearer " . $jwt)
            ->get('/api/notification/types/0/0');
        $this->assertJson($response->content());
        $data = json_decode($response->content(), true);
        $this->assertCount(4, $data);
    }
}
