<?php

declare(strict_types=1);

namespace Ratoufa\Messaging\Data;

final readonly class BalanceInfo
{
    public function __construct(
        public string $country,
        public int $balance,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            country: (string) ($data['countryName'] ?? $data['country'] ?? 'Unknown'),
            balance: (int) ($data['balance'] ?? $data['solde'] ?? 0),
        );
    }

    public function hasCredit(): bool
    {
        return $this->balance > 0;
    }
}
