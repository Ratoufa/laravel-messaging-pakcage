<?php

declare(strict_types=1);

namespace Ratoufa\Messaging\Contracts;

use Illuminate\Support\Collection;
use Ratoufa\Messaging\Data\BalanceInfo;
use Ratoufa\Messaging\Data\Response;
use Ratoufa\Messaging\Data\SmsMessage;

interface GatewayInterface
{
    /**
     * Send a single message.
     */
    public function send(SmsMessage $message): Response;

    /**
     * Get account balance.
     *
     * @return Collection<int, BalanceInfo>
     */
    public function getBalance(): Collection;
}
