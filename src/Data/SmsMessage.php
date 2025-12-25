<?php

declare(strict_types=1);

namespace Ratoufa\Messaging\Data;

final readonly class SmsMessage
{
    public function __construct(
        public string $recipient,
        public string $content,
        public ?string $senderId = null,
    ) {}

    /**
     * @return array{recipient: string, content: string, sender_id: string|null}
     */
    public function toArray(): array
    {
        return [
            'recipient' => $this->recipient,
            'content' => $this->content,
            'sender_id' => $this->senderId,
        ];
    }
}
