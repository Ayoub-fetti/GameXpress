<?php

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
        'token' => env('POSTMARK_TOKEN'),
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

    'stripe' => [
    'key' => env('pk_test_51R41RuHIFvEer26VwN4WvzcQthbdGfGHaUNYBpn3tJbLnjirTYh6KR53UWvKU0HI991vcAQt2YpLmGkrQNbTB0mk00aSMMIM1u'),
    'secret' => env('sk_test_51R41RuHIFvEer26VQ1G7WXYw6e7hszFa6uu15IPwCWK9M3i2w0EP68Z4ATWbFLBYk38R8IsRhbLB7XjWM1hKvzZb00kIn93CPd'),
],

];
