<?php

declare(strict_types=1);

namespace Ratoufa\Messaging\Facades;

use Illuminate\Support\Facades\Facade;
use Ratoufa\Messaging\Data\OtpResult;
use Ratoufa\Messaging\Services\OtpService;

/**
 * @method static OtpResult send(string $phone, string $purpose = 'verification')
 * @method static bool verify(string $phone, string $code, string $purpose = 'verification')
 * @method static OtpResult resend(string $phone, string $purpose = 'verification')
 * @method static bool isValid(string $phone, string $purpose = 'verification')
 * @method static int remainingAttempts(string $phone, string $purpose = 'verification')
 * @method static void invalidate(string $phone, string $purpose = 'verification')
 *
 * @see OtpService
 */
final class Otp extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return OtpService::class;
    }
}
