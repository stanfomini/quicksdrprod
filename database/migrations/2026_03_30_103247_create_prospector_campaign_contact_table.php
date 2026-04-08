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
        Schema::create('claimstaker_campaign_contact', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained('claimstaker_campaigns')->cascadeOnDelete();
            $table->foreignId('contact_id')->constrained('claimstaker_contacts')->cascadeOnDelete();
            $table->string('status')->default('queued');
            $table->unsignedInteger('sort_order')->default(0);
            $table->unsignedInteger('attempts')->default(0);
            $table->timestamp('last_attempted_at')->nullable();
            $table->string('last_disposition')->nullable();
            $table->timestamp('added_at')->useCurrent();

            $table->unique(['campaign_id', 'contact_id']);
            $table->index(['campaign_id', 'status', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('claimstaker_campaign_contact');
    }
};
