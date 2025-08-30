<?php

namespace Database\Factories;

use App\Models\Transaction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Transaction>
 */
class TransactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $status = fake()->randomElement([
            Transaction::STATUS_COMPLETED,
            Transaction::STATUS_FAILED,
            Transaction::STATUS_PENDING,
            Transaction::STATUS_PROCESSING,
            Transaction::STATUS_CANCELLED
        ]);

        return [
            'donation_id' => \App\Models\Donation::factory(),
            'transaction_reference' => 'TXN-' . fake()->unique()->numerify('##########'),
            'payment_gateway' => fake()->randomElement(['stripe', 'paypal', 'square', 'braintree']),
            'gateway_transaction_id' => fake()->optional()->regexify('[A-Za-z0-9]{20}'),
            'amount' => fake()->numberBetween(100, 100000), // Amount in cents
            'currency' => fake()->currencyCode(),
            'fee_amount' => fake()->numberBetween(0, 1000), // Fee in cents
            'status' => $status,
            'status_message' => $status === Transaction::STATUS_FAILED ? fake()->sentence() : null,
            'processed_at' => $status === Transaction::STATUS_COMPLETED ? fake()->dateTimeBetween('-30 days', 'now') : null,
        ];
    }
}
