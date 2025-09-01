<?php

declare(strict_types=1);

use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Transaction;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

test('authenticated user can list transactions', function () {
    $user = User::factory()->create();
    $transactions = Transaction::factory(3)->create();

    Sanctum::actingAs($user);

    $response = $this->getJson('/api/transactions');

    $response->assertStatus(200)
        ->assertJsonCount(3, 'data')
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
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
                    'created_at',
                    'updated_at'
                ]
            ]
        ]);
});

test('transactions are returned with correct data structure', function () {
    $user = User::factory()->create();
    $donation = Donation::factory()->create();
    $transaction = Transaction::factory()->create([
        'donation_id' => $donation->id,
        'transaction_reference' => 'TXN-2024-001',
        'payment_gateway' => 'stripe',
        'gateway_transaction_id' => 'pi_1234567890',
        'amount' => 10000,
        'currency' => 'USD',
        'fee_amount' => 300,
        'status' => Transaction::STATUS_COMPLETED,
        'status_message' => 'Payment successful',
        'processed_at' => '2024-01-15 10:00:00',
        'request_payload' => ['payment_method' => 'card'],
        'response_payload' => ['status' => 'succeeded'],
    ]);

    Sanctum::actingAs($user);

    $response = $this->getJson('/api/transactions');

    $response->assertStatus(200);

    $transactionData = collect($response->json('data'))
        ->firstWhere('id', $transaction->id);

    expect($transactionData)->not->toBeNull()
        ->and($transactionData['donation_id'])->toBe($donation->id)
        ->and($transactionData['transaction_reference'])->toBe('TXN-2024-001')
        ->and($transactionData['payment_gateway'])->toBe('stripe')
        ->and($transactionData['gateway_transaction_id'])->toBe('pi_1234567890')
        ->and($transactionData['amount'])->toBe(10000)
        ->and($transactionData['currency'])->toBe('USD')
        ->and($transactionData['fee_amount'])->toBe(300)
        ->and($transactionData['status'])->toBe(Transaction::STATUS_COMPLETED)
        ->and($transactionData['status_message'])->toBe('Payment successful')
        ->and($transactionData['request_payload'])->toBe(['payment_method' => 'card'])
        ->and($transactionData['response_payload'])->toBe(['status' => 'succeeded']);
});

test('transactions show different payment gateways correctly', function () {
    $user = User::factory()->create();

    $stripeTransaction = Transaction::factory()->create([
        'payment_gateway' => 'stripe',
        'gateway_transaction_id' => 'pi_stripe123',
    ]);

    $paypalTransaction = Transaction::factory()->create([
        'payment_gateway' => 'paypal',
        'gateway_transaction_id' => 'PAY-paypal456',
    ]);

    $razorpayTransaction = Transaction::factory()->create([
        'payment_gateway' => 'razorpay',
        'gateway_transaction_id' => 'pay_razorpay789',
    ]);

    Sanctum::actingAs($user);

    $response = $this->getJson('/api/transactions');

    $response->assertStatus(200);

    $transactionsData = collect($response->json('data'));

    $stripeData = $transactionsData->firstWhere('id', $stripeTransaction->id);
    $paypalData = $transactionsData->firstWhere('id', $paypalTransaction->id);
    $razorpayData = $transactionsData->firstWhere('id', $razorpayTransaction->id);

    expect($stripeData['payment_gateway'])->toBe('stripe')
        ->and($stripeData['gateway_transaction_id'])->toBe('pi_stripe123')
        ->and($paypalData['payment_gateway'])->toBe('paypal')
        ->and($paypalData['gateway_transaction_id'])->toBe('PAY-paypal456')
        ->and($razorpayData['payment_gateway'])->toBe('razorpay')
        ->and($razorpayData['gateway_transaction_id'])->toBe('pay_razorpay789');
});

test('transactions show different statuses correctly', function () {
    $user = User::factory()->create();

    $pendingTransaction = Transaction::factory()->create([
        'status' => Transaction::STATUS_PENDING,
        'status_message' => 'Payment pending'
    ]);

    $processingTransaction = Transaction::factory()->create([
        'status' => Transaction::STATUS_PROCESSING,
        'status_message' => 'Payment processing'
    ]);

    $completedTransaction = Transaction::factory()->create([
        'status' => Transaction::STATUS_COMPLETED,
        'status_message' => 'Payment completed'
    ]);

    $failedTransaction = Transaction::factory()->create([
        'status' => Transaction::STATUS_FAILED,
        'status_message' => 'Payment failed'
    ]);

    $cancelledTransaction = Transaction::factory()->create([
        'status' => Transaction::STATUS_CANCELLED,
        'status_message' => 'Payment cancelled'
    ]);

    Sanctum::actingAs($user);

    $response = $this->getJson('/api/transactions');

    $response->assertStatus(200);

    $transactionsData = collect($response->json('data'));

    expect($transactionsData->firstWhere('id', $pendingTransaction->id)['status'])->toBe(Transaction::STATUS_PENDING);
    expect($transactionsData->firstWhere('id', $processingTransaction->id)['status'])->toBe(Transaction::STATUS_PROCESSING);
    expect($transactionsData->firstWhere('id', $completedTransaction->id)['status'])->toBe(Transaction::STATUS_COMPLETED);
    expect($transactionsData->firstWhere('id', $failedTransaction->id)['status'])->toBe(Transaction::STATUS_FAILED);
    expect($transactionsData->firstWhere('id', $cancelledTransaction->id)['status'])->toBe(Transaction::STATUS_CANCELLED);
});

test('transactions with null processed_at are handled correctly', function () {
    $user = User::factory()->create();

    $unprocessedTransaction = Transaction::factory()->create([
        'processed_at' => null,
        'status' => Transaction::STATUS_PENDING,
    ]);

    $processedTransaction = Transaction::factory()->create([
        'processed_at' => '2024-01-15 10:00:00',
        'status' => Transaction::STATUS_COMPLETED,
    ]);

    Sanctum::actingAs($user);

    $response = $this->getJson('/api/transactions');

    $response->assertStatus(200);

    $transactionsData = collect($response->json('data'));

    $unprocessedData = $transactionsData->firstWhere('id', $unprocessedTransaction->id);
    $processedData = $transactionsData->firstWhere('id', $processedTransaction->id);

    expect($unprocessedData['processed_at'])->toBeNull()
        ->and($processedData['processed_at'])->not->toBeNull();
});

test('transactions with empty payloads are handled correctly', function () {
    $user = User::factory()->create();

    $transaction = Transaction::factory()->create([
        'request_payload' => [],
        'response_payload' => [],
    ]);

    Sanctum::actingAs($user);

    $response = $this->getJson('/api/transactions');

    $response->assertStatus(200);

    $transactionData = collect($response->json('data'))
        ->firstWhere('id', $transaction->id);

    expect($transactionData['request_payload'])->toBeArray()->toBeEmpty()
        ->and($transactionData['response_payload'])->toBeArray()->toBeEmpty();
});

test('transactions with complex payloads are handled correctly', function () {
    $user = User::factory()->create();

    $complexRequest = [
        'payment_method' => 'card',
        'card_details' => [
            'last4' => '4242',
            'exp_month' => 12,
            'exp_year' => 2025,
        ],
        'billing_address' => [
            'line1' => '123 Main St',
            'city' => 'San Francisco',
            'state' => 'CA',
            'postal_code' => '94105',
            'country' => 'US',
        ]
    ];

    $complexResponse = [
        'id' => 'pi_1234567890',
        'object' => 'payment_intent',
        'amount' => 10000,
        'currency' => 'usd',
        'status' => 'succeeded',
        'charges' => [
            'data' => [
                [
                    'id' => 'ch_1234567890',
                    'amount' => 10000,
                    'captured' => true,
                ]
            ]
        ]
    ];

    $transaction = Transaction::factory()->create([
        'request_payload' => $complexRequest,
        'response_payload' => $complexResponse,
    ]);

    Sanctum::actingAs($user);

    $response = $this->getJson('/api/transactions');

    $response->assertStatus(200);

    $transactionData = collect($response->json('data'))
        ->firstWhere('id', $transaction->id);

    expect($transactionData['request_payload'])->toBe($complexRequest)
        ->and($transactionData['response_payload'])->toBe($complexResponse);
});

test('transactions are ordered by creation date', function () {
    $user = User::factory()->create();

    $transaction1 = Transaction::factory()->create([
        'created_at' => '2024-01-01 10:00:00',
        'transaction_reference' => 'TXN-001',
    ]);

    $transaction2 = Transaction::factory()->create([
        'created_at' => '2024-01-02 10:00:00',
        'transaction_reference' => 'TXN-002',
    ]);

    $transaction3 = Transaction::factory()->create([
        'created_at' => '2024-01-03 10:00:00',
        'transaction_reference' => 'TXN-003',
    ]);

    Sanctum::actingAs($user);

    $response = $this->getJson('/api/transactions');

    $response->assertStatus(200);

    $transactionsData = $response->json('data');

    // Assuming transactions are ordered by latest first
    expect($transactionsData)->toHaveCount(3);
});

test('unauthenticated user cannot access transaction endpoints', function () {
    $response = $this->getJson('/api/transactions');

    $response->assertStatus(401)
        ->assertJson(['message' => 'Unauthenticated.']);
});

test('transaction endpoint returns empty array when no transactions exist', function () {
    $user = User::factory()->create();

    Sanctum::actingAs($user);

    $response = $this->getJson('/api/transactions');

    $response->assertStatus(200)
        ->assertJson(['data' => []]);
});

test('transactions include related donation information', function () {
    $user = User::factory()->create();
    $campaign = Campaign::factory()->create(['title' => 'Test Campaign']);
    $donation = Donation::factory()->create([
        'campaign_id' => $campaign->id,
        'amount' => 10000,
    ]);
    $transaction = Transaction::factory()->create([
        'donation_id' => $donation->id,
        'amount' => 10000,
    ]);

    Sanctum::actingAs($user);

    $response = $this->getJson('/api/transactions');

    $response->assertStatus(200);

    $transactionData = collect($response->json('data'))
        ->firstWhere('id', $transaction->id);

    expect($transactionData['donation_id'])->toBe($donation->id);
});
