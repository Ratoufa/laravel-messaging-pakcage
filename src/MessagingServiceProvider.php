<?php

declare(strict_types=1);

namespace Ratoufa\Messaging;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Container\Container;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Notifications\ChannelManager;
use Illuminate\Support\Facades\Notification;
use Ratoufa\Messaging\Commands\MessagingCommand;
use Ratoufa\Messaging\Contracts\SmsGatewayInterface;
use Ratoufa\Messaging\Contracts\WhatsAppGatewayInterface;
use Ratoufa\Messaging\Gateways\AfrikSmsGateway;
use Ratoufa\Messaging\Gateways\TwilioWhatsAppGateway;
use Ratoufa\Messaging\Notifications\SmsChannel;
use Ratoufa\Messaging\Notifications\WhatsAppChannel;
use Ratoufa\Messaging\Services\OtpManager;
use Ratoufa\Messaging\Services\OtpService;
use Ratoufa\Messaging\Services\SmsManager;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

final class MessagingServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-messaging')
            ->hasConfigFile('messaging')
            ->hasTranslations()
            ->hasCommand(MessagingCommand::class);
    }

    public function packageRegistered(): void
    {
        $this->registerGateways();
        $this->registerServices();
    }

    public function packageBooted(): void
    {
        $this->registerNotificationChannels();
    }

    private function registerGateways(): void
    {
        $this->app->singleton(AfrikSmsGateway::class, fn (Container $app): AfrikSmsGateway => new AfrikSmsGateway(
            $app->make(ConfigRepository::class),
            $app->make(HttpFactory::class),
        ));

        $this->app->singleton(TwilioWhatsAppGateway::class, fn (Container $app): TwilioWhatsAppGateway => new TwilioWhatsAppGateway(
            $app->make(ConfigRepository::class),
        ));

        $this->app->bind(SmsGatewayInterface::class, function (Container $app): SmsGatewayInterface {
            /** @var ConfigRepository $config */
            $config = $app->make(ConfigRepository::class);
            $default = $config->get('messaging.default', 'sms');

            return match ($default) {
                'whatsapp' => $app->make(TwilioWhatsAppGateway::class),
                default => $app->make(AfrikSmsGateway::class),
            };
        });

        $this->app->bind(WhatsAppGatewayInterface::class, fn (Container $app): WhatsAppGatewayInterface => $app->make(TwilioWhatsAppGateway::class));
    }

    private function registerServices(): void
    {
        $this->app->singleton(
            Messaging::class,
            fn (Container $app): Messaging => new Messaging($app, $app->make(ConfigRepository::class)),
        );

        $this->app->singleton(SmsManager::class, fn (Container $app): SmsManager => new SmsManager(
            $app->make(SmsGatewayInterface::class),
        ));

        $this->app->singleton(OtpService::class, fn (Container $app): OtpService => new OtpService(
            $app->make(SmsManager::class),
            $app->make(CacheRepository::class),
            $app->make(ConfigRepository::class),
        ));

        $this->app->singleton(OtpManager::class, fn (Container $app): OtpManager => new OtpManager(
            $app->make(AfrikSmsGateway::class),
            $app->make(TwilioWhatsAppGateway::class),
            $app->make(CacheRepository::class),
            $app->make(ConfigRepository::class),
        ));
    }

    private function registerNotificationChannels(): void
    {
        Notification::resolved(function (ChannelManager $service): void {
            $service->extend('sms', fn (Container $app): SmsChannel => $app->make(SmsChannel::class));
            $service->extend('whatsapp', fn (Container $app): WhatsAppChannel => $app->make(WhatsAppChannel::class));
        });
    }
}
