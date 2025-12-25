<?php

declare(strict_types=1);

namespace Ratoufa\Messaging\Services;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use InvalidArgumentException;
use Ratoufa\Messaging\Data\OtpResult;
use Ratoufa\Messaging\Enums\Channel;
use Ratoufa\Messaging\Gateways\AfrikSmsGateway;
use Ratoufa\Messaging\Gateways\TwilioWhatsAppGateway;

final class OtpManager
{
    /** @var array<string, OtpService> */
    private array $services = [];

    public function __construct(
        private readonly AfrikSmsGateway $afrikSmsGateway,
        private readonly TwilioWhatsAppGateway $twilioWhatsAppGateway,
        private readonly CacheRepository $cache,
        private readonly ConfigRepository $config,
    ) {}

    public function channel(string $channel): OtpService
    {
        $channelEnum = Channel::tryFrom($channel);

        if ($channelEnum === null) {
            throw new InvalidArgumentException('Unknown OTP channel: '.$channel);
        }

        return $this->resolveService($channelEnum);
    }

    public function sms(): OtpService
    {
        return $this->channel(Channel::SMS->value);
    }

    public function whatsapp(): OtpService
    {
        return $this->channel(Channel::WHATSAPP->value);
    }

    public function send(string $phone, string $purpose = 'verification'): OtpResult
    {
        return $this->sms()->send($phone, $purpose);
    }

    public function verify(string $phone, string $code, string $purpose = 'verification'): bool
    {
        return $this->sms()->verify($phone, $code, $purpose);
    }

    public function resend(string $phone, string $purpose = 'verification'): OtpResult
    {
        return $this->sms()->resend($phone, $purpose);
    }

    public function isValid(string $phone, string $purpose = 'verification'): bool
    {
        return $this->sms()->isValid($phone, $purpose);
    }

    public function remainingAttempts(string $phone, string $purpose = 'verification'): int
    {
        return $this->sms()->remainingAttempts($phone, $purpose);
    }

    public function invalidate(string $phone, string $purpose = 'verification'): void
    {
        $this->sms()->invalidate($phone, $purpose);
    }

    private function resolveService(Channel $channel): OtpService
    {
        $key = $channel->value;

        if (isset($this->services[$key])) {
            return $this->services[$key];
        }

        $gateway = match ($channel) {
            Channel::SMS => $this->afrikSmsGateway,
            Channel::WHATSAPP => $this->twilioWhatsAppGateway,
        };

        $manager = new SmsManager($gateway);
        $service = new OtpService($manager, $this->cache, $this->config);

        $this->services[$key] = $service;

        return $service;
    }
}
