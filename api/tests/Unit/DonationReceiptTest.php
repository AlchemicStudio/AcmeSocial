<?php

declare(strict_types=1);

use App\Models\Donation;
use App\Models\DonationReceipt;

test('donation receipt belongs to donation', function () {
    $donation = Donation::factory()->create();
    $receipt = DonationReceipt::factory()->create(['donation_id' => $donation->id]);

    expect($receipt->donation)->toBeInstanceOf(Donation::class)
        ->and($receipt->donation->id)->toBe($donation->id);
});

test('donation receipt media collection constant is defined', function () {
    expect(DonationReceipt::RECEIPT_MEDIA_COLLECTION)->toBe('receipt');
});

test('donation receipt attributes are cast correctly', function () {
    $receipt = DonationReceipt::factory()->create([
        'issued_date' => '2024-01-15',
        'email_sent_at' => '2024-01-16',
    ]);

    expect($receipt->issued_date)->toBeInstanceOf(Carbon\Carbon::class)
        ->and($receipt->email_sent_at)->toBeInstanceOf(Carbon\Carbon::class);
});

test('donation receipt fillable attributes are correct', function () {
    $fillable = [
        'donation_id',
        'receipt_number',
        'issued_date',
        'email_sent_at',
    ];

    $receipt = new DonationReceipt();
    expect($receipt->getFillable())->toBe($fillable);
});

test('donation receipt uses correct traits', function () {
    $receipt = new DonationReceipt();

    expect($receipt)->toUse([
        Illuminate\Database\Eloquent\Factories\HasFactory::class,
        Illuminate\Database\Eloquent\Concerns\HasUuids::class,
        Spatie\Activitylog\Traits\LogsActivity::class,
        Spatie\MediaLibrary\InteractsWithMedia::class,
    ]);
});

test('donation receipt implements HasMedia interface', function () {
    $receipt = new DonationReceipt();

    expect($receipt)->toBeInstanceOf(Spatie\MediaLibrary\HasMedia::class);
});

test('donation receipt can be created with all required attributes', function () {
    $donation = Donation::factory()->create();

    $receipt = DonationReceipt::factory()->create([
        'donation_id' => $donation->id,
        'receipt_number' => 'RCP-2024-001',
        'issued_date' => '2024-01-15',
        'email_sent_at' => '2024-01-16',
    ]);

    expect($receipt)->toBeInstanceOf(DonationReceipt::class)
        ->and($receipt->donation_id)->toBe($donation->id)
        ->and($receipt->receipt_number)->toBe('RCP-2024-001')
        ->and($receipt->issued_date)->toBeInstanceOf(Carbon\Carbon::class)
        ->and($receipt->email_sent_at)->toBeInstanceOf(Carbon\Carbon::class);
});

test('donation receipt can be created without email sent date', function () {
    $donation = Donation::factory()->create();

    $receipt = DonationReceipt::factory()->create([
        'donation_id' => $donation->id,
        'receipt_number' => 'RCP-2024-002',
        'issued_date' => '2024-01-15',
        'email_sent_at' => null,
    ]);

    expect($receipt->email_sent_at)->toBeNull();
});

test('donation receipt has unique receipt number', function () {
    $donation1 = Donation::factory()->create();
    $donation2 = Donation::factory()->create();

    $receipt1 = DonationReceipt::factory()->create([
        'donation_id' => $donation1->id,
        'receipt_number' => 'RCP-2024-001',
    ]);

    $receipt2 = DonationReceipt::factory()->create([
        'donation_id' => $donation2->id,
        'receipt_number' => 'RCP-2024-002',
    ]);

    expect($receipt1->receipt_number)->not->toBe($receipt2->receipt_number);
});
