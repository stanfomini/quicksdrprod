<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Host Application Configuration
    |--------------------------------------------------------------------------
    |
    | These are structural settings needed at boot time before the database
    | is available. All other settings are stored in the database and managed
    | through the Claimstaker settings UI.
    |
    */

    'user_model' => 'App\\Models\\User',
    'route_prefix' => 'claimstaker',

    /*
    |--------------------------------------------------------------------------
    | Dispositions
    |--------------------------------------------------------------------------
    |
    | Default disposition list. Can be customized by publishing this config.
    |
    */

    'dispositions' => [
        'booked' => ['label' => 'Booked Appointment'],
        'interested' => ['label' => 'Interested'],
        'callback' => ['label' => 'Callback Requested'],
        'not_interested' => ['label' => 'Not Interested'],
        'no_answer' => ['label' => 'No Answer', 'auto_sequence' => null],
        'voicemail' => ['label' => 'Left Voicemail', 'auto_sequence' => null],
        'wrong_number' => ['label' => 'Wrong Number'],
        'dnc' => ['label' => 'Do Not Call'],
        'busy' => ['label' => 'Busy'],
        'disconnected' => ['label' => 'Disconnected'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Webhook
    |--------------------------------------------------------------------------
    */

    'webhook' => [
        'csrf_exempt' => true,
    ],
];
