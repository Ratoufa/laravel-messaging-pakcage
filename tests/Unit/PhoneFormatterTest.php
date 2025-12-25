<?php

declare(strict_types=1);

use Ratoufa\Messaging\Support\PhoneFormatter;

it('formats phone number with default country code', function (): void {
    expect(PhoneFormatter::format('90123456', '228'))->toBe('22890123456');
});

it('removes special characters from phone number', function (): void {
    expect(PhoneFormatter::format('+228 90 12 34 56'))->toBe('22890123456');
});

it('removes leading double zeros', function (): void {
    expect(PhoneFormatter::format('0022890123456'))->toBe('22890123456');
});

it('validates phone number length correctly', function (): void {
    expect(PhoneFormatter::isValid('22890123456'))->toBeTrue();
    expect(PhoneFormatter::isValid('123'))->toBeFalse();
    expect(PhoneFormatter::isValid('1234567890123456'))->toBeFalse(); // Too long
});

it('formats for WhatsApp with prefix', function (): void {
    expect(PhoneFormatter::formatForWhatsApp('90123456', '228'))
        ->toBe('whatsapp:+22890123456');
});

it('formats multiple phone numbers', function (): void {
    $phones = ['90123456', '91234567'];
    $formatted = PhoneFormatter::formatMany($phones, '228');

    expect($formatted)->toBe(['22890123456', '22891234567']);
});

it('normalizes phone with plus prefix', function (): void {
    expect(PhoneFormatter::normalize('90123456', '228'))->toBe('+22890123456');
});

it('uses config default country code when not provided', function (): void {
    config()->set('messaging.phone.default_country_code', '229');

    expect(PhoneFormatter::format('90123456'))->toBe('22990123456');
});

it('does not add country code if already present', function (): void {
    expect(PhoneFormatter::format('22890123456', '228'))->toBe('22890123456');
});
