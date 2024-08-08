<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

public function testBasicTest()
{
    Artisan::call('middleware:disable', ['middleware' => 'locked']);
    // Create a test user
    $user = User::factory()->create();

    // Simulate authentication
    $response = $this->actingAs($user)->get('/');

    // Assert the response status
    $response->assertStatus(200);
}

