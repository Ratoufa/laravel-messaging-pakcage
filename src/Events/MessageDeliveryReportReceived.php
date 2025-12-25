<?php

declare(strict_types=1);

namespace Ratoufa\Messaging\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Ratoufa\Messaging\Enums\Channel;
use Ratoufa\Messaging\Enums\DeliveryStatus;

final class MessageDeliveryReportReceived
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly string $resourceId,
        public readonly DeliveryStatus $status,
        public readonly string $code,
        public readonly string $message,
        public readonly Channel $channel = Channel::SMS,
    ) {}

    public function isDelivered(): bool
    {
        return $this->status->isDelivered();
    }

    public function isFailed(): bool
    {
        return $this->status->isFailed();
    }

    public function isSent(): bool
    {
        return $this->status->isSent();
    }

    public function isPending(): bool
    {
        return $this->status->isPending();
    }
}
