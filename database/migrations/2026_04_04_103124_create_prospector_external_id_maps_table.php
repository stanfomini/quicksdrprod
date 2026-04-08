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
        Schema::create('claimstaker_external_id_maps', function (Blueprint $table) {
            $table->id();
            $table->string('claimstaker_type', 50); // 'company' or 'contact'
            $table->unsignedBigInteger('claimstaker_id');
            $table->string('external_model'); // Fully qualified host model class
            $table->unsignedBigInteger('external_id'); // Host model's ID
            $table->string('sync_direction', 20)->default('both'); // 'import', 'export', 'both'
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();

            $table->unique(['claimstaker_type', 'external_model', 'external_id'], 'ext_map_unique');
            $table->index(['claimstaker_type', 'claimstaker_id'], 'ext_map_claimstaker');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('claimstaker_external_id_maps');
    }
};
