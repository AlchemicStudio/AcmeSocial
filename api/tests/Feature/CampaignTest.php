<?php

declare(strict_types=1);

use App\Models\Campaign;
use App\Models\Donation;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

test('authenticated user can list campaigns', function () {
    $user = User::factory()->create();
    $campaigns = Campaign::factory(3)->create();

    Sanctum::actingAs($user);

    $response = $this->getJson('/api/campaigns');

    $response->assertStatus(200)
        ->assertJsonCount(3, 'data')
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'title',
                    'description',
                    'goal_amount',
                    'current_amount',
                    'start_date',
                    'end_date',
                    'status',
                    'creator_id',
                    'created_at',
                    'updated_at'
                ]
            ]
        ]);
});

test('authenticated user can view single campaign', function () {
    $user = User::factory()->create();
    $campaign = Campaign::factory()->create();

    Sanctum::actingAs($user);

    $response = $this->getJson("/api/campaigns/{$campaign->id}");

    $response->assertStatus(200)
        ->assertJson([
            'data' => [
                'id' => $campaign->id,
                'title' => $campaign->title,
                'description' => $campaign->description,
                'goal_amount' => $campaign->goal_amount,
                'current_amount' => $campaign->current_amount,
                'status' => $campaign->status,
                'creator_id' => $campaign->creator_id,
            ]
        ]);
});

test('authenticated user can create campaign', function () {
    $user = User::factory()->create();

    Sanctum::actingAs($user);

    $campaignData = [
        'title' => 'Test Campaign',
        'description' => 'This is a test campaign description',
        'goal_amount' => 50000,
        'start_date' => '2024-01-01',
        'end_date' => '2024-12-31',
        'status' => Campaign::STATUS_DRAFT,
    ];

    $response = $this->postJson('/api/campaigns', $campaignData);

    $response->assertStatus(201)
        ->assertJson([
            'data' => [
                'title' => 'Test Campaign',
                'description' => 'This is a test campaign description',
                'goal_amount' => 50000,
                'status' => Campaign::STATUS_DRAFT,
                'creator_id' => $user->id,
            ]
        ]);

    $this->assertDatabaseHas('campaigns', [
        'title' => 'Test Campaign',
        'creator_id' => $user->id,
        'goal_amount' => 50000,
        'status' => Campaign::STATUS_DRAFT,
    ]);
});

test('authenticated user can update their own campaign', function () {
    $user = User::factory()->create();
    $campaign = Campaign::factory()->create(['creator_id' => $user->id]);

    Sanctum::actingAs($user);

    $updateData = [
        'title' => 'Updated Campaign Title',
        'description' => 'Updated campaign description',
        'goal_amount' => 75000,
    ];

    $response = $this->putJson("/api/campaigns/{$campaign->id}", $updateData);

    $response->assertStatus(200)
        ->assertJson([
            'data' => [
                'id' => $campaign->id,
                'title' => 'Updated Campaign Title',
                'description' => 'Updated campaign description',
                'goal_amount' => 75000,
            ]
        ]);

    $this->assertDatabaseHas('campaigns', [
        'id' => $campaign->id,
        'title' => 'Updated Campaign Title',
        'goal_amount' => 75000,
    ]);
});

test('authenticated user can delete their own campaign', function () {
    $user = User::factory()->create();
    $campaign = Campaign::factory()->create(['creator_id' => $user->id]);

    Sanctum::actingAs($user);

    $response = $this->deleteJson("/api/campaigns/{$campaign->id}");

    $response->assertStatus(204);

    $this->assertSoftDeleted('campaigns', ['id' => $campaign->id]);
});

test('authenticated user can approve campaign', function () {
    $admin = User::factory()->create(['is_admin' => 1]);
    $campaign = Campaign::factory()->create(['status' => Campaign::STATUS_PENDING]);

    Sanctum::actingAs($admin);

    $response = $this->putJson("/api/campaigns/{$campaign->id}/approve");

    $response->assertStatus(200)
        ->assertJson([
            'data' => [
                'id' => $campaign->id,
                'status' => Campaign::STATUS_APPROVED,
                'approved_by' => $admin->id,
            ]
        ]);

    $this->assertDatabaseHas('campaigns', [
        'id' => $campaign->id,
        'status' => Campaign::STATUS_APPROVED,
        'approved_by' => $admin->id,
    ]);
});

test('authenticated user can reject campaign', function () {
    $admin = User::factory()->create(['is_admin' => 1]);
    $campaign = Campaign::factory()->create(['status' => Campaign::STATUS_PENDING]);

    Sanctum::actingAs($admin);

    $rejectionData = [
        'rejected_reason' => 'Campaign does not meet requirements',
    ];

    $response = $this->putJson("/api/campaigns/{$campaign->id}/reject", $rejectionData);

    $response->assertStatus(200)
        ->assertJson([
            'data' => [
                'id' => $campaign->id,
                'status' => Campaign::STATUS_REJECTED,
                'rejected_by' => $admin->id,
                'rejected_reason' => 'Campaign does not meet requirements',
            ]
        ]);

    $this->assertDatabaseHas('campaigns', [
        'id' => $campaign->id,
        'status' => Campaign::STATUS_REJECTED,
        'rejected_by' => $admin->id,
        'rejected_reason' => 'Campaign does not meet requirements',
    ]);
});

test('authenticated user can get campaign statistics', function () {
    $user = User::factory()->create();
    $campaign = Campaign::factory()->create();

    // Create completed donations for statistics
    Donation::factory()->create([
        'campaign_id' => $campaign->id,
        'status' => Donation::STATUS_COMPLETED,
        'amount' => 100,
        'created_at' => '2024-01-01',
    ]);

    Donation::factory()->create([
        'campaign_id' => $campaign->id,
        'status' => Donation::STATUS_COMPLETED,
        'amount' => 200,
        'created_at' => '2024-01-02',
    ]);

    Sanctum::actingAs($user);

    $response = $this->getJson("/api/campaigns/{$campaign->id}/statistics");

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                'labels',
                'datasets' => [
                    '*' => [
                        'label',
                        'data'
                    ]
                ]
            ]
        ]);
});

test('unauthenticated user cannot access campaign endpoints', function () {
    $campaign = Campaign::factory()->create();

    $this->getJson('/api/campaigns')->assertStatus(401);
    $this->getJson("/api/campaigns/{$campaign->id}")->assertStatus(401);
    $this->postJson('/api/campaigns', [])->assertStatus(401);
    $this->putJson("/api/campaigns/{$campaign->id}", [])->assertStatus(401);
    $this->deleteJson("/api/campaigns/{$campaign->id}")->assertStatus(401);
    $this->getJson("/api/campaigns/{$campaign->id}/statistics")->assertStatus(401);
    $this->putJson("/api/campaigns/{$campaign->id}/approve")->assertStatus(401);
    $this->putJson("/api/campaigns/{$campaign->id}/reject")->assertStatus(401);
});

test('campaign creation requires validation', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $response = $this->postJson('/api/campaigns', []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['title', 'description', 'goal_amount']);
});

test('campaign update requires validation', function () {
    $user = User::factory()->create();
    $campaign = Campaign::factory()->create(['creator_id' => $user->id]);

    Sanctum::actingAs($user);

    $response = $this->putJson("/api/campaigns/{$campaign->id}", [
        'goal_amount' => 'invalid_amount',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['goal_amount']);
});

test('user cannot update campaign they do not own', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $campaign = Campaign::factory()->create(['creator_id' => $user1->id]);

    Sanctum::actingAs($user2);

    $response = $this->putJson("/api/campaigns/{$campaign->id}", [
        'title' => 'Hacked Title',
    ]);

    $response->assertStatus(403);
});

test('user cannot delete campaign they do not own', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $campaign = Campaign::factory()->create(['creator_id' => $user1->id]);

    Sanctum::actingAs($user2);

    $response = $this->deleteJson("/api/campaigns/{$campaign->id}");

    $response->assertStatus(403);
});

test('campaign not found returns 404', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $nonExistentId = '00000000-0000-0000-0000-000000000000';

    $response = $this->getJson("/api/campaigns/{$nonExistentId}");

    $response->assertStatus(404);
});
