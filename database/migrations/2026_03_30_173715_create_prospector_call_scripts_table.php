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
        Schema::create('claimstaker_call_scripts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained('claimstaker_companies')->cascadeOnDelete();
            $table->foreignId('campaign_id')->nullable()->constrained('claimstaker_campaigns')->cascadeOnDelete();
            $table->string('name');
            $table->longText('body');
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('claimstaker_call_scripts');
    }
};
