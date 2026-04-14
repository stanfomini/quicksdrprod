<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();

            // Core identity
            $table->string('business_name');
            $table->string('admin_name');
            $table->string('admin_email');
            $table->string('admin_phone')->nullable();
            $table->string('company_website')->nullable();
            $table->string('role_title')->nullable();
            $table->string('subdomain')->unique();

            // Tier
            $table->string('tier')->default('tier1');

            // Stripe
            $table->string('stripe_customer_id')->nullable()->index();
            $table->string('stripe_subscription_id')->nullable();

            // PartnerStack attribution
            $table->string('partnerstack_partner_key')->nullable();

            // Forge artifacts (captured during provisioning for idempotency)
            $table->unsignedBigInteger('forge_server_id')->nullable();
            $table->unsignedBigInteger('forge_site_id')->nullable();
            $table->unsignedBigInteger('forge_ws_site_id')->nullable();
            $table->unsignedBigInteger('forge_database_id')->nullable();
            $table->string('server_ip', 45)->nullable();

            // Cloudflare artifacts
            $table->string('cloudflare_record_id')->nullable();
            $table->string('cloudflare_ws_record_id')->nullable();

            // Encrypted secrets we generate + store so we can resume/retry idempotently
            $table->text('app_key')->nullable();           // Laravel APP_KEY for the tenant
            $table->text('reverb_app_id')->nullable();
            $table->text('reverb_app_key')->nullable();
            $table->text('reverb_app_secret')->nullable();
            $table->text('stats_hmac_key')->nullable();    // shared secret for stats ingest
            $table->text('db_password')->nullable();       // Postgres user password we hand to Forge

            // Admin onboarding
            $table->text('admin_signup_url')->nullable(); // tokenized URL emailed to admin

            // Status state machine
            $table->string('status')->default('pending')->index();
            $table->unsignedSmallInteger('status_step')->default(0);
            $table->json('status_detail')->nullable();

            // Lifecycle
            $table->timestamp('provisioned_at')->nullable();
            $table->timestamp('export_only_until')->nullable();
            $table->timestamp('destroyed_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
