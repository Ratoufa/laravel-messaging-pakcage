<?php

declare(strict_types=1);

namespace Ratoufa\Messaging\Tests;

use Illuminate\Database\Eloquent\Factories\Factory;
use Orchestra\Testbench\TestCase as Orchestra;
use Override;
use Ratoufa\Messaging\MessagingServiceProvider;

abstract class TestCase extends Orchestra
{
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName): string => 'Ratoufa\\Messaging\\Database\\Factories\\'.class_basename($modelName).'Factory'
        );
    }

    protected function getEnvironmentSetUp($app): void
    {
        config()->set('database.default', 'testing');

        /*
        foreach (\Illuminate\Support\Facades\File::allFiles(__DIR__.'/../database/migrations') as $migration) {
            (include $migration->getRealPath())->up();
        }
        */
    }

    protected function getPackageProviders($app): array
    {
        return [
            MessagingServiceProvider::class,
        ];
    }
}
