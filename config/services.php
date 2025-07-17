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

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],
    'shopify' => [
        'host' => env('SHOPIFY_HOST'),
        'key' => env('SHOPIFY_KEY'),
        'secret' => env('SHOPIFY_SECRET'),
        'access_token' => env('SHOPIFY_ACCESS_TOKEN'),
        'version' => env('SHOPIFY_VERSION'),
        'collection_id' => env('SHOPIFY_COLLECTION_ID'),
        'location_id' => env('SHOPIFY_LOCATION_ID'),
    ],
    'supabase' => [
        'url' => env('SUPABASE_URL'),
        'anon_key' => env('SUPABASE_ANON_KEY'),
        'service_role_key' => env('SUPABASE_SERVICE_ROLE_KEY'),
    ],
    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],
    'open_ia' => [
        'api_key' => env('API_KEY_OPEN_IA'),
        'assistant' => env('ASSISTANTS_ID'),
    ],

];
