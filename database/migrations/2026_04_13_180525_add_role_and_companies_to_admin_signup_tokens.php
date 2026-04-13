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
        Schema::table('admin_signup_tokens', function (Blueprint $table) {
            $table->string('role', 20)->nullable()->after('email_hint');
            $table->json('company_ids')->nullable()->after('role');
        });
    }

    public function down(): void
    {
        Schema::table('admin_signup_tokens', function (Blueprint $table) {
            $table->dropColumn(['role', 'company_ids']);
        });
    }
};
