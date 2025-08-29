<?php

declare(strict_types=1);

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
        Schema::create('campaigns', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('title');
            $table->text('description');
            $table->integer('goal_amount')->unsigned();
            $table->integer('current_amount')->unsigned()->default(0);
            $table->date('start_date');
            $table->date('end_date');
            $table->integer('status')->unsigned()->index()->default(0);
            $table->foreignUuid('creator_id')->index()->constrained('users')->cascadeOnDelete();
            $table->string('cover_image_url')->nullable();
            $table->string('video_url')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->foreignUuid('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('rejected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('rejected_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('campaigns');
    }
};
