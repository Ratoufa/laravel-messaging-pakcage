<?php

declare(strict_types=1);

namespace Ratoufa\Messaging\Pending;

use InvalidArgumentException;
use Ratoufa\Messaging\Contracts\WhatsAppGatewayInterface;
use Ratoufa\Messaging\Data\Response;
use Ratoufa\Messaging\Data\SmsMessage;

final class PendingWhatsApp
{
    private ?string $templateSid = null;

    /** @var array<string, string> */
    private array $templateVariables = [];

    private ?string $mediaUrl = null;

    public function __construct(
        private readonly WhatsAppGatewayInterface $gateway,
        private readonly string $recipient,
    ) {}

    /**
     * @param  array<string, string>  $variables
     */
    public function template(string $templateSid, array $variables = []): self
    {
        $this->templateSid = $templateSid;
        $this->templateVariables = $variables;

        return $this;
    }

    public function media(string $url): self
    {
        $this->mediaUrl = $url;

        return $this;
    }

    public function send(?string $content = null): Response
    {
        if ($this->templateSid !== null) {
            return $this->gateway->sendTemplate(
                $this->recipient,
                $this->templateSid,
                $this->templateVariables
            );
        }

        if ($this->mediaUrl !== null) {
            return $this->gateway->sendMedia(
                $this->recipient,
                $this->mediaUrl,
                $content
            );
        }

        if ($content === null) {
            throw new InvalidArgumentException('Message content is required');
        }

        return $this->gateway->send(new SmsMessage(
            recipient: $this->recipient,
            content: $content,
        ));
    }
}
