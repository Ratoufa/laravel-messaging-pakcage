<?php

declare(strict_types=1);

return [
    'otp' => [
        'message' => 'Your verification code is: :code. Valid for :expiry minutes.',
        'expired' => 'This code has expired.',
        'invalid' => 'Invalid code.',
        'max_attempts' => 'Maximum number of attempts reached.',
    ],

    'errors' => [
        'send_failed' => 'Failed to send message.',
        'invalid_recipient' => 'Invalid phone number.',
        'quota_exceeded' => 'Message quota exceeded.',
        'configuration_missing' => 'Missing configuration.',
        'api_error' => 'API error.',
        'unsupported_operation' => 'Unsupported operation.',
    ],

    'success' => [
        'message_sent' => 'Message sent successfully.',
        'otp_sent' => 'Verification code sent.',
        'otp_verified' => 'Code verified successfully.',
    ],
];
