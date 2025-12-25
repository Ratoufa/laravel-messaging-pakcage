<?php

declare(strict_types=1);

namespace Ratoufa\Messaging\Commands;

use Exception;
use Illuminate\Console\Command;
use Ratoufa\Messaging\Data\BalanceInfo;
use Ratoufa\Messaging\Facades\Sms;
use Ratoufa\Messaging\Facades\WhatsApp;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\error;
use function Laravel\Prompts\info;
use function Laravel\Prompts\outro;
use function Laravel\Prompts\select;
use function Laravel\Prompts\spin;
use function Laravel\Prompts\table;
use function Laravel\Prompts\text;

final class MessagingCommand extends Command
{
    /** @var string */
    protected $signature = 'messaging
                            {action? : Action to perform (balance|callback|test)}
                            {--channel= : Channel to use (sms|whatsapp)}
                            {--url= : Callback URL for callback action}
                            {--method= : Callback method (GET or POST)}
                            {--to= : Phone number for test action}
                            {--message= : Message for test action}';

    /** @var string */
    protected $description = 'Manage messaging service (check balance, configure callback, send test)';

    public function handle(): int
    {
        $action = $this->argument('action') ?? $this->selectAction();

        return match ($action) {
            'balance' => $this->checkBalance(),
            'callback' => $this->configureCallback(),
            'test' => $this->sendTest(),
            default => $this->handleUnknownAction(),
        };
    }

    private function selectAction(): string
    {
        return select(
            label: 'What would you like to do?',
            options: [
                'balance' => 'Check account balance',
                'test' => 'Send a test message',
                'callback' => 'Configure delivery callback URL',
            ],
            default: 'balance',
        );
    }

    private function selectChannel(): string
    {
        /** @var string|null $channel */
        $channel = $this->option('channel');

        if ($channel !== null && in_array($channel, ['sms', 'whatsapp'], true)) {
            return $channel;
        }

        return select(
            label: 'Which channel?',
            options: [
                'sms' => 'SMS (AfrikSMS)',
                'whatsapp' => 'WhatsApp (Twilio)',
            ],
            default: 'sms',
        );
    }

    private function checkBalance(): int
    {
        $channel = $this->selectChannel();

        try {
            $balances = spin(
                callback: fn () => $channel === 'whatsapp'
                    ? WhatsApp::getBalance()
                    : Sms::getBalance(),
                message: 'Fetching balance...',
            );

            if ($balances->isEmpty()) {
                info('No balance information available.');

                return self::SUCCESS;
            }

            table(
                headers: ['Country/Account', 'Balance'],
                rows: $balances->map(fn (BalanceInfo $b): array => [$b->country, number_format($b->balance)])->toArray(),
            );

            $total = $balances->sum('balance');
            outro(sprintf('Total: %s credits', $total));

            return self::SUCCESS;
        } catch (Exception $exception) {
            error('Failed to fetch balance: '.$exception->getMessage());

            return self::FAILURE;
        }
    }

    private function configureCallback(): int
    {
        /** @var string|null $url */
        $url = $this->option('url');

        if ($url === null) {
            $url = text(
                label: 'Enter the callback URL',
                placeholder: 'https://example.com/webhook/sms',
                required: 'Callback URL is required.',
                validate: fn (string $value): ?string => filter_var($value, FILTER_VALIDATE_URL) === false
                    ? 'Please enter a valid URL.'
                    : null,
            );
        }

        /** @var string|null $methodOption */
        $methodOption = $this->option('method');

        $method = $methodOption ?? select(
            label: 'HTTP method for the callback',
            options: [
                'POST' => 'POST (Recommended)',
                'GET' => 'GET',
            ],
            default: 'POST',
        );

        if (! confirm(sprintf('Configure callback to %s using %s?', $url, $method), default: true)) {
            info('Operation cancelled.');

            return self::SUCCESS;
        }

        try {
            $response = spin(
                callback: fn () => Sms::configureCallback($url, $method),
                message: 'Configuring callback...',
            );

            if ($response->success) {
                outro('Callback configured successfully!');

                return self::SUCCESS;
            }

            error('Failed: '.$response->message);

            return self::FAILURE;
        } catch (Exception $exception) {
            error('Failed to configure callback: '.$exception->getMessage());

            return self::FAILURE;
        }
    }

    private function sendTest(): int
    {
        $channel = $this->selectChannel();

        /** @var string|null $to */
        $to = $this->option('to');

        if ($to === null) {
            $to = text(
                label: 'Enter the recipient phone number',
                placeholder: '22890123456',
                required: 'Phone number is required.',
                validate: fn (string $value): ?string => mb_strlen(preg_replace('/\D/', '', $value) ?? '') < 8
                    ? 'Please enter a valid phone number.'
                    : null,
            );
        }

        /** @var string|null $messageOption */
        $messageOption = $this->option('message');

        $appName = config('app.name', 'Laravel');
        $message = $messageOption ?? text(
            label: 'Enter the message to send',
            placeholder: 'Hello from Laravel Messaging!',
            default: 'Test message from '.$appName,
            required: 'Message is required.',
        );

        $channelLabel = $channel === 'whatsapp' ? 'WhatsApp' : 'SMS';

        if (! confirm(sprintf('Send %s to %s?', $channelLabel, $to), default: true)) {
            info('Operation cancelled.');

            return self::SUCCESS;
        }

        try {
            $response = spin(
                callback: fn () => $channel === 'whatsapp'
                    ? WhatsApp::to($to)->send($message)
                    : Sms::to($to)->send($message),
                message: sprintf('Sending %s message...', $channelLabel),
            );

            if ($response->success) {
                outro('Message sent successfully! Resource ID: '.$response->resourceId);

                return self::SUCCESS;
            }

            error('Failed: '.$response->message);

            return self::FAILURE;
        } catch (Exception $exception) {
            error('Failed to send message: '.$exception->getMessage());

            return self::FAILURE;
        }
    }

    private function handleUnknownAction(): int
    {
        error('Unknown action. Use: balance, callback, or test');

        return self::FAILURE;
    }
}
