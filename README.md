# Laravel Messaging

[![Latest Version on Packagist](https://img.shields.io/packagist/v/ratoufa/laravel-messaging.svg?style=flat-square)](https://packagist.org/packages/ratoufa/laravel-messaging)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/ratoufa/laravel-messaging/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/ratoufa/laravel-messaging/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/ratoufa/laravel-messaging/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/ratoufa/laravel-messaging/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/ratoufa/laravel-messaging.svg?style=flat-square)](https://packagist.org/packages/ratoufa/laravel-messaging)

A multi-channel messaging package for Laravel supporting SMS (via AfrikSMS) and WhatsApp (via Twilio). Features a fluent API, OTP verification, Laravel Notifications integration, and extensible gateway system.

## Requirements

- PHP 8.4+
- Laravel 11.x or 12.x

## Installation

Install the package via Composer:

```bash
composer require ratoufa/laravel-messaging
```

Publish the configuration file:

```bash
php artisan vendor:publish --tag="messaging-config"
```

## Configuration

Add the following environment variables to your `.env` file:

```env
# Default channel (sms or whatsapp)
MESSAGING_DEFAULT_CHANNEL=sms

# AfrikSMS credentials
AFRIKSMS_CLIENT_ID=your-client-id
AFRIKSMS_API_KEY=your-api-key
AFRIKSMS_SENDER_ID=MyApp

# Twilio WhatsApp credentials
TWILIO_SID=your-account-sid
TWILIO_AUTH_TOKEN=your-auth-token
TWILIO_WHATSAPP_FROM=whatsapp:+15551234567

# OTP settings (optional)
MESSAGING_OTP_LENGTH=6
MESSAGING_OTP_EXPIRY=10
MESSAGING_OTP_MAX_ATTEMPTS=3

# Phone formatting (optional)
MESSAGING_DEFAULT_COUNTRY_CODE=228
```

## Usage

### SMS

#### Send a single SMS

```php
use Ratoufa\Messaging\Facades\Sms;

// Fluent API
$response = Sms::to('22890123456')->send('Hello World!');

// With custom sender ID
$response = Sms::to('22890123456')
    ->from('MyBrand')
    ->send('Hello World!');

// Using a message object
use Ratoufa\Messaging\Data\SmsMessage;

$message = new SmsMessage(
    recipient: '22890123456',
    content: 'Hello World!',
    senderId: 'MyBrand',
);

$response = Sms::send($message);
```

#### Send bulk SMS

```php
use Ratoufa\Messaging\Facades\Sms;

// Fluent API - same message to multiple recipients
$response = Sms::toMany(['22890123456', '22891234567'])
    ->send('Bulk message to all');

// Using a message object
use Ratoufa\Messaging\Data\BulkMessage;

$message = new BulkMessage(
    recipients: ['22890123456', '22891234567'],
    content: 'Bulk message to all',
    senderId: 'MyBrand',
);

$response = Sms::sendBulk($message);
```

#### Send personalized SMS

```php
use Ratoufa\Messaging\Facades\Sms;
use Ratoufa\Messaging\Data\PersonalizedMessage;

$messages = [
    new PersonalizedMessage('22890123456', 'Hello John, your code is 1234'),
    new PersonalizedMessage('22891234567', 'Hello Jane, your code is 5678'),
];

$response = Sms::sendPersonalized($messages);
```

#### Check balance

```php
use Ratoufa\Messaging\Facades\Sms;

$balances = Sms::getBalance();

foreach ($balances as $balance) {
    echo "{$balance->country}: {$balance->balance} credits";
}
```

#### Configure delivery callback

```php
use Ratoufa\Messaging\Facades\Sms;

// POST callback (default)
$response = Sms::configureCallback('https://example.com/webhook/sms');

// GET callback
$response = Sms::configureCallback('https://example.com/webhook/sms', 'GET');
```

### WhatsApp

#### Send a message

```php
use Ratoufa\Messaging\Facades\WhatsApp;

// Simple message
$response = WhatsApp::to('22890123456')->send('Hello via WhatsApp!');

// Using a message object
use Ratoufa\Messaging\Data\SmsMessage;

$message = new SmsMessage(
    recipient: '22890123456',
    content: 'Hello via WhatsApp!',
);

$response = WhatsApp::send($message);
```

#### Send a template message

```php
use Ratoufa\Messaging\Facades\WhatsApp;

$response = WhatsApp::sendTemplate(
    recipient: '22890123456',
    contentSid: 'HXb5a34a7e18eb123456789',
    variables: ['1' => 'John', '2' => 'Order #12345'],
);
```

#### Send media

```php
use Ratoufa\Messaging\Facades\WhatsApp;

// Image with caption
$response = WhatsApp::sendMedia(
    recipient: '22890123456',
    mediaUrl: 'https://example.com/image.jpg',
    caption: 'Check this out!',
);

// Document without caption
$response = WhatsApp::sendMedia(
    recipient: '22890123456',
    mediaUrl: 'https://example.com/document.pdf',
);
```

### OTP Verification

#### Send OTP

```php
use Ratoufa\Messaging\Facades\Otp;

$result = Otp::send('22890123456');

if ($result->success) {
    echo "OTP sent, expires at: {$result->expiresAt}";
}

// With custom purpose
$result = Otp::send('22890123456', 'password-reset');
```

#### Verify OTP

```php
use Ratoufa\Messaging\Facades\Otp;

$isValid = Otp::verify('22890123456', '123456');

if ($isValid) {
    echo "OTP verified successfully!";
}

// With custom purpose
$isValid = Otp::verify('22890123456', '123456', 'password-reset');
```

#### Resend OTP

```php
use Ratoufa\Messaging\Facades\Otp;

$result = Otp::resend('22890123456');
```

#### Check remaining attempts

```php
use Ratoufa\Messaging\Facades\Otp;

$attempts = Otp::remainingAttempts('22890123456');
echo "Remaining attempts: {$attempts}";
```

#### Invalidate OTP

```php
use Ratoufa\Messaging\Facades\Otp;

Otp::invalidate('22890123456');
```

### Using the Messaging Facade

The `Messaging` facade provides access to all channels:

```php
use Ratoufa\Messaging\Facades\Messaging;

// SMS
Messaging::sms()->to('22890123456')->send('Hello!');

// WhatsApp
Messaging::whatsapp()->to('22890123456')->send('Hello!');

// OTP
Messaging::otp()->send('22890123456');

// Dynamic channel selection
Messaging::channel('sms')->to('22890123456')->send('Hello!');
```

### Response Handling

All send operations return a `Response` object:

```php
use Ratoufa\Messaging\Facades\Sms;
use Ratoufa\Messaging\Enums\ResponseCode;

$response = Sms::to('22890123456')->send('Hello!');

// Check success
if ($response->success) {
    echo "Message sent! ID: {$response->resourceId}";
}

// Check specific error codes
if ($response->code === ResponseCode::INSUFFICIENT_BALANCE) {
    echo "Please recharge your account";
}

// Available response codes
ResponseCode::SUCCESS              // 100
ResponseCode::INVALID_CREDENTIALS  // 401
ResponseCode::INSUFFICIENT_BALANCE // 402
ResponseCode::INVALID_RECIPIENT    // 422
ResponseCode::SERVER_ERROR         // 500
```

## Laravel Notifications

### SMS Channel

```php
use Illuminate\Notifications\Notification;
use Ratoufa\Messaging\Data\SmsMessage;

class OrderShipped extends Notification
{
    public function via($notifiable): array
    {
        return ['sms'];
    }

    public function toSms($notifiable): SmsMessage
    {
        return new SmsMessage(
            recipient: $notifiable->phone,
            content: "Your order #{$this->order->id} has been shipped!",
        );
    }
}
```

### WhatsApp Channel

```php
use Illuminate\Notifications\Notification;
use Ratoufa\Messaging\Data\SmsMessage;

class OrderShipped extends Notification
{
    public function via($notifiable): array
    {
        return ['whatsapp'];
    }

    public function toWhatsApp($notifiable): SmsMessage
    {
        return new SmsMessage(
            recipient: $notifiable->phone,
            content: "Your order #{$this->order->id} has been shipped!",
        );
    }
}
```

### Model Trait

Add the `HasMessaging` trait to your model for quick messaging:

```php
use Ratoufa\Messaging\Concerns\HasMessaging;

class User extends Authenticatable
{
    use HasMessaging;

    // Define the phone field (defaults to 'phone')
    public function routeNotificationForSms(): ?string
    {
        return $this->phone_number;
    }

    public function routeNotificationForWhatsApp(): ?string
    {
        return $this->whatsapp_number;
    }
}
```

Usage:

```php
$user->sendSms('Hello!');
$user->sendWhatsApp('Hello via WhatsApp!');
$user->sendOtp();
$user->verifyOtp('123456');
```

## Custom Gateways

You can register custom gateways:

```php
use Ratoufa\Messaging\Contracts\GatewayInterface;
use Ratoufa\Messaging\Facades\Messaging;

class MyCustomGateway implements GatewayInterface
{
    public function send(SmsMessage $message): Response
    {
        // Your implementation
    }

    public function getBalance(): Collection
    {
        // Your implementation
    }
}

// Register the gateway
Messaging::extend('custom', new MyCustomGateway());

// Use it
Messaging::channel('custom')->to('22890123456')->send('Hello!');
```

## Artisan Command

Check your account balance:

```bash
php artisan messaging balance
php artisan messaging balance --channel=whatsapp
```

## Phone Number Formatting

The package includes a phone formatter utility:

```php
use Ratoufa\Messaging\Support\PhoneFormatter;

$formatter = new PhoneFormatter();

// Format with default country code (from config)
$phone = $formatter->format('90123456'); // "22890123456"

// Format with specific country code
$phone = $formatter->format('90123456', '229'); // "22990123456"

// Format for WhatsApp
$phone = $formatter->formatForWhatsApp('22890123456'); // "whatsapp:+22890123456"

// Format multiple numbers
$phones = $formatter->formatMany(['90123456', '91234567']);

// Validate phone number
$isValid = $formatter->isValid('22890123456'); // true
```

## Events

The package dispatches events for delivery reports:

```php
use Ratoufa\Messaging\Events\MessageDeliveryReportReceived;

class MessageDeliveryListener
{
    public function handle(MessageDeliveryReportReceived $event): void
    {
        $messageId = $event->messageId;
        $status = $event->status; // DeliveryStatus enum
        $recipient = $event->recipient;
        $deliveredAt = $event->deliveredAt;
    }
}
```

Register in `EventServiceProvider`:

```php
protected $listen = [
    MessageDeliveryReportReceived::class => [
        MessageDeliveryListener::class,
    ],
];
```

## Testing

```bash
composer test
```

This runs:
- PHPStan (static analysis)
- Pest (unit & feature tests)
- Pint (code style)
- Rector (refactoring checks)
- Peck (typo detection)

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Just Chris](https://github.com/justchr1s)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
