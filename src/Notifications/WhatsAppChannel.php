<?php

declare(strict_types=1);

namespace Ratoufa\Messaging\Notifications;

use Illuminate\Notifications\Notification;
use Ratoufa\Messaging\Contracts\WhatsAppGatewayInterface;
use Ratoufa\Messaging\Data\Response;
use Ratoufa\Messaging\Data\SmsMessage;

final readonly class WhatsAppChannel
{
    public function __construct(
        private WhatsAppGatewayInterface $gateway,
    ) {}

    public function send(object $notifiable, Notification $notification): ?Response
    {
        if (method_exists($notification, 'toWhatsApp')) {
            return $this->sendWhatsAppMessage($notifiable, $notification);
        }

        if (method_exists($notification, 'toSms')) {
            return $this->sendSmsMessage($notifiable, $notification);
        }

        return null;
    }

    private function sendWhatsAppMessage(object $notifiable, Notification $notification): ?Response
    {
        /** @var array<string, mixed>|string|object|null $message */
        $message = $notification->toWhatsApp($notifiable); // @phpstan-ignore method.notFound

        if ($message === null) {
            return null;
        }

        if (is_array($message) && isset($message['template'])) {
            /** @var array<string, string> $variables */
            $variables = $message['variables'] ?? [];

            return $this->gateway->sendTemplate(
                $this->getRecipient($notifiable),
                (string) $message['template'],
                $variables
            );
        }

        if (is_array($message) && isset($message['media'])) {
            return $this->gateway->sendMedia(
                $this->getRecipient($notifiable),
                (string) $message['media'],
                isset($message['caption']) ? (string) $message['caption'] : null
            );
        }

        $content = match (true) {
            is_string($message) => $message,
            is_array($message) && isset($message['content']) => (string) $message['content'],
            is_object($message) && property_exists($message, 'content') => (string) $message->content,
            default => '',
        };

        return $this->gateway->send(new SmsMessage(
            recipient: $this->getRecipient($notifiable),
            content: $content,
        ));
    }

    private function sendSmsMessage(object $notifiable, Notification $notification): ?Response
    {
        /** @var SmsMessage|string|null $message */
        $message = $notification->toSms($notifiable); // @phpstan-ignore method.notFound

        if ($message === null) {
            return null;
        }

        if (is_string($message)) {
            $message = new SmsMessage(
                recipient: $this->getRecipient($notifiable),
                content: $message,
            );
        }

        return $this->gateway->send($message);
    }

    private function getRecipient(object $notifiable): string
    {
        if (method_exists($notifiable, 'routeNotificationForWhatsApp')) {
            return (string) $notifiable->routeNotificationForWhatsApp();
        }

        if (method_exists($notifiable, 'routeNotificationForSms')) {
            return (string) $notifiable->routeNotificationForSms();
        }

        /** @var string|null $phone */
        $phone = $notifiable->phone ?? $notifiable->phone_number ?? null;

        return $phone ?? '';
    }
}
