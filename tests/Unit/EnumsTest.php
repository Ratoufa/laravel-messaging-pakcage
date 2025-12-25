<?php

declare(strict_types=1);

use Ratoufa\Messaging\Enums\Channel;
use Ratoufa\Messaging\Enums\DeliveryStatus;
use Ratoufa\Messaging\Enums\ResponseCode;

describe('Channel', function (): void {
    it('has sms channel', function (): void {
        expect(Channel::SMS->value)->toBe('sms');
    });

    it('has whatsapp channel', function (): void {
        expect(Channel::WHATSAPP->value)->toBe('whatsapp');
    });
});

describe('DeliveryStatus', function (): void {
    it('has all expected statuses', function (): void {
        expect(DeliveryStatus::PENDING->value)->toBe('pending');
        expect(DeliveryStatus::SENT->value)->toBe('sent');
        expect(DeliveryStatus::DELIVERED->value)->toBe('delivered');
        expect(DeliveryStatus::FAILED->value)->toBe('failed');
    });
});

describe('ResponseCode', function (): void {
    it('identifies success codes', function (): void {
        expect(ResponseCode::SUCCESS->isSuccess())->toBeTrue();
        expect(ResponseCode::INVALID_CREDENTIALS->isSuccess())->toBeFalse();
        expect(ResponseCode::INSUFFICIENT_BALANCE->isSuccess())->toBeFalse();
    });

    it('has correct integer values', function (): void {
        expect(ResponseCode::SUCCESS->value)->toBe(100);
        expect(ResponseCode::INVALID_CREDENTIALS->value)->toBe(401);
        expect(ResponseCode::INSUFFICIENT_BALANCE->value)->toBe(402);
        expect(ResponseCode::INVALID_RECIPIENT->value)->toBe(422);
        expect(ResponseCode::SERVER_ERROR->value)->toBe(500);
    });

    it('can be created from value', function (): void {
        expect(ResponseCode::tryFrom(100))->toBe(ResponseCode::SUCCESS);
        expect(ResponseCode::tryFrom(401))->toBe(ResponseCode::INVALID_CREDENTIALS);
        expect(ResponseCode::tryFrom(9999))->toBeNull();
    });
});
