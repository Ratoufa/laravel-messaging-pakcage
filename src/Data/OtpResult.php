<?php

declare(strict_types=1);

namespace Ratoufa\Messaging\Data;

use Carbon\CarbonInterface;

final readonly class OtpResult
{
    public function __construct(
        public bool $success,
        public ?string $code,
        public CarbonInterface $expiresAt,
        public Response $response,
    ) {}

    public function failed(): bool
    {
        return ! $this->success;
    }

    public function expiresInMinutes(): int
    {
        return (int) now()->diffInMinutes($this->expiresAt);
    }

    public function isExpired(): bool
    {
        return $this->expiresAt->isPast();
    }
}
