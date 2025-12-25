<?php

declare(strict_types=1);

namespace Ratoufa\Messaging\Contracts;

use Ratoufa\Messaging\Data\BulkMessage;
use Ratoufa\Messaging\Data\PersonalizedMessage;
use Ratoufa\Messaging\Data\Response;

interface WhatsAppGatewayInterface extends GatewayInterface
{
    /**
     * Send a WhatsApp template message.
     *
     * @param  array<string, string>  $variables
     */
    public function sendTemplate(string $recipient, string $contentSid, array $variables = []): Response;

    /**
     * Send media (image, document, etc.) via WhatsApp.
     */
    public function sendMedia(string $recipient, string $mediaUrl, ?string $caption = null): Response;

    /**
     * Send the same message to multiple recipients.
     */
    public function sendBulk(BulkMessage $message): Response;

    /**
     * Send different messages to different recipients.
     *
     * @param  list<PersonalizedMessage>  $messages
     */
    public function sendPersonalized(array $messages, ?string $senderId = null): Response;
}
