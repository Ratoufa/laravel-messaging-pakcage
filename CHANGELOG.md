# Changelog

All notable changes to `laravel-messaging` will be documented in this file.

## Whatsapp: add template support for OTP - 2025-12-26

- Add TEMPLATE_REQUIRED (463) response code for Twilio error 63016
- Implement WhatsAppOtpSender to send OTP via Message Templates
- Create OtpSenderInterface for sender abstraction
- Add configuration for WhatsApp OTP template (TWILIO_OTP_TEMPLATE_SID)
- Update OtpManager to use interface-based dependency injection
- Document WhatsApp 24-hour messaging window limitation in README

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
