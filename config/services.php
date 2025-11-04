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
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'contact_status' => [
        'grpc_endpoint' => env('GRPC_CONTACT_ENDPOINT'),
        'grpc_timeout' => (float) env('GRPC_CONTACT_TIMEOUT', 2.0),
    ],

    'mongo' => [
        'uri' => env('MONGODB_URI'),
        'database' => env('MONGODB_DATABASE'),
        'contacts_collection' => env('MONGODB_CONTACTS_COLLECTION', 'contacts'),
    ],

    'd7' => [
        'api_key' => env('D7_API_KEY'),
        'webhook_secret' => env('D7_WEBHOOK_SECRET'),
    ],

];
