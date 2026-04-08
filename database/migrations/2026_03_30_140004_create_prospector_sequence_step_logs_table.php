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
        Schema::create('claimstaker_sequence_step_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enrollment_id')->constrained('claimstaker_sequence_enrollments')->cascadeOnDelete();
            $table->foreignId('step_id')->constrained('claimstaker_sequence_steps')->cascadeOnDelete();
            $table->string('status')->default('pending');
            $table->json('result')->nullable();
            $table->dateTime('executed_at')->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('claimstaker_sequence_step_logs');
    }
};
