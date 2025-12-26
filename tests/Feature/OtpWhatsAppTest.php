<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Cache;
use Ratoufa\Messaging\Contracts\WhatsAppGatewayInterface;
use Ratoufa\Messaging\Data\Response;
use Ratoufa\Messaging\Enums\ResponseCode;
use Ratoufa\Messaging\Exceptions\MessagingException;
use Ratoufa\Messaging\Facades\Otp;
use Ratoufa\Messaging\Services\OtpManager;

beforeEach(function (): void {
    // Set up WhatsApp OTP template configuration
    config()->set('messaging.otp.whatsapp.template_sid', 'HXtest123456789');
    config()->set('messaging.otp.whatsapp.code_variable', '1');

    // Create a mock WhatsApp gateway using the interface
    $mockGateway = Mockery::mock(WhatsAppGatewayInterface::class);
    $mockGateway->shouldReceive('sendTemplate')
        ->andReturn(new Response(
            success: true,
            code: ResponseCode::SUCCESS,
            message: 'Template sent',
            resourceId: 'SM123456',
        ));

    // Bind the mock to the interface (used by OtpManager)
    app()->instance(WhatsAppGatewayInterface::class, $mockGateway);

    // Clear the OtpManager service cache to use the new mock
    app()->forgetInstance(OtpManager::class);
});

afterEach(function (): void {
    Mockery::close();
});

describe('WhatsApp OTP', function (): void {
    it('sends otp via whatsapp using template', function (): void {
        $result = Otp::whatsapp()->send('22890123456');

        expect($result->success)->toBeTrue();
        expect($result->code)->toHaveLength(6);
        expect(Cache::has('messaging:otp:verification:22890123456'))->toBeTrue();
    });

    it('verifies correct otp sent via whatsapp', function (): void {
        $result = Otp::whatsapp()->send('22890123456');

        expect(Otp::whatsapp()->verify('22890123456', $result->code))->toBeTrue();
        expect(Cache::has('messaging:otp:verification:22890123456'))->toBeFalse();
    });

    it('rejects incorrect otp for whatsapp', function (): void {
        Otp::whatsapp()->send('22890123456');

        expect(Otp::whatsapp()->verify('22890123456', '000000'))->toBeFalse();
    });

    it('can resend otp via whatsapp', function (): void {
        $first = Otp::whatsapp()->send('22890123456');
        $second = Otp::whatsapp()->resend('22890123456');

        expect($second->success)->toBeTrue();
        expect($second->code)->not->toBe($first->code);
    });

    it('tracks remaining attempts for whatsapp otp', function (): void {
        Otp::whatsapp()->send('22890123456');

        expect(Otp::whatsapp()->remainingAttempts('22890123456'))->toBe(3);

        Otp::whatsapp()->verify('22890123456', 'wrong1');
        expect(Otp::whatsapp()->remainingAttempts('22890123456'))->toBe(2);
    });

    it('can invalidate whatsapp otp manually', function (): void {
        Otp::whatsapp()->send('22890123456');
        expect(Otp::whatsapp()->isValid('22890123456'))->toBeTrue();

        Otp::whatsapp()->invalidate('22890123456');

        expect(Otp::whatsapp()->isValid('22890123456'))->toBeFalse();
    });
});

describe('WhatsApp OTP configuration', function (): void {
    it('throws exception when template sid is not configured', function (): void {
        config()->set('messaging.otp.whatsapp.template_sid');

        // Clear the OtpManager to force recreation
        app()->forgetInstance(OtpManager::class);

        Otp::whatsapp()->send('22890123456');
    })->throws(MessagingException::class);

    it('uses sendTemplate with correct parameters', function (): void {
        $mockGateway = Mockery::mock(WhatsAppGatewayInterface::class);
        $mockGateway->shouldReceive('sendTemplate')
            ->once()
            ->withArgs(fn ($recipient, $contentSid, array $variables): bool => $recipient === '22890123456'
                && $contentSid === 'HXtest123456789'
                && isset($variables['1'])
                && mb_strlen((string) $variables['1']) === 6)
            ->andReturn(new Response(
                success: true,
                code: ResponseCode::SUCCESS,
                message: 'Template sent',
            ));

        app()->instance(WhatsAppGatewayInterface::class, $mockGateway);
        app()->forgetInstance(OtpManager::class);

        Otp::whatsapp()->send('22890123456');
    });
});
