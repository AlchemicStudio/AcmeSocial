<?php

declare(strict_types=1);

use App\Models\Donation;
use App\Models\Transaction;

test('transaction belongs to donation', function () {
    $donation = Donation::factory()->create();
    $transaction = Transaction::factory()->create(['donation_id' => $donation->id]);

    expect($transaction->donation)->toBeInstanceOf(Donation::class)
        ->and($transaction->donation->id)->toBe($donation->id);
});

test('transaction status constants are defined', function () {
    expect(Transaction::STATUS_PENDING)->toBe(0)
        ->and(Transaction::STATUS_PROCESSING)->toBe(1)
        ->and(Transaction::STATUS_COMPLETED)->toBe(2)
        ->and(Transaction::STATUS_FAILED)->toBe(3)
        ->and(Transaction::STATUS_CANCELLED)->toBe(4);
});

test('transaction attributes are cast correctly', function () {
    $requestPayload = ['key' => 'value', 'amount' => 1000];
    $responsePayload = ['status' => 'success', 'transaction_id' => '12345'];

    $transaction = Transaction::factory()->create([
        'amount' => '1000',
        'fee_amount' => '50',
        'processed_at' => '2024-01-15 10:00:00',
        'request_payload' => $requestPayload,
        'response_payload' => $responsePayload,
    ]);

    expect($transaction->amount)->toBeInt()
        ->and($transaction->fee_amount)->toBeInt()
        ->and($transaction->processed_at)->toBeInstanceOf(Carbon\Carbon::class)
        ->and($transaction->request_payload)->toBeArray()
        ->and($transaction->response_payload)->toBeArray()
        ->and($transaction->request_payload)->toBe($requestPayload)
        ->and($transaction->response_payload)->toBe($responsePayload);
});

test('transaction fillable attributes are correct', function () {
    $fillable = [
        'donation_id',
        'transaction_reference',
        'payment_gateway',
        'gateway_transaction_id',
        'amount',
        'currency',
        'fee_amount',
        'status',
        'status_message',
        'processed_at',
        'request_payload',
        'response_payload',
    ];

    $transaction = new Transaction();
    expect($transaction->getFillable())->toBe($fillable);
});

test('transaction uses correct traits', function () {
    $transaction = new Transaction();

    expect($transaction)->toUse([
        Illuminate\Database\Eloquent\Factories\HasFactory::class,
        Illuminate\Database\Eloquent\Concerns\HasUuids::class,
        Spatie\Activitylog\Traits\LogsActivity::class,
    ]);
});

test('transaction can be created with all required attributes', function () {
    $donation = Donation::factory()->create();
    $requestPayload = ['payment_method' => 'credit_card'];
    $responsePayload = ['gateway_response' => 'approved'];

    $transaction = Transaction::factory()->create([
        'donation_id' => $donation->id,
        'transaction_reference' => 'TXN-2024-001',
        'payment_gateway' => 'stripe',
        'gateway_transaction_id' => 'pi_1234567890',
        'amount' => 1000,
        'currency' => 'USD',
        'fee_amount' => 50,
        'status' => Transaction::STATUS_PENDING,
        'status_message' => 'Payment pending',
        'processed_at' => '2024-01-15 10:00:00',
        'request_payload' => $requestPayload,
        'response_payload' => $responsePayload,
    ]);

    expect($transaction)->toBeInstanceOf(Transaction::class)
        ->and($transaction->donation_id)->toBe($donation->id)
        ->and($transaction->transaction_reference)->toBe('TXN-2024-001')
        ->and($transaction->payment_gateway)->toBe('stripe')
        ->and($transaction->gateway_transaction_id)->toBe('pi_1234567890')
        ->and($transaction->amount)->toBe(1000)
        ->and($transaction->currency)->toBe('USD')
        ->and($transaction->fee_amount)->toBe(50)
        ->and($transaction->status)->toBe(Transaction::STATUS_PENDING)
        ->and($transaction->status_message)->toBe('Payment pending')
        ->and($transaction->processed_at)->toBeInstanceOf(Carbon\Carbon::class)
        ->and($transaction->request_payload)->toBe($requestPayload)
        ->and($transaction->response_payload)->toBe($responsePayload);
});

test('transaction can have different statuses', function () {
    $pendingTransaction = Transaction::factory()->create(['status' => Transaction::STATUS_PENDING]);
    $processingTransaction = Transaction::factory()->create(['status' => Transaction::STATUS_PROCESSING]);
    $completedTransaction = Transaction::factory()->create(['status' => Transaction::STATUS_COMPLETED]);
    $failedTransaction = Transaction::factory()->create(['status' => Transaction::STATUS_FAILED]);
    $cancelledTransaction = Transaction::factory()->create(['status' => Transaction::STATUS_CANCELLED]);

    expect($pendingTransaction->status)->toBe(Transaction::STATUS_PENDING)
        ->and($processingTransaction->status)->toBe(Transaction::STATUS_PROCESSING)
        ->and($completedTransaction->status)->toBe(Transaction::STATUS_COMPLETED)
        ->and($failedTransaction->status)->toBe(Transaction::STATUS_FAILED)
        ->and($cancelledTransaction->status)->toBe(Transaction::STATUS_CANCELLED);
});

test('transaction can handle empty payloads', function () {
    $transaction = Transaction::factory()->create([
        'request_payload' => [],
        'response_payload' => [],
    ]);

    expect($transaction->request_payload)->toBeArray()->toBeEmpty()
        ->and($transaction->response_payload)->toBeArray()->toBeEmpty();
});

test('transaction can handle null processed_at', function () {
    $transaction = Transaction::factory()->create([
        'processed_at' => null,
    ]);

    expect($transaction->processed_at)->toBeNull();
});

test('transaction can have different payment gateways', function () {
    $stripeTransaction = Transaction::factory()->create(['payment_gateway' => 'stripe']);
    $paypalTransaction = Transaction::factory()->create(['payment_gateway' => 'paypal']);
    $razorpayTransaction = Transaction::factory()->create(['payment_gateway' => 'razorpay']);

    expect($stripeTransaction->payment_gateway)->toBe('stripe')
        ->and($paypalTransaction->payment_gateway)->toBe('paypal')
        ->and($razorpayTransaction->payment_gateway)->toBe('razorpay');
});
