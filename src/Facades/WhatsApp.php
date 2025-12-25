<?php

declare(strict_types=1);

namespace Ratoufa\Messaging\Facades;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Facade;
use Ratoufa\Messaging\Contracts\WhatsAppGatewayInterface;
use Ratoufa\Messaging\Data\BalanceInfo;
use Ratoufa\Messaging\Data\Response;
use Ratoufa\Messaging\Data\SmsMessage;
use Ratoufa\Messaging\Gateways\TwilioWhatsAppGateway;
use Ratoufa\Messaging\Pending\PendingWhatsApp;

/**
 * @method static Response send(SmsMessage $message)
 * @method static Response sendTemplate(string $recipient, string $templateSid, array $variables = [])
 * @method static Response sendMedia(string $recipient, string $mediaUrl, ?string $caption = null)
 * @method static Collection<int, BalanceInfo> getBalance()
 *
 * @see TwilioWhatsAppGateway
 */
final class WhatsApp extends Facade
{
    public static function to(string $phone): PendingWhatsApp
    {
        /** @var WhatsAppGatewayInterface $gateway */
        $gateway = app(WhatsAppGatewayInterface::class);

        return new PendingWhatsApp($gateway, $phone);
    }

    protected static function getFacadeAccessor(): string
    {
        return WhatsAppGatewayInterface::class;
    }
}
