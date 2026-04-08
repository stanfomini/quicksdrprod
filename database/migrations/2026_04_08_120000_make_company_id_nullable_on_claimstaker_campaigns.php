<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Allow `claimstaker_campaigns.company_id` to be NULL so a campaign can be
     * scoped to "all companies" instead of a single one. The Livewire campaigns
     * page (`resources/views/pages/⚡campaigns.blade.php`) already supports a
     * `companyScope = 'all'` mode that inserts NULL, but the original
     * `create_prospector_campaigns_table` migration defined the column as
     * NOT NULL — causing a 23000 integrity-constraint error on save.
     *
     * Also relax the foreign key from cascadeOnDelete to nullOnDelete so that
     * deleting a company turns its single-company campaigns into all-company
     * campaigns rather than deleting them along with the company.
     */
    public function up(): void
    {
        Schema::table('claimstaker_campaigns', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->unsignedBigInteger('company_id')->nullable()->change();
            $table->foreign('company_id')
                ->references('id')
                ->on('claimstaker_companies')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        // Restore NOT NULL by reassigning any orphan rows to the first
        // available company. If no company exists, the change() call will
        // fail — which is the correct outcome (you cannot restore NOT NULL
        // on a column with NULL rows and no fallback).
        $fallbackId = DB::table('claimstaker_companies')->orderBy('id')->value('id');
        if ($fallbackId) {
            DB::table('claimstaker_campaigns')
                ->whereNull('company_id')
                ->update(['company_id' => $fallbackId]);
        }

        Schema::table('claimstaker_campaigns', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->unsignedBigInteger('company_id')->nullable(false)->change();
            $table->foreign('company_id')
                ->references('id')
                ->on('claimstaker_companies')
                ->cascadeOnDelete();
        });
    }
};
