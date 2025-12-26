<?php

declare(strict_types=1);

namespace Ratoufa\Messaging\Services;

use Illuminate\Support\Collection;
use Ratoufa\Messaging\Contracts\GatewayInterface;
use Ratoufa\Messaging\Contracts\OtpSenderInterface;
use Ratoufa\Messaging\Contracts\SmsGatewayInterface;
use Ratoufa\Messaging\Data\BalanceInfo;
use Ratoufa\Messaging\Data\BulkMessage;
use Ratoufa\Messaging\Data\PersonalizedMessage;
use Ratoufa\Messaging\Data\Response;
use Ratoufa\Messaging\Data\SmsMessage;
use Ratoufa\Messaging\Exceptions\MessagingException;
use Ratoufa\Messaging\Pending\PendingBulkSms;
use Ratoufa\Messaging\Pending\PendingSms;

final readonly class SmsManager implements OtpSenderInterface
{
    public function __construct(
        private GatewayInterface $gateway,
    ) {}

    public function using(GatewayInterface $gateway): self
    {
        return new self($gateway);
    }

    public function send(SmsMessage $message): Response
    {
        return $this->gateway->send($message);
    }

    public function sendBulk(BulkMessage $message): Response
    {
        if (! $this->gateway instanceof SmsGatewayInterface) {
            throw MessagingException::unsupportedOperation('sendBulk', $this->gateway::class);
        }

        return $this->gateway->sendBulk($message);
    }

    /**
     * @param  list<PersonalizedMessage>  $messages
     */
    public function sendPersonalized(array $messages, ?string $senderId = null): Response
    {
        if (! $this->gateway instanceof SmsGatewayInterface) {
            throw MessagingException::unsupportedOperation('sendPersonalized', $this->gateway::class);
        }

        return $this->gateway->sendPersonalized($messages, $senderId);
    }

    public function sendWithEmail(SmsMessage $message, string $email, string $subject): Response
    {
        if (! $this->gateway instanceof SmsGatewayInterface) {
            throw MessagingException::unsupportedOperation('sendWithEmail', $this->gateway::class);
        }

        return $this->gateway->sendWithEmail($message, $email, $subject);
    }

    /**
     * @return Collection<int, BalanceInfo>
     */
    public function getBalance(): Collection
    {
        return $this->gateway->getBalance();
    }

    public function configureCallback(string $url, string $method = 'POST'): Response
    {
        if (! $this->gateway instanceof SmsGatewayInterface) {
            throw MessagingException::unsupportedOperation('configureCallback', $this->gateway::class);
        }

        return $this->gateway->configureCallback($url, $method);
    }

    public function to(string $phone): PendingSms
    {
        return new PendingSms($this, $phone);
    }

    /**
     * @param  list<string>  $phones
     */
    public function toMany(array $phones): PendingBulkSms
    {
        return new PendingBulkSms($this, $phones);
    }

    public function getGateway(): GatewayInterface
    {
        return $this->gateway;
    }
}
