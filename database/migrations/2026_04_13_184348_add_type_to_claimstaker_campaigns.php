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
        Schema::table('claimstaker_campaigns', function (Blueprint $table) {
            $table->string('type', 20)->default('outbound')->after('status');
            $table->index(['company_id', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('claimstaker_campaigns', function (Blueprint $table) {
            $table->dropIndex(['company_id', 'type']);
            $table->dropColumn('type');
        });
    }
};
