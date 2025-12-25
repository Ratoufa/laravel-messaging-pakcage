<?php

declare(strict_types=1);

namespace Ratoufa\Messaging\Contracts;

use Ratoufa\Messaging\Data\BulkMessage;
use Ratoufa\Messaging\Data\PersonalizedMessage;
use Ratoufa\Messaging\Data\Response;
use Ratoufa\Messaging\Data\SmsMessage;

interface SmsGatewayInterface extends GatewayInterface
{
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

    /**
     * Send SMS with a copy to email.
     */
    public function sendWithEmail(SmsMessage $message, string $email, string $subject): Response;

    /**
     * Configure the delivery callback URL.
     */
    public function configureCallback(string $url, string $method = 'POST'): Response;
}
