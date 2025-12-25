<?php

declare(strict_types=1);

namespace Ratoufa\Messaging\Exceptions;

use Exception;
use Ratoufa\Messaging\Enums\ResponseCode;

final class MessagingException extends Exception
{
    /**
     * @param  array<string, mixed>  $context
     */
    public function __construct(
        string $message,
        public readonly ResponseCode $errorCode = ResponseCode::UNKNOWN,
        public readonly array $context = [],
    ) {
        parent::__construct($message, $errorCode->value);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public static function sendFailed(string $reason, array $context = []): self
    {
        return new self(
            message: 'Failed to send message: '.$reason,
            errorCode: ResponseCode::SERVER_ERROR,
            context: $context,
        );
    }

    public static function invalidRecipient(string $recipient): self
    {
        return new self(
            message: 'Invalid recipient phone number: '.$recipient,
            errorCode: ResponseCode::INVALID_RECIPIENT,
        );
    }

    public static function configurationMissing(string $key): self
    {
        return new self(
            message: 'Missing messaging configuration: '.$key,
            errorCode: ResponseCode::SERVER_ERROR,
        );
    }

    public static function apiError(ResponseCode $code, string $message): self
    {
        return new self(
            message: sprintf('API error [%s]: %s', $code->value, $message),
            errorCode: $code,
        );
    }

    public static function quotaExceeded(): self
    {
        return new self(
            message: 'Message quota exceeded. Please check your balance.',
            errorCode: ResponseCode::INSUFFICIENT_BALANCE,
        );
    }

    public static function unsupportedOperation(string $operation, string $gateway): self
    {
        return new self(
            message: sprintf('%s gateway does not support %s.', $gateway, $operation),
            errorCode: ResponseCode::UNSUPPORTED_OPERATION,
        );
    }

    public static function invalidCredentials(): self
    {
        return new self(
            message: 'Invalid API credentials.',
            errorCode: ResponseCode::INVALID_CREDENTIALS,
        );
    }
}
