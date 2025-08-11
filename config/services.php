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

    'resend' => [
        'key' => env('RESEND_KEY'),
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

    'elasticsearch' => [
        'hosts' => [
            env('ELASTICSEARCH_HOST', '192.168.0.224:9200'),
        ],
        'username' => env('ELASTICSEARCH_USER', 'elastic'),
        'password' => env('ELASTICSEARCH_PASS', 'dmacq@2025'),
        'index_prefix' => env('ELASTICSEARCH_INDEX_PREFIX', 'laravel_scout'),
        'number_of_shards' => env('ELASTICSEARCH_NUMBER_OF_SHARDS', 1),
        'number_of_replicas' => env('ELASTICSEARCH_NUMBER_OF_REPLICAS', 0),
        'ssl_verification' => env('ELASTICSEARCH_SSL_VERIFICATION', false),
    ],

];
