<?php

namespace Database\Factories;

use App\Models\Donation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DonationReceipt>
 */
class DonationReceiptFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'donation_id' => Donation::factory(),
            'receipt_number' => 'RCP-' . $this->faker->unique()->numerify('######'),
            'issued_date' => $this->faker->dateTimeBetween('-1 year', 'now')->format('Y-m-d'),
            'file_url' => $this->faker->optional(0.8)->url(),
            'email_sent' => $this->faker->boolean(70),
        ];
    }
}
