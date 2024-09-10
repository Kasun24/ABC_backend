<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class CustomerTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function test_can_create_customer()
{
    // Simulate a POST request to create a customer
    $response = $this->postJson('/api/customer/create', [
        'first_name' => 'John',
        'last_name' => 'Doe',
        'mobile_number' => '0771234567',
        'email' => 'john.doe@example.com',
    ]);

    // Assert that the customer is created successfully
    $response->assertStatus(200)
             ->assertJson(['status' => true, 'message' => 'Customer added successfully']);

    // Verify that the customer exists in the database
    $this->assertDatabaseHas('customers', [
        'first_name' => 'John',
        'last_name' => 'Doe',
        'mobile_number' => '0771234567',
    ]);
}
public function test_customer_creation_fails_with_missing_required_fields()
{
    // Simulate a POST request with missing required fields (first_name and mobile_number)
    $response = $this->postJson('/api/customer/create', [
        'email' => 'john.doe@example.com',
    ]);

    // Assert that the validation fails with status 422 (Unprocessable Entity)
    $response->assertStatus(422)
             ->assertJsonValidationErrors(['first_name', 'mobile_number']);
}


}
