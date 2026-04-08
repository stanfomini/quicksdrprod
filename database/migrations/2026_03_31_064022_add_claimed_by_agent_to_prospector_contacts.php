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
        if (Schema::hasColumn('claimstaker_contacts', 'claimed_by_agent_id')) {
            return;
        }

        Schema::table('claimstaker_contacts', function (Blueprint $table) {
            // Use 'after' only if has_unread_sms exists; otherwise just append.
            if (Schema::hasColumn('claimstaker_contacts', 'has_unread_sms')) {
                $table->foreignId('claimed_by_agent_id')->nullable()->after('has_unread_sms')
                    ->constrained('claimstaker_agents')->nullOnDelete();
            } else {
                $table->foreignId('claimed_by_agent_id')->nullable()
                    ->constrained('claimstaker_agents')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('claimstaker_contacts', 'claimed_by_agent_id')) {
            return;
        }

        Schema::table('claimstaker_contacts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('claimed_by_agent_id');
        });
    }
};
