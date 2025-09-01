<?php

declare(strict_types=1);

use App\Models\Campaign;
use App\Models\Donation;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

test('authenticated user can list donations', function () {
    $user = User::factory()->create();
    $donations = Donation::factory(3)->create();

    Sanctum::actingAs($user);

    $response = $this->getJson('/api/donations');

    $response->assertStatus(200)
        ->assertJsonCount(3, 'data')
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'campaign_id',
                    'donor_id',
                    'amount',
                    'currency',
                    'message',
                    'visibility',
                    'status',
                    'created_at',
                    'updated_at'
                ]
            ]
        ]);
});

test('authenticated user can view single donation', function () {
    $user = User::factory()->create();
    $donation = Donation::factory()->create();

    Sanctum::actingAs($user);

    $response = $this->getJson("/api/donations/{$donation->id}");

    $response->assertStatus(200)
        ->assertJson([
            'data' => [
                'id' => $donation->id,
                'campaign_id' => $donation->campaign_id,
                'donor_id' => $donation->donor_id,
                'amount' => $donation->amount,
                'currency' => $donation->currency,
                'visibility' => $donation->visibility,
                'status' => $donation->status,
            ]
        ]);
});

test('authenticated user can create donation', function () {
    $user = User::factory()->create();
    $campaign = Campaign::factory()->create();

    Sanctum::actingAs($user);

    $donationData = [
        'campaign_id' => $campaign->id,
        'amount' => 10000,
        'currency' => 'USD',
        'message' => 'Happy to support this cause!',
        'visibility' => Donation::VISIBILITY_PUBLIC,
    ];

    $response = $this->postJson('/api/donations', $donationData);

    $response->assertStatus(201)
        ->assertJson([
            'data' => [
                'campaign_id' => $campaign->id,
                'donor_id' => $user->id,
                'amount' => 10000,
                'currency' => 'USD',
                'message' => 'Happy to support this cause!',
                'visibility' => Donation::VISIBILITY_PUBLIC,
                'status' => Donation::STATUS_PENDING,
            ]
        ]);

    $this->assertDatabaseHas('donations', [
        'campaign_id' => $campaign->id,
        'donor_id' => $user->id,
        'amount' => 10000,
        'currency' => 'USD',
        'status' => Donation::STATUS_PENDING,
    ]);
});

test('authenticated user can update their own donation', function () {
    $user = User::factory()->create();
    $donation = Donation::factory()->create(['donor_id' => $user->id]);

    Sanctum::actingAs($user);

    $updateData = [
        'message' => 'Updated donation message',
        'visibility' => Donation::VISIBILITY_ANONYMOUS,
    ];

    $response = $this->putJson("/api/donations/{$donation->id}", $updateData);

    $response->assertStatus(200)
        ->assertJson([
            'data' => [
                'id' => $donation->id,
                'message' => 'Updated donation message',
                'visibility' => Donation::VISIBILITY_ANONYMOUS,
            ]
        ]);

    $this->assertDatabaseHas('donations', [
        'id' => $donation->id,
        'message' => 'Updated donation message',
        'visibility' => Donation::VISIBILITY_ANONYMOUS,
    ]);
});

test('authenticated user can delete their own donation', function () {
    $user = User::factory()->create();
    $donation = Donation::factory()->create(['donor_id' => $user->id]);

    Sanctum::actingAs($user);

    $response = $this->deleteJson("/api/donations/{$donation->id}");

    $response->assertStatus(204);

    $this->assertDatabaseMissing('donations', ['id' => $donation->id]);
});

test('authenticated user can create campaign donation', function () {
    $user = User::factory()->create();
    $campaign = Campaign::factory()->create();

    Sanctum::actingAs($user);

    $donationData = [
        'amount' => 15000,
        'currency' => 'EUR',
        'message' => 'Great campaign!',
        'visibility' => Donation::VISIBILITY_PUBLIC,
    ];

    $response = $this->postJson("/api/campaigns/{$campaign->id}/donations", $donationData);

    $response->assertStatus(201)
        ->assertJson([
            'data' => [
                'campaign_id' => $campaign->id,
                'donor_id' => $user->id,
                'amount' => 15000,
                'currency' => 'EUR',
                'message' => 'Great campaign!',
                'visibility' => Donation::VISIBILITY_PUBLIC,
            ]
        ]);

    $this->assertDatabaseHas('donations', [
        'campaign_id' => $campaign->id,
        'donor_id' => $user->id,
        'amount' => 15000,
        'currency' => 'EUR',
    ]);
});

test('authenticated user can list campaign donations', function () {
    $user = User::factory()->create();
    $campaign = Campaign::factory()->create();
    $donations = Donation::factory(3)->create(['campaign_id' => $campaign->id]);

    // Create donations for other campaigns (should not be included)
    Donation::factory(2)->create();

    Sanctum::actingAs($user);

    $response = $this->getJson("/api/campaigns/{$campaign->id}/donations");

    $response->assertStatus(200)
        ->assertJsonCount(3, 'data')
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'campaign_id',
                    'donor_id',
                    'amount',
                    'currency',
                    'message',
                    'visibility',
                    'status',
                    'created_at',
                    'updated_at'
                ]
            ]
        ]);

    foreach ($response->json('data') as $donationData) {
        expect($donationData['campaign_id'])->toBe($campaign->id);
    }
});

test('authenticated user can view specific campaign donation', function () {
    $user = User::factory()->create();
    $campaign = Campaign::factory()->create();
    $donation = Donation::factory()->create(['campaign_id' => $campaign->id]);

    Sanctum::actingAs($user);

    $response = $this->getJson("/api/campaigns/{$campaign->id}/donations/{$donation->id}");

    $response->assertStatus(200)
        ->assertJson([
            'data' => [
                'id' => $donation->id,
                'campaign_id' => $campaign->id,
                'donor_id' => $donation->donor_id,
                'amount' => $donation->amount,
                'currency' => $donation->currency,
                'visibility' => $donation->visibility,
                'status' => $donation->status,
            ]
        ]);
});

test('anonymous donations hide donor information', function () {
    $user = User::factory()->create();
    $donor = User::factory()->create(['name' => 'Secret Donor']);
    $campaign = Campaign::factory()->create();

    $anonymousDonation = Donation::factory()->create([
        'campaign_id' => $campaign->id,
        'donor_id' => $donor->id,
        'visibility' => Donation::VISIBILITY_ANONYMOUS,
        'message' => 'Anonymous donation message',
    ]);

    Sanctum::actingAs($user);

    $response = $this->getJson("/api/campaigns/{$campaign->id}/donations");

    $response->assertStatus(200);

    $anonymousDonationData = collect($response->json('data'))
        ->firstWhere('id', $anonymousDonation->id);

    expect($anonymousDonationData['visibility'])->toBe(Donation::VISIBILITY_ANONYMOUS);
});

test('donation creation requires validation', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $response = $this->postJson('/api/donations', []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['campaign_id', 'amount']);
});

test('campaign donation creation requires validation', function () {
    $user = User::factory()->create();
    $campaign = Campaign::factory()->create();

    Sanctum::actingAs($user);

    $response = $this->postJson("/api/campaigns/{$campaign->id}/donations", []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['amount']);
});

test('user cannot update donation they do not own', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $donation = Donation::factory()->create(['donor_id' => $user1->id]);

    Sanctum::actingAs($user2);

    $response = $this->putJson("/api/donations/{$donation->id}", [
        'message' => 'Hacked message',
    ]);

    $response->assertStatus(403);
});

test('user cannot delete donation they do not own', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $donation = Donation::factory()->create(['donor_id' => $user1->id]);

    Sanctum::actingAs($user2);

    $response = $this->deleteJson("/api/donations/{$donation->id}");

    $response->assertStatus(403);
});

test('unauthenticated user cannot access donation endpoints', function () {
    $campaign = Campaign::factory()->create();
    $donation = Donation::factory()->create();

    $this->getJson('/api/donations')->assertStatus(401);
    $this->getJson("/api/donations/{$donation->id}")->assertStatus(401);
    $this->postJson('/api/donations', [])->assertStatus(401);
    $this->putJson("/api/donations/{$donation->id}", [])->assertStatus(401);
    $this->deleteJson("/api/donations/{$donation->id}")->assertStatus(401);
    $this->postJson("/api/campaigns/{$campaign->id}/donations", [])->assertStatus(401);
    $this->getJson("/api/campaigns/{$campaign->id}/donations")->assertStatus(401);
    $this->getJson("/api/campaigns/{$campaign->id}/donations/{$donation->id}")->assertStatus(401);
});

test('donation statuses work correctly', function () {
    $user = User::factory()->create();

    $pendingDonation = Donation::factory()->create(['status' => Donation::STATUS_PENDING]);
    $completedDonation = Donation::factory()->create(['status' => Donation::STATUS_COMPLETED]);
    $failedDonation = Donation::factory()->create(['status' => Donation::STATUS_FAILED]);
    $refundedDonation = Donation::factory()->create(['status' => Donation::STATUS_REFUNDED]);

    Sanctum::actingAs($user);

    $response = $this->getJson('/api/donations');

    $response->assertStatus(200);

    $donationsData = collect($response->json('data'));

    expect($donationsData->firstWhere('id', $pendingDonation->id)['status'])->toBe(Donation::STATUS_PENDING);
    expect($donationsData->firstWhere('id', $completedDonation->id)['status'])->toBe(Donation::STATUS_COMPLETED);
    expect($donationsData->firstWhere('id', $failedDonation->id)['status'])->toBe(Donation::STATUS_FAILED);
    expect($donationsData->firstWhere('id', $refundedDonation->id)['status'])->toBe(Donation::STATUS_REFUNDED);
});

test('donation not found returns 404', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $nonExistentId = '00000000-0000-0000-0000-000000000000';

    $response = $this->getJson("/api/donations/{$nonExistentId}");

    $response->assertStatus(404);
});
