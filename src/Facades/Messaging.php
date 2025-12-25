<?php

declare(strict_types=1);

namespace Ratoufa\Messaging\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Ratoufa\Messaging\Messaging
 */
final class Messaging extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Ratoufa\Messaging\Messaging::class;
    }
}
