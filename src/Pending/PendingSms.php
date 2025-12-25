<?php

declare(strict_types=1);

namespace Ratoufa\Messaging\Pending;

use Ratoufa\Messaging\Data\Response;
use Ratoufa\Messaging\Data\SmsMessage;
use Ratoufa\Messaging\Services\SmsManager;

final class PendingSms
{
    private ?string $senderId = null;

    private ?string $email = null;

    private ?string $emailSubject = null;

    public function __construct(
        private readonly SmsManager $manager,
        private readonly string $recipient,
    ) {}

    public function from(string $senderId): self
    {
        $this->senderId = $senderId;

        return $this;
    }

    public function withEmail(string $email, string $subject): self
    {
        $this->email = $email;
        $this->emailSubject = $subject;

        return $this;
    }

    public function send(string $content): Response
    {
        $message = new SmsMessage(
            recipient: $this->recipient,
            content: $content,
            senderId: $this->senderId,
        );

        if ($this->email !== null && $this->emailSubject !== null) {
            return $this->manager->sendWithEmail($message, $this->email, $this->emailSubject);
        }

        return $this->manager->send($message);
    }
}
