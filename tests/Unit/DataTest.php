<?php

declare(strict_types=1);

use Carbon\Carbon;
use Ratoufa\Messaging\Data\BalanceInfo;
use Ratoufa\Messaging\Data\BulkMessage;
use Ratoufa\Messaging\Data\OtpResult;
use Ratoufa\Messaging\Data\PersonalizedMessage;
use Ratoufa\Messaging\Data\Response;
use Ratoufa\Messaging\Data\SmsMessage;
use Ratoufa\Messaging\Enums\ResponseCode;

describe('SmsMessage', function (): void {
    it('creates an sms message with required fields', function (): void {
        $message = new SmsMessage(
            recipient: '22890123456',
            content: 'Hello World',
        );

        expect($message->recipient)->toBe('22890123456');
        expect($message->content)->toBe('Hello World');
        expect($message->senderId)->toBeNull();
    });

    it('creates an sms message with sender id', function (): void {
        $message = new SmsMessage(
            recipient: '22890123456',
            content: 'Hello World',
            senderId: 'MyApp',
        );

        expect($message->senderId)->toBe('MyApp');
    });
});

describe('BulkMessage', function (): void {
    it('creates a bulk message with multiple recipients', function (): void {
        $message = new BulkMessage(
            recipients: ['22890123456', '22891234567'],
            content: 'Bulk message',
        );

        expect($message->recipients)->toHaveCount(2);
        expect($message->content)->toBe('Bulk message');
        expect($message->count())->toBe(2);
    });

    it('returns recipients as comma-separated string', function (): void {
        $message = new BulkMessage(
            recipients: ['22890123456', '22891234567'],
            content: 'Bulk message',
        );

        expect($message->recipientsAsString())->toBe('22890123456,22891234567');
    });
});

describe('PersonalizedMessage', function (): void {
    it('creates a personalized message', function (): void {
        $message = new PersonalizedMessage(
            recipient: '22890123456',
            content: 'Hello John',
        );

        expect($message->recipient)->toBe('22890123456');
        expect($message->content)->toBe('Hello John');
    });
});

describe('Response', function (): void {
    it('creates a successful response', function (): void {
        $response = new Response(
            success: true,
            code: ResponseCode::SUCCESS,
            message: 'Message sent',
            resourceId: 'msg_123',
        );

        expect($response->success)->toBeTrue();
        expect($response->code)->toBe(ResponseCode::SUCCESS);
        expect($response->resourceId)->toBe('msg_123');
        expect($response->failed())->toBeFalse();
    });

    it('creates a failed response', function (): void {
        $response = new Response(
            success: false,
            code: ResponseCode::INVALID_RECIPIENT,
            message: 'Invalid phone number',
        );

        expect($response->success)->toBeFalse();
        expect($response->failed())->toBeTrue();
        expect($response->code)->toBe(ResponseCode::INVALID_RECIPIENT);
    });

    it('creates response from api array', function (): void {
        $response = Response::fromApiResponse([
            'code' => 100,
            'message' => 'Success operation',
            'resourceId' => 'res_456',
        ]);

        expect($response->success)->toBeTrue();
        expect($response->code)->toBe(ResponseCode::SUCCESS);
        expect($response->resourceId)->toBe('res_456');
    });

    it('handles unknown response codes', function (): void {
        $response = Response::fromApiResponse([
            'code' => 999,
            'message' => 'Unknown error',
        ]);

        expect($response->success)->toBeFalse();
        expect($response->code)->toBe(ResponseCode::UNKNOWN);
    });
});

describe('BalanceInfo', function (): void {
    it('creates balance info from array', function (): void {
        $balance = BalanceInfo::fromArray([
            'countryName' => 'Togo',
            'balance' => 1500,
        ]);

        expect($balance->country)->toBe('Togo');
        expect($balance->balance)->toBe(1500);
    });

    it('creates balance info with constructor', function (): void {
        $balance = new BalanceInfo(
            country: 'Benin',
            balance: 2500,
        );

        expect($balance->country)->toBe('Benin');
        expect($balance->balance)->toBe(2500);
    });
});

describe('OtpResult', function (): void {
    it('creates otp result', function (): void {
        $expiresAt = Carbon::now()->addMinutes(10);
        $response = new Response(
            success: true,
            code: ResponseCode::SUCCESS,
            message: 'OTP sent',
        );

        $result = new OtpResult(
            success: true,
            code: '123456',
            expiresAt: $expiresAt,
            response: $response,
        );

        expect($result->success)->toBeTrue();
        expect($result->code)->toBe('123456');
        expect($result->expiresAt)->toBe($expiresAt);
        expect($result->response)->toBe($response);
    });
});
