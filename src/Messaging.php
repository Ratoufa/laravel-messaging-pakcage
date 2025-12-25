<?php

declare(strict_types=1);

namespace Ratoufa\Messaging;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Container\Container;
use InvalidArgumentException;
use Ratoufa\Messaging\Contracts\GatewayInterface;
use Ratoufa\Messaging\Contracts\WhatsAppGatewayInterface;
use Ratoufa\Messaging\Enums\Channel;
use Ratoufa\Messaging\Gateways\AfrikSmsGateway;
use Ratoufa\Messaging\Gateways\TwilioWhatsAppGateway;
use Ratoufa\Messaging\Pending\PendingSms;
use Ratoufa\Messaging\Pending\PendingWhatsApp;
use Ratoufa\Messaging\Services\OtpService;
use Ratoufa\Messaging\Services\SmsManager;

final class Messaging
{
    /** @var array<string, GatewayInterface> */
    private array $gateways = [];

    private readonly Channel $defaultChannel;

    public function __construct(
        private readonly Container $container,
        private readonly ConfigRepository $config,
    ) {
        $default = $this->config->get('messaging.default', 'sms');
        $this->defaultChannel = Channel::tryFrom($default) ?? Channel::SMS;
    }

    public function channel(?string $name = null): SmsManager
    {
        $name ??= $this->defaultChannel->value;

        return new SmsManager($this->resolveGateway($name));
    }

    public function sms(): SmsManager
    {
        return $this->channel(Channel::SMS->value);
    }

    public function smsTo(string $phone): PendingSms
    {
        return new PendingSms($this->sms(), $phone);
    }

    public function otp(): OtpService
    {
        return $this->container->make(OtpService::class);
    }

    public function whatsapp(): SmsManager
    {
        $gateway = $this->resolveGateway(Channel::WHATSAPP->value);

        if (! $gateway instanceof WhatsAppGatewayInterface) {
            throw new InvalidArgumentException('WhatsApp gateway must implement WhatsAppGatewayInterface');
        }

        return new SmsManager($gateway);
    }

    public function whatsappTo(string $phone): PendingWhatsApp
    {
        $gateway = $this->resolveGateway(Channel::WHATSAPP->value);

        if (! $gateway instanceof WhatsAppGatewayInterface) {
            throw new InvalidArgumentException('WhatsApp gateway must implement WhatsAppGatewayInterface');
        }

        return new PendingWhatsApp($gateway, $phone);
    }

    public function gateway(string $name): GatewayInterface
    {
        return $this->resolveGateway($name);
    }

    public function extend(string $name, GatewayInterface $gateway): void
    {
        $this->gateways[$name] = $gateway;
    }

    public function getDefaultChannel(): Channel
    {
        return $this->defaultChannel;
    }

    private function resolveGateway(string $name): GatewayInterface
    {
        if (isset($this->gateways[$name])) {
            return $this->gateways[$name];
        }

        $gateway = match ($name) {
            'sms', 'afriksms' => $this->container->make(AfrikSmsGateway::class),
            'whatsapp', 'twilio' => $this->container->make(TwilioWhatsAppGateway::class),
            default => throw new InvalidArgumentException('Unknown messaging channel: '.$name),
        };

        $this->gateways[$name] = $gateway;

        return $gateway;
    }
}
