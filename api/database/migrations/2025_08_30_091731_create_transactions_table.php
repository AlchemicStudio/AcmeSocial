<?php

use App\Models\Transaction;
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
        Schema::create('transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->timestamps();
            $table->foreignUuid('donation_id')->index()->constrained('donations')->cascadeOnDelete();
            $table->string('transaction_reference')->unique();
            $table->string('payment_gateway');
            $table->string('gateway_transaction_id')->nullable();
            $table->integer('amount')->unsigned();
            $table->string('currency', 3)->default('USD');
            $table->integer('fee_amount')->unsigned()->default(0);
            $table->integer('status')->unsigned()->default(Transaction::STATUS_PENDING);
            $table->text('status_message')->nullable();
            $table->timestamp('processed_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
