<?php

namespace Tests\Feature;

use App\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

/**
 * Empties the database before running each test and then rolls back any database changes after the test is complete.
 */
class DatabaseTestCase extends TestCase
{
    use DatabaseMigrations;

    protected function login(User $user): string
    {
        $response = $this->postJson("/api/user/login", ["email" => $user->email, "password" => "secret"]);
        $json = json_decode($response->content());
        return $json->jwt;
    }
}
