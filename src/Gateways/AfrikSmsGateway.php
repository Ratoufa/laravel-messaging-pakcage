<?php

declare(strict_types=1);

namespace Ratoufa\Messaging\Gateways;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Ratoufa\Messaging\Contracts\SmsGatewayInterface;
use Ratoufa\Messaging\Data\BalanceInfo;
use Ratoufa\Messaging\Data\BulkMessage;
use Ratoufa\Messaging\Data\PersonalizedMessage;
use Ratoufa\Messaging\Data\Response;
use Ratoufa\Messaging\Data\SmsMessage;
use Ratoufa\Messaging\Enums\ResponseCode;
use Ratoufa\Messaging\Exceptions\MessagingException;
use Ratoufa\Messaging\Support\PhoneFormatter;
use Throwable;

final readonly class AfrikSmsGateway implements SmsGatewayInterface
{
    private const int MAX_BULK_RECIPIENTS = 500;

    private string $clientId;

    private string $apiKey;

    private string $senderId;

    private string $baseUrl;

    private int $timeout;

    /** @var array{times: int, sleep: int} */
    private array $retryConfig;

    private string $logChannel;

    public function __construct(
        private ConfigRepository $config,
        private HttpFactory $http,
    ) {
        $this->clientId = $this->config->get('messaging.afriksms.client_id')
            ?? throw MessagingException::configurationMissing('messaging.afriksms.client_id');
        $this->apiKey = $this->config->get('messaging.afriksms.api_key')
            ?? throw MessagingException::configurationMissing('messaging.afriksms.api_key');
        $this->senderId = $this->config->get('messaging.afriksms.sender_id', 'MyApp');
        $this->baseUrl = $this->config->get('messaging.afriksms.base_url', 'https://api.afriksms.com/api/web/web_v1/outbounds');
        $this->timeout = $this->config->get('messaging.afriksms.timeout', 30);
        $this->retryConfig = $this->config->get('messaging.afriksms.retry', ['times' => 3, 'sleep' => 100]);
        $this->logChannel = $this->config->get('messaging.logging.channel', 'stack');
    }

    public function send(SmsMessage $message): Response
    {
        $recipient = PhoneFormatter::format($message->recipient);

        if (! PhoneFormatter::isValid($recipient)) {
            throw MessagingException::invalidRecipient($message->recipient);
        }

        $response = $this->request()->get('/send', [
            'ClientId' => $this->clientId,
            'ApiKey' => $this->apiKey,
            'SenderId' => $message->senderId ?? $this->senderId,
            'Message' => $message->content,
            'MobileNumbers' => $recipient,
        ]);

        /** @var array<string, mixed> $json */
        $json = $response->json();

        $this->logRequest('send', ['recipient' => $recipient], $json);

        return Response::fromApiResponse($json);
    }

    public function sendBulk(BulkMessage $message): Response
    {
        if ($message->count() > self::MAX_BULK_RECIPIENTS) {
            throw MessagingException::sendFailed(
                sprintf('Maximum %d recipients allowed per bulk request', self::MAX_BULK_RECIPIENTS)
            );
        }

        $recipients = PhoneFormatter::formatMany($message->recipients);

        /** @phpstan-ignore argument.type (Laravel multipart format) */
        $response = $this->request()->asMultipart()->post('/send_multisms', [
            ['name' => 'ClientId', 'contents' => $this->clientId],
            ['name' => 'ApiKey', 'contents' => $this->apiKey],
            ['name' => 'SenderId', 'contents' => $message->senderId ?? $this->senderId],
            ['name' => 'Message', 'contents' => $message->content],
            ['name' => 'MobileNumbers', 'contents' => implode(',', $recipients)],
        ]);

        /** @var array<string, mixed> $json */
        $json = $response->json();

        $this->logRequest('sendBulk', ['count' => $message->count()], $json);

        return Response::fromApiResponse($json);
    }

    public function sendPersonalized(array $messages, ?string $senderId = null): Response
    {
        if (count($messages) > self::MAX_BULK_RECIPIENTS) {
            throw MessagingException::sendFailed(
                sprintf('Maximum %d messages allowed per personalized request', self::MAX_BULK_RECIPIENTS)
            );
        }

        $contentMessage = array_map(
            fn (PersonalizedMessage $msg): array => [
                'MobileNumbers' => PhoneFormatter::format($msg->recipient),
                'Message' => $msg->content,
            ],
            $messages
        );

        /** @phpstan-ignore argument.type (Laravel multipart format) */
        $response = $this->request()->asMultipart()->post('/send_customer_multisms', [
            ['name' => 'ClientId', 'contents' => $this->clientId],
            ['name' => 'ApiKey', 'contents' => $this->apiKey],
            ['name' => 'SenderId', 'contents' => $senderId ?? $this->senderId],
            ['name' => 'ContentMessage', 'contents' => json_encode($contentMessage, JSON_THROW_ON_ERROR)],
        ]);

        /** @var array<string, mixed> $json */
        $json = $response->json();

        $this->logRequest('sendPersonalized', ['count' => count($messages)], $json);

        return Response::fromApiResponse($json);
    }

    public function sendWithEmail(SmsMessage $message, string $email, string $subject): Response
    {
        $recipient = PhoneFormatter::format($message->recipient);

        $response = $this->request()->get('/send_emailsms', [
            'ClientId' => $this->clientId,
            'ApiKey' => $this->apiKey,
            'SenderId' => $message->senderId ?? $this->senderId,
            'Message' => $message->content,
            'MobileNumbers' => $recipient,
            'Email' => $email,
            'Subject' => $subject,
        ]);

        /** @var array<string, mixed> $json */
        $json = $response->json();

        $this->logRequest('sendWithEmail', ['recipient' => $recipient, 'email' => $email], $json);

        return Response::fromApiResponse($json);
    }

    public function getBalance(): Collection
    {
        $response = $this->request()->get('/solde', [
            'ClientId' => $this->clientId,
            'ApiKey' => $this->apiKey,
        ]);

        /** @var array<string, mixed> $data */
        $data = $response->json();

        $code = ResponseCode::tryFrom($data['code'] ?? 999) ?? ResponseCode::UNKNOWN;

        if (! $code->isSuccess()) {
            throw MessagingException::apiError($code, $data['message'] ?? 'Unknown error');
        }

        /** @var list<array<string, mixed>> $information */
        $information = $data['information'] ?? [];

        return collect($information)
            ->map(fn (array $item): BalanceInfo => BalanceInfo::fromArray($item));
    }

    public function configureCallback(string $url, string $method = 'POST'): Response
    {
        $typeNotification = mb_strtoupper($method) === 'GET' ? 2 : 1;

        $response = $this->request()->get('/callback_url', [
            'ClientId' => $this->clientId,
            'ApiKey' => $this->apiKey,
            'notifyURL' => $url,
            'TypeNotification' => $typeNotification,
        ]);

        /** @var array<string, mixed> $json */
        $json = $response->json();

        return Response::fromApiResponse($json);
    }

    private function request(): PendingRequest
    {
        return $this->http->baseUrl($this->baseUrl)
            ->timeout($this->timeout)
            ->retry(
                $this->retryConfig['times'],
                $this->retryConfig['sleep'],
                fn (Throwable $exception): bool => $exception instanceof ConnectionException
            )
            ->throw();
    }

    /**
     * @param  array<string, mixed>  $params
     * @param  array<string, mixed>  $response
     */
    private function logRequest(string $method, array $params, array $response): void
    {
        Log::channel($this->logChannel)
            ->info('AfrikSMS::'.$method, [
                'params' => $params,
                'response_code' => $response['code'] ?? null,
                'response_message' => $response['message'] ?? null,
            ]);
    }
}
