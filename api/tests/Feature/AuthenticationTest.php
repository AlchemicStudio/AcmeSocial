<?php

declare(strict_types=1);

use App\Models\User;
use Laravel\Sanctum\Sanctum;

test('unauthenticated user cannot access protected user endpoint', function () {
    $response = $this->getJson('/api/user');

    $response->assertStatus(401)
        ->assertJson(['message' => 'Unauthenticated.']);
});

test('authenticated user can access user endpoint', function () {
    $user = User::factory()->create([
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'is_admin' => 0,
    ]);

    Sanctum::actingAs($user);

    $response = $this->getJson('/api/user');

    $response->assertStatus(200)
        ->assertJson([
            'id' => $user->id,
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'is_admin' => 0,
        ])
        ->assertJsonMissing(['password'])
        ->assertJsonMissing(['remember_token']);
});

test('admin user can access user endpoint', function () {
    $admin = User::factory()->create([
        'name' => 'Admin User',
        'email' => 'admin@example.com',
        'is_admin' => 1,
    ]);

    Sanctum::actingAs($admin);

    $response = $this->getJson('/api/user');

    $response->assertStatus(200)
        ->assertJson([
            'id' => $admin->id,
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'is_admin' => 1,
        ]);
});

test('user endpoint returns correct user data structure', function () {
    $user = User::factory()->create();

    Sanctum::actingAs($user);

    $response = $this->getJson('/api/user');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'id',
            'name',
            'email',
            'is_admin',
            'email_verified_at',
            'created_at',
            'updated_at'
        ])
        ->assertJsonMissing(['password'])
        ->assertJsonMissing(['remember_token']);
});

test('user endpoint with different authentication tokens', function () {
    $user1 = User::factory()->create(['name' => 'User One']);
    $user2 = User::factory()->create(['name' => 'User Two']);

    // Test with first user's token
    Sanctum::actingAs($user1);
    $response1 = $this->getJson('/api/user');
    $response1->assertStatus(200)
        ->assertJson(['name' => 'User One']);

    // Test with second user's token
    Sanctum::actingAs($user2);
    $response2 = $this->getJson('/api/user');
    $response2->assertStatus(200)
        ->assertJson(['name' => 'User Two']);
});
