<?php

declare(strict_types=1);

namespace Ratoufa\Messaging\Facades;

use Illuminate\Support\Facades\Facade;
use Ratoufa\Messaging\Contracts\GatewayInterface;
use Ratoufa\Messaging\Enums\Channel;
use Ratoufa\Messaging\Pending\PendingWhatsApp;
use Ratoufa\Messaging\Services\SmsManager;

/**
 * @method static SmsManager channel(?string $name = null)
 * @method static SmsManager sms()
 * @method static SmsManager whatsapp()
 * @method static PendingWhatsApp whatsappTo(string $phone)
 * @method static GatewayInterface gateway(string $name)
 * @method static void extend(string $name, GatewayInterface $gateway)
 * @method static Channel getDefaultChannel()
 *
 * @see \Ratoufa\Messaging\Messaging
 */
final class Messaging extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Ratoufa\Messaging\Messaging::class;
    }
}
