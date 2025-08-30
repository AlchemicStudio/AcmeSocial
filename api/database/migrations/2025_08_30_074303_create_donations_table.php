<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('donations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('campaign_id')->index()->constrained('campaigns')->cascadeOnDelete();
            $table->foreignUuid('donor_id')->index()->constrained('users')->cascadeOnDelete();
            $table->integer('amount')->unsigned();
            $table->string('currency', 3)->default('USD');
            $table->integer('visibility')->unsigned()->default(\App\Models\Donation::VISIBILITY_PUBLIC);
            $table->text('message')->nullable();
            $table->integer('status')->unsigned()->default(\App\Models\Donation::STATUS_PENDING);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('donations');
    }
};
