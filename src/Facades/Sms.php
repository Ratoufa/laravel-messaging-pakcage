<?php

declare(strict_types=1);

namespace Ratoufa\Messaging\Facades;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Facade;
use Ratoufa\Messaging\Data\BalanceInfo;
use Ratoufa\Messaging\Data\BulkMessage;
use Ratoufa\Messaging\Data\PersonalizedMessage;
use Ratoufa\Messaging\Data\Response;
use Ratoufa\Messaging\Data\SmsMessage;
use Ratoufa\Messaging\Pending\PendingBulkSms;
use Ratoufa\Messaging\Pending\PendingSms;
use Ratoufa\Messaging\Services\SmsManager;

/**
 * @method static Response send(SmsMessage $message)
 * @method static Response sendBulk(BulkMessage $message)
 * @method static Response sendPersonalized(array<PersonalizedMessage> $messages, ?string $senderId = null)
 * @method static Response sendWithEmail(SmsMessage $message, string $email, string $subject)
 * @method static Collection<int, BalanceInfo> getBalance()
 * @method static Response configureCallback(string $url, string $method = 'POST')
 * @method static PendingSms to(string $phone)
 * @method static PendingBulkSms toMany(array $phones)
 *
 * @see SmsManager
 */
final class Sms extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return SmsManager::class;
    }
}
