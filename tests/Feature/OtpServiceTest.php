<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Ratoufa\Messaging\Facades\Otp;

beforeEach(function (): void {
    Http::fake([
        'api.afriksms.com/*' => Http::response([
            'code' => 100,
            'message' => 'Success operation',
            'resourceId' => 'test_resource_id',
        ]),
    ]);
});

it('sends otp and stores in cache', function (): void {
    $result = Otp::send('22890123456');

    expect($result->success)->toBeTrue();
    expect($result->code)->toHaveLength(6);
    expect(Cache::has('messaging:otp:verification:22890123456'))->toBeTrue();
});

it('verifies correct otp', function (): void {
    $result = Otp::send('22890123456');

    expect(Otp::verify('22890123456', $result->code))->toBeTrue();
    expect(Cache::has('messaging:otp:verification:22890123456'))->toBeFalse();
});

it('rejects incorrect otp', function (): void {
    Otp::send('22890123456');

    expect(Otp::verify('22890123456', '000000'))->toBeFalse();
});

it('tracks remaining attempts', function (): void {
    Otp::send('22890123456');

    expect(Otp::remainingAttempts('22890123456'))->toBe(3);

    Otp::verify('22890123456', 'wrong1');
    expect(Otp::remainingAttempts('22890123456'))->toBe(2);

    Otp::verify('22890123456', 'wrong2');
    expect(Otp::remainingAttempts('22890123456'))->toBe(1);
});

it('invalidates otp after max attempts', function (): void {
    Otp::send('22890123456');

    Otp::verify('22890123456', 'wrong1');
    Otp::verify('22890123456', 'wrong2');
    Otp::verify('22890123456', 'wrong3');

    expect(Otp::remainingAttempts('22890123456'))->toBe(0);
    expect(Cache::has('messaging:otp:verification:22890123456'))->toBeFalse();
});

it('supports different purposes', function (): void {
    $verification = Otp::send('22890123456', 'verification');
    $passwordReset = Otp::send('22890123456', 'password-reset');

    expect($verification->code)->not->toBe($passwordReset->code);
    expect(Cache::has('messaging:otp:verification:22890123456'))->toBeTrue();
    expect(Cache::has('messaging:otp:password-reset:22890123456'))->toBeTrue();
});

it('can resend otp with new code', function (): void {
    $first = Otp::send('22890123456');
    $second = Otp::resend('22890123456');

    expect($second->success)->toBeTrue();
    expect($second->code)->not->toBe($first->code);
});

it('checks if otp is valid', function (): void {
    expect(Otp::isValid('22890123456'))->toBeFalse();

    Otp::send('22890123456');

    expect(Otp::isValid('22890123456'))->toBeTrue();
});

it('can invalidate otp manually', function (): void {
    Otp::send('22890123456');
    expect(Otp::isValid('22890123456'))->toBeTrue();

    Otp::invalidate('22890123456');

    expect(Otp::isValid('22890123456'))->toBeFalse();
});

it('returns zero remaining attempts when no otp exists', function (): void {
    expect(Otp::remainingAttempts('22899999999'))->toBe(0);
});
