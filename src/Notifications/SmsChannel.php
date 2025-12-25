<?php

declare(strict_types=1);

namespace Ratoufa\Messaging\Notifications;

use Illuminate\Notifications\Notification;
use Ratoufa\Messaging\Data\Response;
use Ratoufa\Messaging\Data\SmsMessage;
use Ratoufa\Messaging\Services\SmsManager;

final readonly class SmsChannel
{
    public function __construct(
        private SmsManager $smsManager,
    ) {}

    public function send(object $notifiable, Notification $notification): ?Response
    {
        if (! method_exists($notification, 'toSms')) {
            return null;
        }

        /** @var SmsMessage|string|null $message */
        $message = $notification->toSms($notifiable);

        if ($message === null) {
            return null;
        }

        if (is_string($message)) {
            $message = new SmsMessage(
                recipient: $this->getRecipient($notifiable),
                content: $message,
            );
        }

        if ($message->recipient === '') {
            $message = new SmsMessage(
                recipient: $this->getRecipient($notifiable),
                content: $message->content,
                senderId: $message->senderId,
            );
        }

        return $this->smsManager->send($message);
    }

    private function getRecipient(object $notifiable): string
    {
        if (method_exists($notifiable, 'routeNotificationForSms')) {
            return (string) $notifiable->routeNotificationForSms();
        }

        /** @var string|null $phone */
        $phone = $notifiable->phone ?? $notifiable->phone_number ?? null;

        return $phone ?? '';
    }
}
