<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Campaign;
use App\Models\Donation;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DonationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Ensure we have campaigns and users to work with
        $campaigns = Campaign::limit(10)->get();
        $users = User::limit(20)->get();

        if ($campaigns->isEmpty()) {
            $campaigns = Campaign::factory(5)->create();
        }

        if ($users->isEmpty()) {
            $users = User::factory(10)->create();
        }

        // Create various donation scenarios
        foreach ($campaigns as $campaign) {
            // Create multiple donations for each campaign
            $donationCount = fake()->numberBetween(20, 1000);

            for ($i = 0; $i < $donationCount; $i++) {
                $donor = $users->random();

                // Create different types of donations
                $donationType = fake()->randomElement([
                    'completed_public',
                    'completed_private',
                    'completed_anonymous',
                    'pending_public',
                    'pending_private',
                    'failed',
                    'large_completed',
                    'small_completed',
                ]);

                match ($donationType) {
                    'completed_public' => Donation::factory()
                        ->for($campaign)
                        ->for($donor, 'donor')
                        ->completed()
                        ->public()
                        ->create([
                            'message' => fake()->optional(0.7)->sentence(),
                        ]),

                    'completed_private' => Donation::factory()
                        ->for($campaign)
                        ->for($donor, 'donor')
                        ->completed()
                        ->private()
                        ->create([
                            'message' => fake()->optional(0.4)->sentence(),
                        ]),

                    'completed_anonymous' => Donation::factory()
                        ->for($campaign)
                        ->for($donor, 'donor')
                        ->completed()
                        ->anonymous()
                        ->public()
                        ->create(),

                    'pending_public' => Donation::factory()
                        ->for($campaign)
                        ->for($donor, 'donor')
                        ->pending()
                        ->public()
                        ->create([
                            'message' => fake()->optional(0.5)->sentence(),
                        ]),

                    'pending_private' => Donation::factory()
                        ->for($campaign)
                        ->for($donor, 'donor')
                        ->pending()
                        ->private()
                        ->create(),

                    'failed' => Donation::factory()
                        ->for($campaign)
                        ->for($donor, 'donor')
                        ->failed()
                        ->create([
                            'message' => fake()->optional(0.3)->sentence(),
                        ]),

                    'large_completed' => Donation::factory()
                        ->for($campaign)
                        ->for($donor, 'donor')
                        ->completed()
                        ->large()
                        ->public()
                        ->create([
                            'message' => fake()->optional(0.8)->paragraph(),
                        ]),

                    'small_completed' => Donation::factory()
                        ->for($campaign)
                        ->for($donor, 'donor')
                        ->completed()
                        ->small()
                        ->create([
                            'message' => fake()->optional(0.4)->words(3, true),
                        ]),
                };
            }
        }

        // Create some refunded donations (rare scenarios)
        Donation::factory(3)
            ->refunded()
            ->recycle($campaigns)
            ->recycle($users)
            ->create();

        // Create donations with specific currencies
        $currencies = ['EUR', 'GBP', 'CAD'];
        foreach ($currencies as $currency) {
            Donation::factory(2)
                ->completed()
                ->recycle($campaigns)
                ->recycle($users)
                ->create(['currency' => $currency]);
        }
    }
}
