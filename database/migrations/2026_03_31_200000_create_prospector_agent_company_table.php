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
        Schema::create('claimstaker_agent_company', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_id')->constrained('claimstaker_agents')->cascadeOnDelete();
            $table->foreignId('company_id')->constrained('claimstaker_companies')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['agent_id', 'company_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('claimstaker_agent_company');
    }
};
