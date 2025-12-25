<?php

declare(strict_types=1);

namespace Ratoufa\Messaging\Pending;

use Ratoufa\Messaging\Data\BulkMessage;
use Ratoufa\Messaging\Data\PersonalizedMessage;
use Ratoufa\Messaging\Data\Response;
use Ratoufa\Messaging\Services\SmsManager;

final class PendingBulkSms
{
    private ?string $senderId = null;

    /**
     * @param  list<string>  $recipients
     */
    public function __construct(
        private readonly SmsManager $manager,
        private readonly array $recipients,
    ) {}

    public function from(string $senderId): self
    {
        $this->senderId = $senderId;

        return $this;
    }

    public function send(string $content): Response
    {
        $message = new BulkMessage(
            recipients: $this->recipients,
            content: $content,
            senderId: $this->senderId,
        );

        return $this->manager->sendBulk($message);
    }

    /**
     * Send personalized messages to each recipient.
     *
     * @param  callable(string): string  $contentCallback
     */
    public function sendPersonalized(callable $contentCallback): Response
    {
        $messages = array_map(
            fn (string $recipient): PersonalizedMessage => new PersonalizedMessage(
                recipient: $recipient,
                content: $contentCallback($recipient),
            ),
            $this->recipients
        );

        return $this->manager->sendPersonalized($messages, $this->senderId);
    }
}
