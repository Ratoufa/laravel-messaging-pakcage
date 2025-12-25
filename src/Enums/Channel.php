<?php

declare(strict_types=1);

namespace Ratoufa\Messaging\Enums;

enum Channel: string
{
    case SMS = 'sms';
    case WHATSAPP = 'whatsapp';

    public function isSms(): bool
    {
        return $this === self::SMS;
    }

    public function isWhatsApp(): bool
    {
        return $this === self::WHATSAPP;
    }
}
