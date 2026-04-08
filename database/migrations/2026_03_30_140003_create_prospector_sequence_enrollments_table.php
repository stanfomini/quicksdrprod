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
        Schema::create('claimstaker_sequence_enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sequence_id')->constrained('claimstaker_sequences')->cascadeOnDelete();
            $table->foreignId('contact_id')->constrained('claimstaker_contacts')->cascadeOnDelete();
            $table->foreignId('current_step_id')->nullable()->constrained('claimstaker_sequence_steps')->nullOnDelete();
            $table->string('status')->default('active');
            $table->string('exit_reason')->nullable();
            $table->dateTime('next_execution_at')->nullable();
            $table->dateTime('enrolled_at');
            $table->dateTime('completed_at')->nullable();
            $table->foreignId('enrolled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['status', 'next_execution_at']);
            $table->unique(['sequence_id', 'contact_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('claimstaker_sequence_enrollments');
    }
};
