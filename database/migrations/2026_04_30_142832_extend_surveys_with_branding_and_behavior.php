<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('surveys', function (Blueprint $table) {
            $table->string('status')->default('draft')->after('audience')->index();
            $table->text('intro_message')->nullable()->after('description');
            $table->text('thank_you_message')->nullable()->after('intro_message');
            $table->string('success_redirect_url')->nullable()->after('thank_you_message');
            $table->string('brand_color', 16)->nullable()->after('success_redirect_url');
            $table->string('cover_image_path')->nullable()->after('brand_color');
            $table->boolean('show_progress_bar')->default(true)->after('cover_image_path');
            $table->string('email_notification_to')->nullable()->after('show_progress_bar');
            $table->boolean('notify_respondent')->default(false)->after('email_notification_to');
            $table->string('webhook_url')->nullable()->after('notify_respondent');
            $table->boolean('require_unique_email')->default(false)->after('webhook_url');
        });

        Schema::table('survey_questions', function (Blueprint $table) {
            $table->string('placeholder')->nullable()->after('help_text');
            $table->json('visible_when')->nullable()->after('options');
        });

        Schema::table('survey_responses', function (Blueprint $table) {
            $table->string('ip_address', 45)->nullable()->after('token');
            $table->string('user_agent')->nullable()->after('ip_address');
            $table->string('email')->nullable()->after('user_agent')->index();
        });
    }

    public function down(): void
    {
        Schema::table('surveys', function (Blueprint $table) {
            $table->dropColumn([
                'status',
                'intro_message',
                'thank_you_message',
                'success_redirect_url',
                'brand_color',
                'cover_image_path',
                'show_progress_bar',
                'email_notification_to',
                'notify_respondent',
                'webhook_url',
                'require_unique_email',
            ]);
        });

        Schema::table('survey_questions', function (Blueprint $table) {
            $table->dropColumn(['placeholder', 'visible_when']);
        });

        Schema::table('survey_responses', function (Blueprint $table) {
            $table->dropColumn(['ip_address', 'user_agent', 'email']);
        });
    }
};
