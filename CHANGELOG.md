# Changelog

All notable changes to `laravel-messaging` will be documented in this file.

## v0.1.0 - 2025-12-25

v0.1.0 - Initial Release

Multi-channel messaging package for Laravel with support for:

• SMS via AfrikSMS (send, bulk, personalized, balance check)
• WhatsApp via Twilio (send, templates, media)
• OTP verification with multi-channel delivery (SMS/WhatsApp)

Features:

- Fluent API with PendingSms/PendingWhatsApp builders
- Laravel notification channels integration
- HasMessaging trait for Eloquent models
- Extensible gateway system
- PHP 8.4+ / Laravel 11-12
