<?php

declare(strict_types=1);

use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;

test('admin can list users', function () {
    $admin = User::factory()->create(['is_admin' => 1]);
    $users = User::factory(3)->create(['is_admin' => 0]);

    Sanctum::actingAs($admin);

    $response = $this->getJson('/api/users');

    $response->assertStatus(200)
        ->assertJsonCount(4, 'data') // 3 users + 1 admin
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'name',
                    'email',
                    'is_admin',
                    'email_verified_at',
                    'created_at',
                    'updated_at'
                ]
            ]
        ]);
});

test('admin can view single user', function () {
    $admin = User::factory()->create(['is_admin' => 1]);
    $user = User::factory()->create([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'is_admin' => 0,
    ]);

    Sanctum::actingAs($admin);

    $response = $this->getJson("/api/users/{$user->id}");

    $response->assertStatus(200)
        ->assertJson([
            'data' => [
                'id' => $user->id,
                'name' => 'Test User',
                'email' => 'test@example.com',
                'is_admin' => 0,
            ]
        ])
        ->assertJsonMissing(['password'])
        ->assertJsonMissing(['remember_token']);
});

test('admin can create user', function () {
    $admin = User::factory()->create(['is_admin' => 1]);

    Sanctum::actingAs($admin);

    $userData = [
        'name' => 'New User',
        'email' => 'newuser@example.com',
        'password' => 'password123',
        'is_admin' => 0,
    ];

    $response = $this->postJson('/api/users', $userData);

    $response->assertStatus(201)
        ->assertJson([
            'data' => [
                'name' => 'New User',
                'email' => 'newuser@example.com',
                'is_admin' => 0,
            ]
        ]);

    $this->assertDatabaseHas('users', [
        'name' => 'New User',
        'email' => 'newuser@example.com',
        'is_admin' => 0,
    ]);

    // Verify password is hashed
    $createdUser = User::where('email', 'newuser@example.com')->first();
    expect(Hash::check('password123', $createdUser->password))->toBeTrue();
});

test('admin can update user', function () {
    $admin = User::factory()->create(['is_admin' => 1]);
    $user = User::factory()->create([
        'name' => 'Original Name',
        'email' => 'original@example.com',
        'is_admin' => 0,
    ]);

    Sanctum::actingAs($admin);

    $updateData = [
        'name' => 'Updated Name',
        'email' => 'updated@example.com',
        'is_admin' => 1,
    ];

    $response = $this->putJson("/api/users/{$user->id}", $updateData);

    $response->assertStatus(200)
        ->assertJson([
            'data' => [
                'id' => $user->id,
                'name' => 'Updated Name',
                'email' => 'updated@example.com',
                'is_admin' => 1,
            ]
        ]);

    $this->assertDatabaseHas('users', [
        'id' => $user->id,
        'name' => 'Updated Name',
        'email' => 'updated@example.com',
        'is_admin' => 1,
    ]);
});

test('admin can delete user', function () {
    $admin = User::factory()->create(['is_admin' => 1]);
    $user = User::factory()->create();

    Sanctum::actingAs($admin);

    $response = $this->deleteJson("/api/users/{$user->id}");

    $response->assertStatus(204);

    $this->assertDatabaseMissing('users', ['id' => $user->id]);
});

test('admin can search users', function () {
    $admin = User::factory()->create(['is_admin' => 1]);

    $john = User::factory()->create(['name' => 'John Doe', 'email' => 'john@example.com']);
    $jane = User::factory()->create(['name' => 'Jane Smith', 'email' => 'jane@example.com']);
    $bob = User::factory()->create(['name' => 'Bob Johnson', 'email' => 'bob@test.com']);

    Sanctum::actingAs($admin);

    // Search by name
    $response = $this->getJson('/api/users/search?query=John');

    $response->assertStatus(200)
        ->assertJsonCount(2, 'data') // John Doe and Bob Johnson
        ->assertJsonFragment(['name' => 'John Doe'])
        ->assertJsonFragment(['name' => 'Bob Johnson'])
        ->assertJsonMissing(['name' => 'Jane Smith']);

    // Search by email domain
    $response = $this->getJson('/api/users/search?query=example.com');

    $response->assertStatus(200)
        ->assertJsonCount(2, 'data') // john@example.com and jane@example.com
        ->assertJsonFragment(['email' => 'john@example.com'])
        ->assertJsonFragment(['email' => 'jane@example.com'])
        ->assertJsonMissing(['email' => 'bob@test.com']);
});

test('admin can get user permissions', function () {
    $admin = User::factory()->create(['is_admin' => 1]);
    $user = User::factory()->create();

    // Create permissions
    $permission1 = Permission::create(['name' => 'view campaigns']);
    $permission2 = Permission::create(['name' => 'create campaigns']);

    // Assign permissions to user
    $user->givePermissionTo([$permission1, $permission2]);

    Sanctum::actingAs($admin);

    $response = $this->getJson("/api/users/{$user->id}/permissions");

    $response->assertStatus(200)
        ->assertJsonCount(2, 'data')
        ->assertJsonFragment(['name' => 'view campaigns'])
        ->assertJsonFragment(['name' => 'create campaigns']);
});

test('admin can assign permissions to user', function () {
    $admin = User::factory()->create(['is_admin' => 1]);
    $user = User::factory()->create();

    // Create permissions
    $permission1 = Permission::create(['name' => 'edit campaigns']);
    $permission2 = Permission::create(['name' => 'delete campaigns']);

    Sanctum::actingAs($admin);

    $response = $this->postJson("/api/users/{$user->id}/permissions", [
        'permissions' => ['edit campaigns', 'delete campaigns'],
    ]);

    $response->assertStatus(200)
        ->assertJson(['message' => 'Permissions assigned successfully']);

    expect($user->fresh()->hasPermissionTo('edit campaigns'))->toBeTrue()
        ->and($user->fresh()->hasPermissionTo('delete campaigns'))->toBeTrue();
});

test('admin can sync permissions for user', function () {
    $admin = User::factory()->create(['is_admin' => 1]);
    $user = User::factory()->create();

    // Create permissions
    $permission1 = Permission::create(['name' => 'manage users']);
    $permission2 = Permission::create(['name' => 'manage campaigns']);
    $permission3 = Permission::create(['name' => 'view reports']);

    // Give user some initial permissions
    $user->givePermissionTo([$permission1, $permission3]);

    Sanctum::actingAs($admin);

    // Sync with new set of permissions
    $response = $this->putJson("/api/users/{$user->id}/permissions", [
        'permissions' => ['manage campaigns', 'view reports'],
    ]);

    $response->assertStatus(200)
        ->assertJson(['message' => 'Permissions synced successfully']);

    $user->refresh();

    expect($user->hasPermissionTo('manage users'))->toBeFalse()
        ->and($user->hasPermissionTo('manage campaigns'))->toBeTrue()
        ->and($user->hasPermissionTo('view reports'))->toBeTrue();
});

test('admin can remove permissions from user', function () {
    $admin = User::factory()->create(['is_admin' => 1]);
    $user = User::factory()->create();

    // Create permissions
    $permission1 = Permission::create(['name' => 'approve campaigns']);
    $permission2 = Permission::create(['name' => 'reject campaigns']);

    // Give user permissions
    $user->givePermissionTo([$permission1, $permission2]);

    Sanctum::actingAs($admin);

    $response = $this->deleteJson("/api/users/{$user->id}/permissions", [
        'permissions' => ['approve campaigns'],
    ]);

    $response->assertStatus(200)
        ->assertJson(['message' => 'Permissions removed successfully']);

    $user->refresh();

    expect($user->hasPermissionTo('approve campaigns'))->toBeFalse()
        ->and($user->hasPermissionTo('reject campaigns'))->toBeTrue();
});

test('non-admin user cannot access user management endpoints', function () {
    $user = User::factory()->create(['is_admin' => 0]);
    $targetUser = User::factory()->create();

    Sanctum::actingAs($user);

    $this->getJson('/api/users')->assertStatus(403);
    $this->getJson("/api/users/{$targetUser->id}")->assertStatus(403);
    $this->postJson('/api/users', [])->assertStatus(403);
    $this->putJson("/api/users/{$targetUser->id}", [])->assertStatus(403);
    $this->deleteJson("/api/users/{$targetUser->id}")->assertStatus(403);
    $this->getJson('/api/users/search')->assertStatus(403);
    $this->getJson("/api/users/{$targetUser->id}/permissions")->assertStatus(403);
    $this->postJson("/api/users/{$targetUser->id}/permissions", [])->assertStatus(403);
    $this->putJson("/api/users/{$targetUser->id}/permissions", [])->assertStatus(403);
    $this->deleteJson("/api/users/{$targetUser->id}/permissions", [])->assertStatus(403);
});

test('unauthenticated user cannot access user management endpoints', function () {
    $user = User::factory()->create();

    $this->getJson('/api/users')->assertStatus(401);
    $this->getJson("/api/users/{$user->id}")->assertStatus(401);
    $this->postJson('/api/users', [])->assertStatus(401);
    $this->putJson("/api/users/{$user->id}", [])->assertStatus(401);
    $this->deleteJson("/api/users/{$user->id}")->assertStatus(401);
    $this->getJson('/api/users/search')->assertStatus(401);
    $this->getJson("/api/users/{$user->id}/permissions")->assertStatus(401);
    $this->postJson("/api/users/{$user->id}/permissions", [])->assertStatus(401);
    $this->putJson("/api/users/{$user->id}/permissions", [])->assertStatus(401);
    $this->deleteJson("/api/users/{$user->id}/permissions", [])->assertStatus(401);
});

test('user creation requires validation', function () {
    $admin = User::factory()->create(['is_admin' => 1]);
    Sanctum::actingAs($admin);

    $response = $this->postJson('/api/users', []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['name', 'email', 'password']);
});

test('user update with invalid email fails validation', function () {
    $admin = User::factory()->create(['is_admin' => 1]);
    $user = User::factory()->create();

    Sanctum::actingAs($admin);

    $response = $this->putJson("/api/users/{$user->id}", [
        'email' => 'invalid-email',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});

test('user creation with duplicate email fails', function () {
    $admin = User::factory()->create(['is_admin' => 1]);
    $existingUser = User::factory()->create(['email' => 'existing@example.com']);

    Sanctum::actingAs($admin);

    $response = $this->postJson('/api/users', [
        'name' => 'New User',
        'email' => 'existing@example.com',
        'password' => 'password123',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});

test('user not found returns 404', function () {
    $admin = User::factory()->create(['is_admin' => 1]);
    Sanctum::actingAs($admin);

    $nonExistentId = '00000000-0000-0000-0000-000000000000';

    $response = $this->getJson("/api/users/{$nonExistentId}");

    $response->assertStatus(404);
});

test('admin cannot delete themselves', function () {
    $admin = User::factory()->create(['is_admin' => 1]);

    Sanctum::actingAs($admin);

    $response = $this->deleteJson("/api/users/{$admin->id}");

    $response->assertStatus(403)
        ->assertJson(['message' => 'Cannot delete your own account']);
});

test('search returns empty results when no matches', function () {
    $admin = User::factory()->create(['is_admin' => 1]);
    User::factory(3)->create();

    Sanctum::actingAs($admin);

    $response = $this->getJson('/api/users/search?query=nonexistentuser');

    $response->assertStatus(200)
        ->assertJson(['data' => []]);
});
