<?php

declare(strict_types=1);

namespace Ratoufa\Messaging\Enums;

enum ResponseCode: int
{
    case SUCCESS = 100;
    case PARTIAL_SUCCESS = 101;
    case INVALID_CREDENTIALS = 401;
    case INSUFFICIENT_BALANCE = 402;
    case INVALID_RECIPIENT = 422;
    case SERVER_ERROR = 500;
    case UNSUPPORTED_OPERATION = 501;
    case UNKNOWN = 999;

    public function isSuccess(): bool
    {
        return $this === self::SUCCESS || $this === self::PARTIAL_SUCCESS;
    }

    public function isError(): bool
    {
        return ! $this->isSuccess();
    }

    public function description(): string
    {
        return match ($this) {
            self::SUCCESS => 'Operation completed successfully',
            self::PARTIAL_SUCCESS => 'Operation partially completed',
            self::INVALID_CREDENTIALS => 'Invalid API credentials',
            self::INSUFFICIENT_BALANCE => 'Insufficient balance or quota exceeded',
            self::INVALID_RECIPIENT => 'Invalid recipient phone number',
            self::SERVER_ERROR => 'Server error occurred',
            self::UNSUPPORTED_OPERATION => 'Operation not supported by this gateway',
            self::UNKNOWN => 'Unknown error occurred',
        };
    }
}
