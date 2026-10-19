<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
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

    'germini' => [
        'key' => env('GERMINI_API_KEY'),
        'model' => env('GERMINI_MODEL', 'gemini-3.8-flash'),
    ],

    'openrouter' => [
        'key' => env('OPEN_ROUTER_API_KEY'),
        'model' => env('OPEN_ROUTER_MODEL', 'nvidia/nemotron-3-ultra-550b-a55b:free'),
        'max_tokens' => (int) env('OPEN_ROUTER_MAX_TOKENS', 4096),
    ],

    'cloudinary' => [
        'cloud_name' => env('CLOUDINARY_CLOUD_NAME'),
        'api_key' => env('CLOUDINARY_API_KEY'),
        'api_secret' => env('CLOUDINARY_API_SECRET'),
        'secure' => true,
        'timeout' => (int) env('CLOUDINARY_TIMEOUT_SECONDS', 120),
        'connection_timeout' => (int) env('CLOUDINARY_CONNECT_TIMEOUT_SECONDS', 10),
        'document_folder' => env('CLOUDINARY_DOCUMENT_FOLDER', 'source-documents'),
        'document_delivery_type' => env('CLOUDINARY_DOCUMENT_DELIVERY_TYPE', 'private'),
        'document_resource_type' => 'raw',
        'download_expiry_seconds' => (int) env('CLOUDINARY_DOWNLOAD_EXPIRY_SECONDS', 900),
    ],

];
