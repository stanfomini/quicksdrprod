<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('tenants')) {
            return;
        }

        if (! Schema::hasColumn('tenants', 'dry_run')) {
            Schema::table('tenants', function (Blueprint $table): void {
                $table->boolean('dry_run')->default(false)->after('tier');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('tenants', 'dry_run')) {
            Schema::table('tenants', function (Blueprint $table): void {
                $table->dropColumn('dry_run');
            });
        }
    }
};
