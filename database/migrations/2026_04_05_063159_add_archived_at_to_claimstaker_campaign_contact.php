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
        if (Schema::hasColumn('claimstaker_campaign_contact', 'archived_at')) {
            return;
        }

        Schema::table('claimstaker_campaign_contact', function (Blueprint $table) {
            if (Schema::hasColumn('claimstaker_campaign_contact', 'exhausted_at')) {
                $table->timestamp('archived_at')->nullable()->after('exhausted_at');
            } else {
                $table->timestamp('archived_at')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasColumn('claimstaker_campaign_contact', 'archived_at')) {
            return;
        }

        Schema::table('claimstaker_campaign_contact', function (Blueprint $table) {
            $table->dropColumn('archived_at');
        });
    }
};
