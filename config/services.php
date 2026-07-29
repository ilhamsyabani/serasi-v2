<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
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

    'sso_bpom' => [
        'driver' => env('SSO_DRIVER', 'saml2'),
        'idp_entity_id' => env('SSO_IDP_ENTITY_ID'),
        'idp_sso_url' => env('SSO_IDP_SSO_URL'),
        'idp_x509_cert' => env('SSO_IDP_X509_CERT'),
        'sp_entity_id' => env('SSO_SP_ENTITY_ID'),
        'sp_acs_url' => env('SSO_SP_ACS_URL'),
        'sp_slo_url' => env('SSO_SP_SLO_URL'),
    ],

    'wa_gateway' => [
        'url'       => env('WA_GATEWAY_URL'),
        'token'     => env('WA_GATEWAY_TOKEN'),
        'secret_key' => env('WA_GATEWAY_SECRET_KEY'),
        'sender_number' => env('WA_GATEWAY_SENDER'),
        'timeout'   => env('WA_GATEWAY_TIMEOUT', 30),
    ],

    'otp' => [
        'length' => env('OTP_LENGTH', 6),
        'validity_minutes' => env('OTP_VALIDITY_MINUTES', 10),
        'max_attempts' => env('OTP_MAX_ATTEMPTS', 3),
    ],

];
