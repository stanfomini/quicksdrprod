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
        Schema::create('claimstaker_rvm_drops', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('claimstaker_companies')->cascadeOnDelete();
            $table->foreignId('contact_id')->constrained('claimstaker_contacts')->cascadeOnDelete();
            $table->foreignId('template_id')->constrained('claimstaker_rvm_templates')->cascadeOnDelete();
            $table->foreignId('sequence_enrollment_id')->nullable()->constrained('claimstaker_sequence_enrollments')->nullOnDelete();
            $table->string('method'); // twilio_amd, slybroadcast
            $table->string('provider_id')->nullable();
            $table->string('status')->default('queued'); // queued, sent, delivered, failed
            $table->string('from_number', 20);
            $table->string('to_number', 20);
            $table->text('error')->nullable();
            $table->dateTime('sent_at')->nullable();
            $table->dateTime('delivered_at')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('claimstaker_rvm_drops');
    }
};
