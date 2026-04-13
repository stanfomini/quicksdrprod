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
        Schema::table('claimstaker_agents', function (Blueprint $table) {
            $table->string('role_type', 20)->default('both')->after('role');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('claimstaker_agents', function (Blueprint $table) {
            $table->dropColumn('role_type');
        });
    }
};
