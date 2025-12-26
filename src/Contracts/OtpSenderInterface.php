<?php

declare(strict_types=1);

namespace Ratoufa\Messaging\Contracts;

use Ratoufa\Messaging\Data\Response;
use Ratoufa\Messaging\Data\SmsMessage;

interface OtpSenderInterface
{
    public function send(SmsMessage $message): Response;
}
