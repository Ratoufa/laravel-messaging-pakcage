<?php

declare(strict_types=1);

namespace Ratoufa\Messaging\Data;

final readonly class BulkMessage
{
    /**
     * @param  list<string>  $recipients
     */
    public function __construct(
        public array $recipients,
        public string $content,
        public ?string $senderId = null,
    ) {}

    public function recipientsAsString(): string
    {
        return implode(',', $this->recipients);
    }

    public function count(): int
    {
        return count($this->recipients);
    }

    /**
     * @return array{recipients: list<string>, content: string, sender_id: string|null}
     */
    public function toArray(): array
    {
        return [
            'recipients' => $this->recipients,
            'content' => $this->content,
            'sender_id' => $this->senderId,
        ];
    }
}
