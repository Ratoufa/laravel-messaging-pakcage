<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Http;
use Ratoufa\Messaging\Contracts\GatewayInterface;
use Ratoufa\Messaging\Data\Response;
use Ratoufa\Messaging\Data\SmsMessage;
use Ratoufa\Messaging\Enums\ResponseCode;
use Ratoufa\Messaging\Exceptions\MessagingException;
use Ratoufa\Messaging\Facades\Messaging;
use Ratoufa\Messaging\Services\SmsManager;

beforeEach(function (): void {
    Http::fake([
        'api.afriksms.com/*' => Http::response([
            'code' => 100,
            'message' => 'Success operation',
            'resourceId' => 'msg_123',
        ]),
    ]);
});

describe('gateway switching', function (): void {
    it('uses default gateway from config', function (): void {
        config()->set('messaging.default', 'sms');

        $manager = app(SmsManager::class);

        expect($manager->getGateway())
            ->toBeInstanceOf(Ratoufa\Messaging\Gateways\AfrikSmsGateway::class);
    });

    it('can switch gateways using using()', function (): void {
        $customGateway = new class implements GatewayInterface
        {
            public function send(SmsMessage $message): Response
            {
                return new Response(
                    success: true,
                    code: ResponseCode::SUCCESS,
                    message: 'Custom gateway',
                    resourceId: 'custom_123',
                );
            }

            public function getBalance(): Illuminate\Support\Collection
            {
                return collect([]);
            }
        };

        $manager = app(SmsManager::class);
        $newManager = $manager->using($customGateway);

        expect($newManager)->not->toBe($manager);
        expect($newManager->getGateway())->toBe($customGateway);
    });
});

describe('Messaging facade', function (): void {
    it('provides sms channel', function (): void {
        $smsManager = Messaging::sms();

        expect($smsManager)->toBeInstanceOf(SmsManager::class);
    });

    it('provides whatsapp channel', function (): void {
        $smsManager = Messaging::whatsapp();

        expect($smsManager)->toBeInstanceOf(SmsManager::class);
    });

    it('provides otp service', function (): void {
        $otp = Messaging::otp();

        expect($otp)->toBeInstanceOf(Ratoufa\Messaging\Services\OtpService::class);
    });

    it('provides fluent sms builder', function (): void {
        $pending = Messaging::smsTo('22890123456');

        expect($pending)->toBeInstanceOf(Ratoufa\Messaging\Pending\PendingSms::class);
    });

    it('provides fluent whatsapp builder', function (): void {
        $pending = Messaging::whatsappTo('22890123456');

        expect($pending)->toBeInstanceOf(Ratoufa\Messaging\Pending\PendingWhatsApp::class);
    });
});

describe('gateway extension', function (): void {
    it('allows extending with custom gateway', function (): void {
        $customGateway = new class implements GatewayInterface
        {
            public function send(SmsMessage $message): Response
            {
                return new Response(
                    success: true,
                    code: ResponseCode::SUCCESS,
                    message: 'Extended gateway',
                    resourceId: 'ext_123',
                );
            }

            public function getBalance(): Illuminate\Support\Collection
            {
                return collect([]);
            }
        };

        Messaging::extend('custom', $customGateway);
        $manager = Messaging::channel('custom');

        $response = $manager->send(new SmsMessage('22890123456', 'Test'));

        expect($response->success)->toBeTrue();
        expect($response->resourceId)->toBe('ext_123');
    });

    it('throws exception for unknown channel', function (): void {
        Messaging::channel('nonexistent');
    })->throws(InvalidArgumentException::class, 'Unknown messaging channel');
});

describe('unsupported operations', function (): void {
    it('throws exception when gateway does not support operation', function (): void {
        $simpleGateway = new class implements GatewayInterface
        {
            public function send(SmsMessage $message): Response
            {
                return new Response(true, ResponseCode::SUCCESS, 'OK');
            }

            public function getBalance(): Illuminate\Support\Collection
            {
                return collect([]);
            }
        };

        $manager = app(SmsManager::class)->using($simpleGateway);

        $manager->sendBulk(new Ratoufa\Messaging\Data\BulkMessage(['22890123456'], 'Test'));
    })->throws(MessagingException::class, 'does not support sendBulk');
});
