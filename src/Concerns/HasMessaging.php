<?php

declare(strict_types=1);

namespace Ratoufa\Messaging\Concerns;

use Ratoufa\Messaging\Data\OtpResult;
use Ratoufa\Messaging\Data\Response;
use Ratoufa\Messaging\Data\SmsMessage;
use Ratoufa\Messaging\Facades\Messaging;
use Ratoufa\Messaging\Facades\Otp;
use Ratoufa\Messaging\Facades\Sms;
use Ratoufa\Messaging\Facades\WhatsApp;

/**
 * @property string|null $phone
 * @property string|null $phone_number
 * @property string|null $whatsapp
 */
trait HasMessaging
{
    public function routeNotificationForSms(): string
    {
        return $this->phone ?? $this->phone_number ?? '';
    }

    public function routeNotificationForWhatsApp(): string
    {
        return $this->whatsapp ?? $this->phone ?? $this->phone_number ?? '';
    }

    public function sendSms(string $content, ?string $senderId = null): Response
    {
        return Sms::send(new SmsMessage(
            recipient: $this->routeNotificationForSms(),
            content: $content,
            senderId: $senderId,
        ));
    }

    public function sendWhatsApp(string $content): Response
    {
        return WhatsApp::to($this->routeNotificationForWhatsApp())->send($content);
    }

    /**
     * @param  array<string, string>  $variables
     */
    public function sendWhatsAppTemplate(string $templateSid, array $variables = []): Response
    {
        return WhatsApp::to($this->routeNotificationForWhatsApp())
            ->template($templateSid, $variables)
            ->send();
    }

    public function sendWhatsAppMedia(string $mediaUrl, ?string $caption = null): Response
    {
        return WhatsApp::to($this->routeNotificationForWhatsApp())
            ->media($mediaUrl)
            ->send($caption);
    }

    public function sendMessage(string $content, string $channel = 'sms'): Response
    {
        $phone = $channel === 'whatsapp'
            ? $this->routeNotificationForWhatsApp()
            : $this->routeNotificationForSms();

        return Messaging::channel($channel)->to($phone)->send($content);
    }

    public function sendOtp(string $purpose = 'verification'): OtpResult
    {
        return Otp::send($this->routeNotificationForSms(), $purpose);
    }

    public function verifyOtp(string $code, string $purpose = 'verification'): bool
    {
        return Otp::verify($this->routeNotificationForSms(), $code, $purpose);
    }

    public function hasValidOtp(string $purpose = 'verification'): bool
    {
        return Otp::isValid($this->routeNotificationForSms(), $purpose);
    }

    public function otpRemainingAttempts(string $purpose = 'verification'): int
    {
        return Otp::remainingAttempts($this->routeNotificationForSms(), $purpose);
    }

    public function invalidateOtp(string $purpose = 'verification'): void
    {
        Otp::invalidate($this->routeNotificationForSms(), $purpose);
    }
}
