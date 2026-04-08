<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Fresh installs already have twilio_account_id nullable from the
     * updated create migration. This migration is for databases that
     * ran the original NOT NULL version of the create migration —
     * it drops the NOT NULL constraint and swaps cascade → null on delete.
     *
     * Why nullable:
     *   1. First-run setup creates a "Default Company" before any Twilio
     *      account exists, so the foreign key must accept NULL.
     *   2. Multi-company installs may have companies without a linked
     *      Twilio account yet (agents, integrations-only setups, etc.).
     *   3. PostgreSQL enforces NOT NULL strictly at INSERT time; MySQL
     *      would too in strict mode. MySQL silently accepting NULLs on
     *      dev was masking this issue.
     */
    public function up(): void
    {
        if (! Schema::hasTable('claimstaker_companies')) {
            return;
        }

        // Requires doctrine/dbal on Laravel <= 10 for change(), but
        // Laravel 11+ has native ALTER support without it.
        Schema::table('claimstaker_companies', function (Blueprint $table) {
            $table->foreignId('twilio_account_id')->nullable()->change();
        });

        // Also swap the FK delete behavior from cascade to set null,
        // so deleting a Twilio account doesn't nuke every company using it.
        // This has to be done as a raw drop + re-add because Laravel's
        // schema builder doesn't have a "modify FK" operation.
        try {
            Schema::table('claimstaker_companies', function (Blueprint $table) {
                $table->dropForeign(['twilio_account_id']);
            });

            Schema::table('claimstaker_companies', function (Blueprint $table) {
                $table->foreign('twilio_account_id')
                    ->references('id')
                    ->on('claimstaker_twilio_accounts')
                    ->nullOnDelete();
            });
        } catch (\Throwable $e) {
            // Foreign key may not exist or may have a different name;
            // the nullability change above is the critical part, so
            // we silently ignore FK errors here.
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('claimstaker_companies')) {
            return;
        }

        // Revert is lossy — if any rows have NULL twilio_account_id,
        // a NOT NULL rollback would fail. Skip the rollback safely.
        if (DB::table('claimstaker_companies')->whereNull('twilio_account_id')->exists()) {
            throw new \RuntimeException(
                'Cannot roll back: some companies have NULL twilio_account_id. '
                .'Assign Twilio accounts to all companies first, then retry.'
            );
        }

        Schema::table('claimstaker_companies', function (Blueprint $table) {
            $table->foreignId('twilio_account_id')->nullable(false)->change();
        });
    }
};
