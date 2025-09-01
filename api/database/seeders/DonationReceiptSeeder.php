<?php

namespace Database\Seeders;

use App\Models\DonationReceipt;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DonationReceiptSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DonationReceipt::factory(10)->create();
    }
}
