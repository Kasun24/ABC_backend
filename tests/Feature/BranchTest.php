<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class BranchTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */
    use RefreshDatabase;
    public function test_admin_can_create_branch()
    {
        // Create an admin user with the correct permissions
        $admin = \App\Models\User::factory()->create([
            'role' => 'admin',  // or whatever role structure you use
        ]);

        // Mock permission check to return true for the test
        \Mockery::mock('alias:App\Helpers\Helper')
            ->shouldReceive('checkFunctionPermission')
            ->with('branch_add')
            ->andReturn(true);

        // Simulate login to get a valid token
        $token = $this->postJson('/api/login', [
            'email' => $admin->email,
            'password' => 'password',  // Ensure this matches the user setup
        ])->json('token');

        // Create a branch with a unique name
        $response = $this->postJson('/api/branch/create', [
            'name' => 'New Test Branch',
            'bill_split' => true,  // Any other required field if applicable
        ], [
            'Authorization' => "Bearer $token"
        ]);

        // Assert that the branch is created successfully
        $response->assertStatus(200)
            ->assertJson(['status' => true, 'message' => 'Branch added successfully']);

        // Optionally, you can verify that the branch exists in the database
        $this->assertDatabaseHas('branches', [
            'name' => 'New Test Branch',
        ]);
    }
    
}
