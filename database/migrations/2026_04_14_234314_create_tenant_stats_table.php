<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_stats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();

            $table->timestamp('period_start')->index();
            $table->timestamp('period_end');

            $table->unsignedInteger('agent_count')->default(0);
            $table->unsignedInteger('call_count')->default(0);
            $table->unsignedInteger('total_talk_seconds')->default(0);
            $table->unsignedInteger('avg_call_duration_seconds')->default(0);

            // { "disposition_key" => count }
            $table->json('disposition_counts')->nullable();

            $table->timestamp('ingested_at');
            $table->timestamps();

            $table->unique(['tenant_id', 'period_start'], 'tenant_period_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_stats');
    }
};
