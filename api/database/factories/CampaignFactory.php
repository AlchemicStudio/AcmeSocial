<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Campaign;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Campaign>
 */
class CampaignFactory extends Factory
{
    protected $model = Campaign::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $start = $this->faker->dateTimeBetween('-1 month', '+1 month');
        $end = (clone $start)->modify('+'.random_int(10, 90).' days');
        $goal = $this->faker->numberBetween(10_00, 1_000_00); // cents
        $current = $this->faker->numberBetween(0, $goal);
        $status = $this->faker->randomElement([
            Campaign::STATUS_DRAFT,
            Campaign::STATUS_PENDING,
            Campaign::STATUS_APPROVED,
            Campaign::STATUS_REJECTED,
            Campaign::STATUS_COMPLETED,
            Campaign::STATUS_CANCELLED,
        ]);

        return [
            'title' => $this->faker->sentence(6),
            'description' => $this->faker->paragraphs(3, true),
            'goal_amount' => $goal,
            'current_amount' => $current,
            'start_date' => $start->format('Y-m-d'),
            'end_date' => $end->format('Y-m-d'),
            'status' => $status,
            'creator_id' => User::factory(),
            'cover_image_url' => $this->faker->optional()->imageUrl(1200, 630, 'campaign', true),
            'video_url' => $this->faker->optional()->url(),
            'approved_at' => in_array($status, [Campaign::STATUS_APPROVED, Campaign::STATUS_COMPLETED], true) ? $this->faker->dateTimeBetween('-1 month', 'now') : null,
            'approved_by' => in_array($status, [Campaign::STATUS_APPROVED, Campaign::STATUS_COMPLETED], true) ? User::factory() : null,
            'rejected_by' => $status === Campaign::STATUS_REJECTED ? User::factory() : null,
            'rejected_reason' => $status === Campaign::STATUS_REJECTED ? $this->faker->sentence() : null,
        ];
    }
}
