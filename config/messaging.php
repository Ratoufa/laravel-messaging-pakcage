<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Default Messaging Channel
    |--------------------------------------------------------------------------
    |
    | This option controls the default messaging channel that will be used
    | when sending messages. Supported: "sms", "whatsapp"
    |
    */
    'default' => env('MESSAGING_DEFAULT_CHANNEL', 'sms'),

    /*
    |--------------------------------------------------------------------------
    | AfrikSMS Configuration
    |--------------------------------------------------------------------------
    |
    | Configure your AfrikSMS API credentials here. You can get these from
    | your AfrikSMS dashboard at https://afriksms.com
    |
    */
    'afriksms' => [
        'client_id' => env('AFRIKSMS_CLIENT_ID'),
        'api_key' => env('AFRIKSMS_API_KEY'),
        'sender_id' => env('AFRIKSMS_SENDER_ID', 'MyApp'),
        'base_url' => env('AFRIKSMS_BASE_URL', 'https://api.afriksms.com/api/web/web_v1/outbounds'),
        'timeout' => (int) env('AFRIKSMS_TIMEOUT', 30),
        'retry' => [
            'times' => (int) env('AFRIKSMS_RETRY_TIMES', 3),
            'sleep' => (int) env('AFRIKSMS_RETRY_SLEEP', 100),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Twilio WhatsApp Configuration
    |--------------------------------------------------------------------------
    |
    | Configure your Twilio API credentials here. You can get these from
    | your Twilio console at https://console.twilio.com
    |
    */
    'twilio' => [
        'sid' => env('TWILIO_SID'),
        'auth_token' => env('TWILIO_AUTH_TOKEN'),
        'whatsapp_from' => env('TWILIO_WHATSAPP_FROM'),
        'timeout' => (int) env('TWILIO_TIMEOUT', 30),
        'retry' => [
            'times' => (int) env('TWILIO_RETRY_TIMES', 3),
            'sleep' => (int) env('TWILIO_RETRY_SLEEP', 100),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | OTP Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the One-Time Password settings for verification codes.
    |
    */
    'otp' => [
        'length' => (int) env('MESSAGING_OTP_LENGTH', 6),
        'expiry_minutes' => (int) env('MESSAGING_OTP_EXPIRY', 10),
        'max_attempts' => (int) env('MESSAGING_OTP_MAX_ATTEMPTS', 3),
        'message' => env('MESSAGING_OTP_MESSAGE', 'Your verification code is: {code}. Valid for {expiry} minutes.'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Phone Number Formatting
    |--------------------------------------------------------------------------
    |
    | Configure default country code for phone number formatting.
    | The default is 228 (Togo).
    |
    */
    'phone' => [
        'default_country_code' => env('MESSAGING_DEFAULT_COUNTRY_CODE', '228'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging
    |--------------------------------------------------------------------------
    |
    | Configure which log channel to use for messaging operations.
    |
    */
    'logging' => [
        'channel' => env('MESSAGING_LOG_CHANNEL', 'stack'),
    ],
];
