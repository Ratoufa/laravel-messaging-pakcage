<?php

declare(strict_types=1);

namespace Ratoufa\Messaging\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Override;
use Ratoufa\Messaging\MessagingServiceProvider;

abstract class TestCase extends Orchestra
{
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();
    }

    #[Override]
    protected function getEnvironmentSetUp($app): void
    {
        config()->set('database.default', 'testing');

        // AfrikSMS config
        config()->set('messaging.afriksms.client_id', 'test_client_id');
        config()->set('messaging.afriksms.api_key', 'test_api_key');
        config()->set('messaging.afriksms.sender_id', 'TestApp');
        config()->set('messaging.afriksms.base_url', 'https://api.afriksms.com/api/web/web_v1/outbounds');

        // Twilio config
        config()->set('messaging.twilio.sid', 'test_sid');
        config()->set('messaging.twilio.auth_token', 'test_auth_token');
        config()->set('messaging.twilio.whatsapp_from', 'whatsapp:+15551234567');

        // General config
        config()->set('messaging.default', 'sms');
        config()->set('messaging.phone.default_country_code', '228');
        config()->set('messaging.logging.channel', 'stack');

        // OTP config
        config()->set('messaging.otp.length', 6);
        config()->set('messaging.otp.expiry_minutes', 10);
        config()->set('messaging.otp.max_attempts', 3);
    }

    #[Override]
    protected function getPackageProviders($app): array
    {
        return [
            MessagingServiceProvider::class,
        ];
    }
}
