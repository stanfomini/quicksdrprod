<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('claimstaker_campaign_contact', function (Blueprint $table) {
            if (! Schema::hasColumn('claimstaker_campaign_contact', 'is_priority')) {
                $table->boolean('is_priority')->default(false)->after('status');
            }
            if (! Schema::hasColumn('claimstaker_campaign_contact', 'priority_reason')) {
                $table->string('priority_reason')->nullable()->after('is_priority');
            }
            if (! Schema::hasColumn('claimstaker_campaign_contact', 'claimed_by_agent_id')) {
                $table->foreignId('claimed_by_agent_id')->nullable()->after('priority_reason')
                    ->constrained('claimstaker_agents')->nullOnDelete();
            }
        });

        Schema::table('claimstaker_contacts', function (Blueprint $table) {
            if (! Schema::hasColumn('claimstaker_contacts', 'has_unread_sms')) {
                $table->boolean('has_unread_sms')->default(false)->after('total_sms');
            }
        });
    }

    public function down(): void
    {
        Schema::table('claimstaker_campaign_contact', function (Blueprint $table) {
            if (Schema::hasColumn('claimstaker_campaign_contact', 'claimed_by_agent_id')) {
                $table->dropConstrainedForeignId('claimed_by_agent_id');
            }
            $table->dropColumn(['is_priority', 'priority_reason']);
        });

        Schema::table('claimstaker_contacts', function (Blueprint $table) {
            $table->dropColumn('has_unread_sms');
        });
    }
};
