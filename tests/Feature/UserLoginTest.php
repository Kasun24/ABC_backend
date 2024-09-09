<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class UserLoginTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function test_user_can_login()
    {
        // Create a user in the database
        $user = \App\Models\User::factory()->create([
            'email' => 'testuser@example.com',
            'password' => bcrypt('password123')
        ]);

        // Simulate a POST request to login
        $response = $this->postJson('/api/login', [
            'email' => 'testuser@example.com',
            'password' => 'password123',
        ]);

        // Assert that the response is successful and contains a token
        $response->assertStatus(200)
            ->assertJsonStructure(['token']);
    }

    public function test_user_cannot_login_with_invalid_credentials()
    {
        // Simulate a POST request to login with incorrect password
        $response = $this->postJson('/api/login', [
            'email' => 'testuser@example.com',
            'password' => 'wrongpassword',
        ]);
    
        // Assert that the login fails with a 401 status
        $response->assertStatus(401)
                 ->assertJson(['message' => 'Invalid credentials']);
    }
    
}
