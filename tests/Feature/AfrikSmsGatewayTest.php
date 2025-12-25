<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Http;
use Ratoufa\Messaging\Data\BulkMessage;
use Ratoufa\Messaging\Data\PersonalizedMessage;
use Ratoufa\Messaging\Data\Response;
use Ratoufa\Messaging\Data\SmsMessage;
use Ratoufa\Messaging\Enums\ResponseCode;
use Ratoufa\Messaging\Exceptions\MessagingException;
use Ratoufa\Messaging\Facades\Sms;

beforeEach(function (): void {
    Http::fake([
        'https://api.afriksms.com/api/web/web_v1/outbounds/send*' => Http::response([
            'code' => 100,
            'message' => 'Success operation',
            'resourceId' => 'msg_123',
        ]),
        'https://api.afriksms.com/api/web/web_v1/outbounds/send_multisms*' => Http::response([
            'code' => 100,
            'message' => 'Success operation',
            'resourceId' => 'bulk_123',
        ]),
        'https://api.afriksms.com/api/web/web_v1/outbounds/send_customer_multisms*' => Http::response([
            'code' => 100,
            'message' => 'Success operation',
            'resourceId' => 'pers_123',
        ]),
        'https://api.afriksms.com/api/web/web_v1/outbounds/solde*' => Http::response([
            'code' => 100,
            'message' => 'Success',
            'information' => [
                ['countryName' => 'Togo', 'balance' => 1500],
                ['countryName' => 'Benin', 'balance' => 800],
            ],
        ]),
        'https://api.afriksms.com/api/web/web_v1/outbounds/callback_url*' => Http::response([
            'code' => 100,
            'message' => 'Callback configured',
        ]),
        'https://api.afriksms.com/*' => Http::response([
            'code' => 100,
            'message' => 'Success operation',
            'resourceId' => 'msg_123',
        ]),
    ]);
});

describe('send', function (): void {
    it('sends a single sms message', function (): void {
        $response = Sms::to('22890123456')->send('Hello World');

        expect($response->success)->toBeTrue();
        expect($response->code)->toBe(ResponseCode::SUCCESS);
        expect($response->resourceId)->toBe('msg_123');

        Http::assertSent(fn ($request): bool => str_contains((string) $request->url(), '/send')
            && $request['Message'] === 'Hello World'
        );
    });

    it('sends message using SmsMessage object', function (): void {
        $message = new SmsMessage(
            recipient: '22890123456',
            content: 'Test message',
            senderId: 'CustomApp',
        );

        $response = Sms::send($message);

        expect($response->success)->toBeTrue();

        Http::assertSent(fn ($request): bool => $request['SenderId'] === 'CustomApp'
            && $request['Message'] === 'Test message'
        );
    });

    it('throws exception for invalid phone number', function (): void {
        Sms::to('123')->send('Test');
    })->throws(MessagingException::class, 'Invalid recipient');
});

describe('sendBulk', function (): void {
    it('sends bulk sms to multiple recipients', function (): void {
        $response = Sms::toMany(['22890123456', '22891234567'])
            ->send('Bulk message');

        expect($response->success)->toBeTrue();
        expect($response->code)->toBe(ResponseCode::SUCCESS);

        Http::assertSent(fn ($request): bool => str_contains((string) $request->url(), '/send_multisms'));
    });

    it('sends bulk using BulkMessage object', function (): void {
        $message = new BulkMessage(
            recipients: ['22890123456', '22891234567'],
            content: 'Bulk test',
        );

        $response = Sms::sendBulk($message);

        expect($response->success)->toBeTrue();
    });

    it('throws exception when exceeding max recipients', function (): void {
        $recipients = array_fill(0, 501, '22890123456');

        $message = new BulkMessage(
            recipients: $recipients,
            content: 'Too many',
        );

        Sms::sendBulk($message);
    })->throws(MessagingException::class, 'Maximum 500 recipients');
});

describe('sendPersonalized', function (): void {
    it('sends personalized messages', function (): void {
        $messages = [
            new PersonalizedMessage('22890123456', 'Hello John'),
            new PersonalizedMessage('22891234567', 'Hello Jane'),
        ];

        $response = Sms::sendPersonalized($messages);

        expect($response->success)->toBeTrue();

        Http::assertSent(fn ($request): bool => str_contains((string) $request->url(), '/send_customer_multisms'));
    });
});

describe('getBalance', function (): void {
    it('retrieves account balance', function (): void {
        $balances = Sms::getBalance();

        expect($balances)->toHaveCount(2);
        expect($balances->first()->country)->toBe('Togo');
        expect($balances->first()->balance)->toBe(1500);
    });
});

describe('configureCallback', function (): void {
    it('configures callback url', function (): void {
        $response = Sms::configureCallback('https://example.com/callback');

        expect($response->success)->toBeTrue();

        Http::assertSent(fn ($request): bool => str_contains((string) $request->url(), '/callback_url')
            && $request['notifyURL'] === 'https://example.com/callback'
        );
    });

    it('configures callback with GET method', function (): void {
        $response = Sms::configureCallback('https://example.com/callback', 'GET');

        expect($response->success)->toBeTrue();

        Http::assertSent(fn ($request): bool => $request['TypeNotification'] === 2);
    });
});

describe('error handling', function (): void {
    it('parses error codes correctly from api response', function (): void {
        $response = Response::fromApiResponse([
            'code' => 401,
            'message' => 'Invalid credentials',
        ]);

        expect($response->success)->toBeFalse();
        expect($response->code)->toBe(ResponseCode::INVALID_CREDENTIALS);
    });

    it('parses insufficient balance error', function (): void {
        $response = Response::fromApiResponse([
            'code' => 402,
            'message' => 'Insufficient balance',
        ]);

        expect($response->success)->toBeFalse();
        expect($response->code)->toBe(ResponseCode::INSUFFICIENT_BALANCE);
    });

    it('parses invalid recipient error', function (): void {
        $response = Response::fromApiResponse([
            'code' => 422,
            'message' => 'Invalid phone number',
        ]);

        expect($response->success)->toBeFalse();
        expect($response->code)->toBe(ResponseCode::INVALID_RECIPIENT);
    });
});
