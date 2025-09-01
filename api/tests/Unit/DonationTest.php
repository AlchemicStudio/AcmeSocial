<?php

declare(strict_types=1);

use App\Models\Campaign;
use App\Models\Donation;
use App\Models\User;

test('donation belongs to campaign', function () {
    $campaign = Campaign::factory()->create();
    $donation = Donation::factory()->create(['campaign_id' => $campaign->id]);

    expect($donation->campaign)->toBeInstanceOf(Campaign::class)
        ->and($donation->campaign->id)->toBe($campaign->id);
});

test('donation belongs to donor', function () {
    $donor = User::factory()->create();
    $donation = Donation::factory()->create(['donor_id' => $donor->id]);

    expect($donation->donor)->toBeInstanceOf(User::class)
        ->and($donation->donor->id)->toBe($donor->id);
});

test('donation visibility constants are defined', function () {
    expect(Donation::VISIBILITY_PUBLIC)->toBe(0)
        ->and(Donation::VISIBILITY_ANONYMOUS)->toBe(1);
});

test('donation status constants are defined', function () {
    expect(Donation::STATUS_PENDING)->toBe(0)
        ->and(Donation::STATUS_COMPLETED)->toBe(1)
        ->and(Donation::STATUS_FAILED)->toBe(2)
        ->and(Donation::STATUS_REFUNDED)->toBe(3);
});

test('donation attributes are cast correctly', function () {
    $donation = Donation::factory()->create([
        'amount' => '1000',
        'visibility' => '1',
        'status' => '2',
    ]);

    expect($donation->amount)->toBeInt()
        ->and($donation->visibility)->toBeInt()
        ->and($donation->status)->toBeInt();
});

test('donation fillable attributes are correct', function () {
    $fillable = [
        'campaign_id',
        'donor_id',
        'amount',
        'currency',
        'message',
        'visibility',
        'status',
    ];

    $donation = new Donation();
    expect($donation->getFillable())->toBe($fillable);
});

test('donation uses correct traits', function () {
    $donation = new Donation();

    expect($donation)->toUse([
        Illuminate\Database\Eloquent\Factories\HasFactory::class,
        Illuminate\Database\Eloquent\Concerns\HasUuids::class,
        Spatie\Activitylog\Traits\LogsActivity::class,
    ]);
});

test('donation can be created with all required attributes', function () {
    $campaign = Campaign::factory()->create();
    $donor = User::factory()->create();

    $donation = Donation::factory()->create([
        'campaign_id' => $campaign->id,
        'donor_id' => $donor->id,
        'amount' => 1000,
        'currency' => 'USD',
        'message' => 'Test donation message',
        'visibility' => Donation::VISIBILITY_PUBLIC,
        'status' => Donation::STATUS_PENDING,
    ]);

    expect($donation)->toBeInstanceOf(Donation::class)
        ->and($donation->campaign_id)->toBe($campaign->id)
        ->and($donation->donor_id)->toBe($donor->id)
        ->and($donation->amount)->toBe(1000)
        ->and($donation->currency)->toBe('USD')
        ->and($donation->message)->toBe('Test donation message')
        ->and($donation->visibility)->toBe(Donation::VISIBILITY_PUBLIC)
        ->and($donation->status)->toBe(Donation::STATUS_PENDING);
});

test('donation can be anonymous', function () {
    $donation = Donation::factory()->create([
        'visibility' => Donation::VISIBILITY_ANONYMOUS,
    ]);

    expect($donation->visibility)->toBe(Donation::VISIBILITY_ANONYMOUS);
});

test('donation can have different statuses', function () {
    $pendingDonation = Donation::factory()->create(['status' => Donation::STATUS_PENDING]);
    $completedDonation = Donation::factory()->create(['status' => Donation::STATUS_COMPLETED]);
    $failedDonation = Donation::factory()->create(['status' => Donation::STATUS_FAILED]);
    $refundedDonation = Donation::factory()->create(['status' => Donation::STATUS_REFUNDED]);

    expect($pendingDonation->status)->toBe(Donation::STATUS_PENDING)
        ->and($completedDonation->status)->toBe(Donation::STATUS_COMPLETED)
        ->and($failedDonation->status)->toBe(Donation::STATUS_FAILED)
        ->and($refundedDonation->status)->toBe(Donation::STATUS_REFUNDED);
});
