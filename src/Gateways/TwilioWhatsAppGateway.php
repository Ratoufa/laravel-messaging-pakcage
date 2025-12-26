<?php

declare(strict_types=1);

namespace Ratoufa\Messaging\Gateways;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Ratoufa\Messaging\Contracts\WhatsAppGatewayInterface;
use Ratoufa\Messaging\Data\BalanceInfo;
use Ratoufa\Messaging\Data\BulkMessage;
use Ratoufa\Messaging\Data\Response;
use Ratoufa\Messaging\Data\SmsMessage;
use Ratoufa\Messaging\Enums\ResponseCode;
use Ratoufa\Messaging\Exceptions\MessagingException;
use Ratoufa\Messaging\Support\PhoneFormatter;
use Throwable;
use Twilio\Exceptions\TwilioException;
use Twilio\Rest\Api\V2010\Account\MessageInstance;
use Twilio\Rest\Client;

final readonly class TwilioWhatsAppGateway implements WhatsAppGatewayInterface
{
    private string $from;

    private string $logChannel;

    private Client $client;

    public function __construct(
        private ConfigRepository $config,
    ) {
        $sid = $this->config->get('messaging.twilio.sid')
            ?? throw MessagingException::configurationMissing('messaging.twilio.sid');
        $authToken = $this->config->get('messaging.twilio.auth_token')
            ?? throw MessagingException::configurationMissing('messaging.twilio.auth_token');
        $this->from = $this->config->get('messaging.twilio.whatsapp_from')
            ?? throw MessagingException::configurationMissing('messaging.twilio.whatsapp_from');
        $this->logChannel = $this->config->get('messaging.logging.channel', 'stack');

        $this->client = new Client($sid, $authToken);
    }

    public function send(SmsMessage $message): Response
    {
        $to = PhoneFormatter::formatForWhatsApp($message->recipient);

        try {
            $twilioMessage = $this->client->messages->create($to, [
                'from' => $this->from,
                'body' => $message->content,
            ]);

            $this->logMessage('send', ['to' => $to], $twilioMessage);

            return $this->buildResponse($twilioMessage);
        } catch (TwilioException $twilioException) {
            $this->logError('send', ['to' => $to], $twilioException);

            return $this->buildErrorResponse($twilioException);
        }
    }

    public function sendBulk(BulkMessage $message): Response
    {
        /** @var list<array{phone: string, success: bool, resourceId: string|null}> $results */
        $results = [];
        $allSuccess = true;

        foreach ($message->recipients as $recipient) {
            $smsMessage = new SmsMessage(
                recipient: $recipient,
                content: $message->content,
                senderId: $message->senderId,
            );

            $result = $this->send($smsMessage);
            $results[] = [
                'phone' => $recipient,
                'success' => $result->success,
                'resourceId' => $result->resourceId,
            ];

            if ($result->failed()) {
                $allSuccess = false;
            }
        }

        return new Response(
            success: $allSuccess,
            code: $allSuccess ? ResponseCode::SUCCESS : ResponseCode::PARTIAL_SUCCESS,
            message: $allSuccess ? 'All messages sent' : 'Some messages failed',
            data: ['results' => $results],
        );
    }

    public function sendPersonalized(array $messages, ?string $senderId = null): Response
    {
        /** @var list<array{phone: string, success: bool, resourceId: string|null}> $results */
        $results = [];
        $allSuccess = true;

        foreach ($messages as $msg) {
            $smsMessage = new SmsMessage(
                recipient: $msg->recipient,
                content: $msg->content,
                senderId: $senderId,
            );

            $result = $this->send($smsMessage);
            $results[] = [
                'phone' => $msg->recipient,
                'success' => $result->success,
                'resourceId' => $result->resourceId,
            ];

            if ($result->failed()) {
                $allSuccess = false;
            }
        }

        return new Response(
            success: $allSuccess,
            code: $allSuccess ? ResponseCode::SUCCESS : ResponseCode::PARTIAL_SUCCESS,
            message: $allSuccess ? 'All messages sent' : 'Some messages failed',
            data: ['results' => $results],
        );
    }

    public function getBalance(): Collection
    {
        try {
            $balance = $this->client->balance->fetch();

            return collect([
                new BalanceInfo(
                    country: 'Twilio Account',
                    balance: (int) floor((float) ($balance->balance ?? 0)),
                ),
            ]);
        } catch (Throwable) {
            return collect([]);
        }
    }

    /**
     * @param  array<string, string>  $variables
     */
    public function sendTemplate(string $recipient, string $contentSid, array $variables = []): Response
    {
        $to = PhoneFormatter::formatForWhatsApp($recipient);

        try {
            $options = [
                'from' => $this->from,
                'contentSid' => $contentSid,
            ];

            if ($variables !== []) {
                $options['contentVariables'] = json_encode($variables, JSON_THROW_ON_ERROR);
            }

            $twilioMessage = $this->client->messages->create($to, $options);

            $this->logMessage('sendTemplate', ['to' => $to, 'template' => $contentSid], $twilioMessage);

            return $this->buildResponse($twilioMessage);
        } catch (TwilioException $twilioException) {
            $this->logError('sendTemplate', ['to' => $to, 'template' => $contentSid], $twilioException);

            return $this->buildErrorResponse($twilioException);
        }
    }

    public function sendMedia(string $recipient, string $mediaUrl, ?string $caption = null): Response
    {
        $to = PhoneFormatter::formatForWhatsApp($recipient);

        try {
            $options = [
                'from' => $this->from,
                'mediaUrl' => [$mediaUrl],
            ];

            if ($caption !== null) {
                $options['body'] = $caption;
            }

            $twilioMessage = $this->client->messages->create($to, $options);

            $this->logMessage('sendMedia', ['to' => $to, 'media' => $mediaUrl], $twilioMessage);

            return $this->buildResponse($twilioMessage);
        } catch (TwilioException $twilioException) {
            $this->logError('sendMedia', ['to' => $to, 'media' => $mediaUrl], $twilioException);

            return $this->buildErrorResponse($twilioException);
        }
    }

    private function buildResponse(MessageInstance $message): Response
    {
        return new Response(
            success: true,
            code: ResponseCode::SUCCESS,
            message: 'Message sent successfully',
            resourceId: $message->sid,
            data: [
                'sid' => $message->sid,
                'status' => $message->status,
                'dateCreated' => $message->dateCreated?->format('c'),
                'direction' => $message->direction,
            ],
        );
    }

    private function buildErrorResponse(TwilioException $exception): Response
    {
        $errorCode = $exception->getCode();

        $code = match ($errorCode) {
            20003, 401 => ResponseCode::INVALID_CREDENTIALS,
            21211, 21614 => ResponseCode::INVALID_RECIPIENT,
            21608, 21610, 21612 => ResponseCode::INSUFFICIENT_BALANCE,
            63016 => ResponseCode::TEMPLATE_REQUIRED,
            default => ResponseCode::UNKNOWN,
        };

        return new Response(
            success: false,
            code: $code,
            message: $exception->getMessage(),
            data: [
                'error_code' => $errorCode,
                'error_message' => $exception->getMessage(),
            ],
        );
    }

    /**
     * @param  array<string, mixed>  $params
     */
    private function logMessage(string $method, array $params, MessageInstance $message): void
    {
        Log::channel($this->logChannel)
            ->info('TwilioWhatsApp::'.$method, [
                'params' => $params,
                'sid' => $message->sid,
                'status' => $message->status,
            ]);
    }

    /**
     * @param  array<string, mixed>  $params
     */
    private function logError(string $method, array $params, TwilioException $exception): void
    {
        Log::channel($this->logChannel)
            ->error('TwilioWhatsApp::'.$method, [
                'params' => $params,
                'error_code' => $exception->getCode(),
                'error_message' => $exception->getMessage(),
            ]);
    }
}
