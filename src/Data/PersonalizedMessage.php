<?php

declare(strict_types=1);

namespace Ratoufa\Messaging\Data;

final readonly class PersonalizedMessage
{
    public function __construct(
        public string $recipient,
        public string $content,
    ) {}

    /**
     * @return array{MobileNumbers: string, Message: string}
     */
    public function toArray(): array
    {
        return [
            'MobileNumbers' => $this->recipient,
            'Message' => $this->content,
        ];
    }
}
