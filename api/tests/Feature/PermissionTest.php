<?php

declare(strict_types=1);

use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;

test('admin can list permissions', function () {
    $admin = User::factory()->create(['is_admin' => 1]);

    // Create test permissions
    $permissions = [
        'view campaigns',
        'create campaigns',
        'edit campaigns',
        'delete campaigns',
        'approve campaigns',
        'reject campaigns',
        'view donations',
        'create donations',
        'edit donations',
        'delete donations',
        'view transactions',
        'manage users',
        'manage permissions',
    ];

    foreach ($permissions as $permissionName) {
        Permission::create(['name' => $permissionName]);
    }

    Sanctum::actingAs($admin);

    $response = $this->getJson('/api/permissions');

    $response->assertStatus(200)
        ->assertJsonCount(count($permissions), 'data')
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'name',
                    'guard_name',
                    'created_at',
                    'updated_at'
                ]
            ]
        ]);

    // Verify all permissions are returned
    foreach ($permissions as $permissionName) {
        $response->assertJsonFragment(['name' => $permissionName]);
    }
});

test('permissions are returned with correct structure', function () {
    $admin = User::factory()->create(['is_admin' => 1]);

    $permission = Permission::create([
        'name' => 'test permission',
        'guard_name' => 'web',
    ]);

    Sanctum::actingAs($admin);

    $response = $this->getJson('/api/permissions');

    $response->assertStatus(200);

    $permissionData = collect($response->json('data'))
        ->firstWhere('id', $permission->id);

    expect($permissionData)->not->toBeNull()
        ->and($permissionData['name'])->toBe('test permission')
        ->and($permissionData['guard_name'])->toBe('web')
        ->and($permissionData['id'])->toBe($permission->id)
        ->and($permissionData['created_at'])->not->toBeNull()
        ->and($permissionData['updated_at'])->not->toBeNull();
});

test('permissions are ordered alphabetically by name', function () {
    $admin = User::factory()->create(['is_admin' => 1]);

    // Create permissions in random order
    $permissionNames = [
        'zebra permission',
        'alpha permission',
        'beta permission',
        'gamma permission',
    ];

    foreach ($permissionNames as $name) {
        Permission::create(['name' => $name]);
    }

    Sanctum::actingAs($admin);

    $response = $this->getJson('/api/permissions');

    $response->assertStatus(200);

    $returnedNames = collect($response->json('data'))
        ->pluck('name')
        ->toArray();

    $expectedOrder = [
        'alpha permission',
        'beta permission',
        'gamma permission',
        'zebra permission',
    ];

    expect($returnedNames)->toBe($expectedOrder);
});

test('permissions endpoint returns empty array when no permissions exist', function () {
    $admin = User::factory()->create(['is_admin' => 1]);

    Sanctum::actingAs($admin);

    $response = $this->getJson('/api/permissions');

    $response->assertStatus(200)
        ->assertJson(['data' => []]);
});

test('permissions include guard information', function () {
    $admin = User::factory()->create(['is_admin' => 1]);

    $webPermission = Permission::create([
        'name' => 'web permission',
        'guard_name' => 'web',
    ]);

    $apiPermission = Permission::create([
        'name' => 'api permission',
        'guard_name' => 'api',
    ]);

    Sanctum::actingAs($admin);

    $response = $this->getJson('/api/permissions');

    $response->assertStatus(200);

    $permissionsData = collect($response->json('data'));

    $webPermissionData = $permissionsData->firstWhere('name', 'web permission');
    $apiPermissionData = $permissionsData->firstWhere('name', 'api permission');

    expect($webPermissionData['guard_name'])->toBe('web')
        ->and($apiPermissionData['guard_name'])->toBe('api');
});

test('permissions can be filtered by category naming convention', function () {
    $admin = User::factory()->create(['is_admin' => 1]);

    // Create permissions following category naming convention
    $campaignPermissions = [
        'campaigns.view',
        'campaigns.create',
        'campaigns.edit',
        'campaigns.delete',
    ];

    $donationPermissions = [
        'donations.view',
        'donations.create',
        'donations.edit',
    ];

    $userPermissions = [
        'users.view',
        'users.create',
        'users.edit',
        'users.delete',
    ];

    $allPermissions = array_merge($campaignPermissions, $donationPermissions, $userPermissions);

    foreach ($allPermissions as $permission) {
        Permission::create(['name' => $permission]);
    }

    Sanctum::actingAs($admin);

    $response = $this->getJson('/api/permissions');

    $response->assertStatus(200)
        ->assertJsonCount(count($allPermissions), 'data');

    // Verify all permissions are returned
    foreach ($allPermissions as $permissionName) {
        $response->assertJsonFragment(['name' => $permissionName]);
    }
});

test('permissions with special characters are handled correctly', function () {
    $admin = User::factory()->create(['is_admin' => 1]);

    $specialPermissions = [
        'permission-with-dashes',
        'permission_with_underscores',
        'permission with spaces',
        'permission.with.dots',
        'permission:with:colons',
    ];

    foreach ($specialPermissions as $permission) {
        Permission::create(['name' => $permission]);
    }

    Sanctum::actingAs($admin);

    $response = $this->getJson('/api/permissions');

    $response->assertStatus(200)
        ->assertJsonCount(count($specialPermissions), 'data');

    foreach ($specialPermissions as $permissionName) {
        $response->assertJsonFragment(['name' => $permissionName]);
    }
});

test('permissions endpoint handles large number of permissions', function () {
    $admin = User::factory()->create(['is_admin' => 1]);

    // Create a large number of permissions
    $permissionCount = 100;
    for ($i = 1; $i <= $permissionCount; $i++) {
        Permission::create(['name' => "permission_{$i}"]);
    }

    Sanctum::actingAs($admin);

    $response = $this->getJson('/api/permissions');

    $response->assertStatus(200)
        ->assertJsonCount($permissionCount, 'data');
});

test('non-admin user cannot access permissions endpoint', function () {
    $user = User::factory()->create(['is_admin' => 0]);

    Permission::create(['name' => 'test permission']);

    Sanctum::actingAs($user);

    $response = $this->getJson('/api/permissions');

    $response->assertStatus(403);
});

test('unauthenticated user cannot access permissions endpoint', function () {
    Permission::create(['name' => 'test permission']);

    $response = $this->getJson('/api/permissions');

    $response->assertStatus(401)
        ->assertJson(['message' => 'Unauthenticated.']);
});

test('permissions maintain consistency across multiple requests', function () {
    $admin = User::factory()->create(['is_admin' => 1]);

    $permissions = [
        'consistent permission 1',
        'consistent permission 2',
        'consistent permission 3',
    ];

    foreach ($permissions as $permission) {
        Permission::create(['name' => $permission]);
    }

    Sanctum::actingAs($admin);

    // Make multiple requests
    $response1 = $this->getJson('/api/permissions');
    $response2 = $this->getJson('/api/permissions');

    $response1->assertStatus(200);
    $response2->assertStatus(200);

    // Ensure both responses are identical
    expect($response1->json('data'))->toBe($response2->json('data'));
});

test('permissions endpoint respects database changes', function () {
    $admin = User::factory()->create(['is_admin' => 1]);

    Sanctum::actingAs($admin);

    // Initially no permissions
    $response1 = $this->getJson('/api/permissions');
    $response1->assertStatus(200)
        ->assertJsonCount(0, 'data');

    // Add a permission
    Permission::create(['name' => 'new permission']);

    // Should now show the new permission
    $response2 = $this->getJson('/api/permissions');
    $response2->assertStatus(200)
        ->assertJsonCount(1, 'data')
        ->assertJsonFragment(['name' => 'new permission']);

    // Add another permission
    Permission::create(['name' => 'another permission']);

    // Should now show both permissions
    $response3 = $this->getJson('/api/permissions');
    $response3->assertStatus(200)
        ->assertJsonCount(2, 'data')
        ->assertJsonFragment(['name' => 'new permission'])
        ->assertJsonFragment(['name' => 'another permission']);
});
