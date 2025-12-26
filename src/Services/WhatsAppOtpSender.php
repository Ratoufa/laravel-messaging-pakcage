<?php

declare(strict_types=1);

namespace Ratoufa\Messaging\Services;

use Ratoufa\Messaging\Contracts\OtpSenderInterface;
use Ratoufa\Messaging\Contracts\WhatsAppGatewayInterface;
use Ratoufa\Messaging\Data\Response;
use Ratoufa\Messaging\Data\SmsMessage;

final readonly class WhatsAppOtpSender implements OtpSenderInterface
{
    public function __construct(
        private WhatsAppGatewayInterface $gateway,
        private string $templateSid,
        private string $codeVariable = '1',
    ) {}

    public function send(SmsMessage $message): Response
    {
        return $this->gateway->sendTemplate(
            recipient: $message->recipient,
            contentSid: $this->templateSid,
            variables: [$this->codeVariable => $message->content],
        );
    }
}
