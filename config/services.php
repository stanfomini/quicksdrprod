<?php

use App\Models\User;

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'stripe' => [
        'model' => User::class,
        'key' => env('STRIPE_KEY'),
        'secret' => env('STRIPE_SECRET'),
        'webhook' => [
            'secret' => env('STRIPE_WEBHOOK_SECRET'),
            'tolerance' => env('STRIPE_WEBHOOK_TOLERANCE', 300),
        ],
        // Stripe Price IDs — set in .env for each tier's recurring + setup fees.
        'tiers' => [
            'tier1' => [
                'price' => env('STRIPE_PRICE_TIER1'),
            ],
            'tier2' => [
                'price' => env('STRIPE_PRICE_TIER2'),
                'setup' => env('STRIPE_PRICE_TIER2_SETUP'),
            ],
            'tier3' => [
                'price' => env('STRIPE_PRICE_TIER3'),
                'setup' => env('STRIPE_PRICE_TIER3_SETUP'),
            ],
        ],
    ],

    'forge' => [
        'token' => env('FORGE_API_TOKEN'),
        'do_credential_id' => env('FORGE_DO_CREDENTIAL_ID'),
        'default_region' => env('FORGE_DEFAULT_REGION', 'nyc3'),
    ],

    'cloudflare' => [
        'token' => env('CLOUDFLARE_API_TOKEN'),
        'zone_id' => env('CLOUDFLARE_ZONE_ID'),
    ],

    'partnerstack' => [
        'token' => env('PARTNERSTACK_API_TOKEN'),
        'public_key' => env('PARTNERSTACK_PUBLIC_KEY'),
    ],

    'lead_webhooks' => [
        'urls' => array_values(array_filter(array_map('trim', explode(',', (string) env('LEAD_WEBHOOK_URLS', ''))))),
    ],

    'google' => [
        'calendar_booking_url' => env('GOOGLE_CALENDAR_BOOKING_URL'),
    ],

];
