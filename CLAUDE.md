# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Commands

```bash
# Run full test suite (lint, type coverage, typos, unit tests, static analysis, refactor checks)
composer test

# Run individual checks
composer test:unit              # Pest tests (parallel)
composer test:lint              # Pint code style check
composer test:types             # PHPStan static analysis
composer test:refactor          # Rector dry-run
composer test:typos             # Peck typo detection
composer test:type-coverage     # Pest type coverage (100% required)

# Run a single test file
./vendor/bin/pest tests/Feature/SmsManagerTest.php

# Run a specific test
./vendor/bin/pest --filter="can switch gateways"

# Auto-fix issues
composer fix                    # Runs phpstan, rector, pint
composer lint                   # Pint formatting
composer refactor               # Rector refactoring
```

## Architecture

This is a Laravel package for multi-channel messaging (SMS via AfrikSMS, WhatsApp via Twilio) with OTP verification support.

### Core Flow

```
Facades (Sms, WhatsApp, Otp, Messaging)
    ↓
Services (SmsManager, OtpService)
    ↓
Gateways (AfrikSmsGateway, TwilioWhatsAppGateway)
    ↓
External APIs
```

### Key Components

- **`Messaging`** (`src/Messaging.php`): Central orchestrator providing access to all channels via `sms()`, `whatsapp()`, `otp()`, and dynamic `channel()` method. Supports gateway extension with `extend()`.

- **`SmsManager`** (`src/Services/SmsManager.php`): Handles message sending through gateways. Delegates operations to the underlying gateway and throws `MessagingException` for unsupported operations.

- **Gateways** (`src/Gateways/`):
  - `AfrikSmsGateway`: SMS via AfrikSMS API (supports send, sendBulk, sendPersonalized, balance, callbacks)
  - `TwilioWhatsAppGateway`: WhatsApp via Twilio (supports send, templates, media)

- **Pending Builders** (`src/Pending/`): Fluent API builders (`PendingSms`, `PendingBulkSms`, `PendingWhatsApp`) for chainable message construction.

- **Data Objects** (`src/Data/`): Immutable DTOs for messages (`SmsMessage`, `BulkMessage`, `PersonalizedMessage`), responses (`Response`, `OtpResult`), and balance info.

- **Contracts** (`src/Contracts/`): Gateway interfaces defining required methods. Custom gateways must implement `GatewayInterface`.

### Laravel Integration

- Service provider registers singletons for gateways and services
- Notification channels (`sms`, `whatsapp`) registered via `ChannelManager`
- `HasMessaging` trait provides `sendSms()`, `sendWhatsApp()`, `sendOtp()`, `verifyOtp()` on models
- Config published to `config/messaging.php`

### Testing Patterns

Tests use Pest with Orchestra Testbench. Gateway tests fake HTTP responses:

```php
Http::fake([
    'api.afriksms.com/*' => Http::response([
        'code' => 100,
        'message' => 'Success',
        'resourceId' => 'msg_123',
    ]),
]);
```

The `TestCase` class sets up test configuration for all gateway credentials.

## Code Style

- PHP 8.4+ with strict types
- PHPStan level 5
- Classes are `final` and `readonly` where appropriate
- Use enums for constants (`ResponseCode`, `Channel`, `DeliveryStatus`)
- Pest for testing with `describe`/`it` blocks
