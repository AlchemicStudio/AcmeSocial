<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Campaign;
use App\Models\Donation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Donation>
 */
class DonationFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = Donation::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $anonymous = $this->faker->boolean(20); // 20% chance of being anonymous
        $visibility = $this->faker->randomElement([
            Donation::VISIBILITY_PUBLIC,
            Donation::VISIBILITY_ANONYMOUS,
        ]);

        return [
            'campaign_id' => Campaign::factory(),
            'donor_id' => User::factory(),
            'amount' => $this->faker->numberBetween(500, 50000), // $5 to $500 in cents
            'currency' => $this->faker->randomElement(['USD', 'EUR', 'GBP', 'CAD']),
            'message' => $anonymous && $this->faker->boolean(70)
                ? null
                : $this->faker->optional(0.6)->sentence(),
            'visibility' => $visibility,
            'status' => $this->faker->randomElement([
                Donation::STATUS_PENDING,
                Donation::STATUS_COMPLETED,
                Donation::STATUS_FAILED,
                Donation::STATUS_REFUNDED,
            ]),

        ];
    }

    /**
     * Indicate that the donation is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Donation::STATUS_PENDING,
        ]);
    }

    /**
     * Indicate that the donation is completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Donation::STATUS_COMPLETED,
        ]);
    }

    /**
     * Indicate that the donation is failed.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Donation::STATUS_FAILED,
        ]);
    }

    /**
     * Indicate that the donation is refunded.
     */
    public function refunded(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Donation::STATUS_REFUNDED,
        ]);
    }

    /**
     * Indicate that the donation is anonymous.
     */
    public function anonymous(): static
    {
        return $this->state(fn (array $attributes) => [
            'visibility' => Donation::VISIBILITY_ANONYMOUS,
            'message' => null, // Anonymous donations typically don't have messages
        ]);
    }

    /**
     * Indicate that the donation is public.
     */
    public function public(): static
    {
        return $this->state(fn (array $attributes) => [
            'visibility' => Donation::VISIBILITY_PUBLIC,
        ]);
    }

    /**
     * Indicate that the donation is private.
     */
    public function private(): static
    {
        return $this->state(fn (array $attributes) => [
            'visibility' => Donation::VISIBILITY_ANONYMOUS,
        ]);
    }

    /**
     * Indicate that the donation has a large amount.
     */
    public function large(): static
    {
        return $this->state(fn (array $attributes) => [
            'amount' => $this->faker->numberBetween(10000, 100000), // $100 to $1000
        ]);
    }

    /**
     * Indicate that the donation has a small amount.
     */
    public function small(): static
    {
        return $this->state(fn (array $attributes) => [
            'amount' => $this->faker->numberBetween(100, 2000), // $1 to $20
        ]);
    }
}
