<?php

declare(strict_types=1);

namespace Ratoufa\Messaging\Commands;

use Illuminate\Console\Command;

final class MessagingCommand extends Command
{
    public $signature = 'laravel-messaging';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
