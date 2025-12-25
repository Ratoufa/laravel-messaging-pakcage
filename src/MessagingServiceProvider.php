<?php

declare(strict_types=1);

namespace Ratoufa\Messaging;

use Ratoufa\Messaging\Commands\MessagingCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

final class MessagingServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('laravel-messaging')
            ->hasConfigFile()
            ->hasViews()
            ->hasMigration('create_laravel_messaging_table')
            ->hasCommand(MessagingCommand::class);
    }
}
