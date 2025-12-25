<?php

declare(strict_types=1);

namespace Ratoufa\Messaging\Enums;

enum DeliveryStatus: string
{
    case PENDING = 'pending';
    case SENT = 'sent';
    case DELIVERED = 'delivered';
    case FAILED = 'failed';

    public function isPending(): bool
    {
        return $this === self::PENDING;
    }

    public function isSent(): bool
    {
        return $this === self::SENT;
    }

    public function isDelivered(): bool
    {
        return $this === self::DELIVERED;
    }

    public function isFailed(): bool
    {
        return $this === self::FAILED;
    }

    public function isTerminal(): bool
    {
        return $this === self::DELIVERED || $this === self::FAILED;
    }
}
