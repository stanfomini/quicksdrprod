<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('claimstaker_campaign_contact', function (Blueprint $table) {
            if (! Schema::hasColumn('claimstaker_campaign_contact', 'calls_today')) {
                $table->unsignedInteger('calls_today')->default(0)->after('attempts');
            }
            if (! Schema::hasColumn('claimstaker_campaign_contact', 'total_attempts')) {
                $table->unsignedInteger('total_attempts')->default(0)->after('calls_today');
            }
            if (! Schema::hasColumn('claimstaker_campaign_contact', 'entered_at')) {
                $table->timestamp('entered_at')->useCurrent()->after('total_attempts');
            }
            if (! Schema::hasColumn('claimstaker_campaign_contact', 'last_called_at')) {
                $table->timestamp('last_called_at')->nullable()->after('entered_at');
            }
            if (! Schema::hasColumn('claimstaker_campaign_contact', 'next_callable_at')) {
                $table->timestamp('next_callable_at')->nullable()->after('last_called_at');
            }
            if (! Schema::hasColumn('claimstaker_campaign_contact', 'exhausted_at')) {
                $table->timestamp('exhausted_at')->nullable()->after('next_callable_at');
            }
        });

        // Add index separately so we can guard it
        try {
            Schema::table('claimstaker_campaign_contact', function (Blueprint $table) {
                $table->index(['campaign_id', 'status', 'calls_today', 'next_callable_at'], 'cc_queue_idx');
            });
        } catch (\Throwable) {
            // Index already exists
        }
    }

    public function down(): void
    {
        Schema::table('claimstaker_campaign_contact', function (Blueprint $table) {
            try {
                $table->dropIndex('cc_queue_idx');
            } catch (\Throwable) {
                // Index didn't exist
            }

            $columns = ['calls_today', 'total_attempts', 'entered_at', 'last_called_at', 'next_callable_at', 'exhausted_at'];
            foreach ($columns as $col) {
                if (Schema::hasColumn('claimstaker_campaign_contact', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
