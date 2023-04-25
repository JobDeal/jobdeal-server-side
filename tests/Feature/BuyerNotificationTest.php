<?php

namespace Tests\Feature;

use App\User;
use Tests\Feature\Seed\Notification\Buyer\NotificationFilterSeeder;

class BuyerNotificationTest extends DatabaseTestCase
{
    public function testNotificationListFilter()
    {
        $this->seed(NotificationFilterSeeder::class);

        $user = User::find(1);
        $jwt = $this->login($user);

        $response = $this->actingAs($user)
            ->withHeader("Authorization", "Bearer " . $jwt)
            ->get("/api/notification/types/1/0");
        fwrite(STDOUT, $response->content());
        $this->assertJson($response->content());
        $data = json_decode($response->content(), true);
        $this->assertCount(3, $data);
    }
}
