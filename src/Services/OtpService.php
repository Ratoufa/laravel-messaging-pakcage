<?php

declare(strict_types=1);

namespace Ratoufa\Messaging\Services;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Ratoufa\Messaging\Contracts\OtpSenderInterface;
use Ratoufa\Messaging\Data\OtpResult;
use Ratoufa\Messaging\Data\SmsMessage;
use Ratoufa\Messaging\Enums\Channel;

final readonly class OtpService
{
    private int $length;

    private int $expiryMinutes;

    private int $maxAttempts;

    private string $messageTemplate;

    public function __construct(
        private OtpSenderInterface $sender,
        private CacheRepository $cache,
        private ConfigRepository $config,
        private Channel $channel = Channel::SMS,
    ) {
        $this->length = $this->config->get('messaging.otp.length', 6);
        $this->expiryMinutes = $this->config->get('messaging.otp.expiry_minutes', 10);
        $this->maxAttempts = $this->config->get('messaging.otp.max_attempts', 3);
        $this->messageTemplate = $this->config->get(
            'messaging.otp.message',
            'Your verification code is: {code}. Valid for {expiry} minutes.'
        );
    }

    public function send(string $phone, string $purpose = 'verification'): OtpResult
    {
        $code = $this->generateCode();
        $cacheKey = $this->getCacheKey($phone, $purpose);

        $this->cache->put(
            $cacheKey,
            [
                'code' => $code,
                'attempts' => 0,
                'created_at' => now()->toIso8601String(),
            ],
            now()->addMinutes($this->expiryMinutes)
        );

        // For WhatsApp templates: send just the code (template handles the message format)
        // For SMS: send the full formatted message
        $content = $this->channel->isWhatsApp() ? $code : $this->buildMessage($code);
        $response = $this->sender->send(new SmsMessage($phone, $content));

        return new OtpResult(
            success: $response->success,
            code: $response->success ? $code : null,
            expiresAt: now()->addMinutes($this->expiryMinutes),
            response: $response,
        );
    }

    public function verify(string $phone, string $code, string $purpose = 'verification'): bool
    {
        $cacheKey = $this->getCacheKey($phone, $purpose);

        /** @var array{code: string, attempts: int, created_at: string}|null $data */
        $data = $this->cache->get($cacheKey);

        if ($data === null) {
            return false;
        }

        if ($data['attempts'] >= $this->maxAttempts) {
            $this->cache->forget($cacheKey);

            return false;
        }

        if ($data['code'] !== $code) {
            $newAttempts = $data['attempts'] + 1;

            if ($newAttempts >= $this->maxAttempts) {
                $this->cache->forget($cacheKey);
            } else {
                $this->cache->put(
                    $cacheKey,
                    [
                        ...$data,
                        'attempts' => $newAttempts,
                    ],
                    now()->addMinutes($this->expiryMinutes)
                );
            }

            return false;
        }

        $this->cache->forget($cacheKey);

        return true;
    }

    public function resend(string $phone, string $purpose = 'verification'): OtpResult
    {
        $this->cache->forget($this->getCacheKey($phone, $purpose));

        return $this->send($phone, $purpose);
    }

    public function isValid(string $phone, string $purpose = 'verification'): bool
    {
        return $this->cache->has($this->getCacheKey($phone, $purpose));
    }

    public function remainingAttempts(string $phone, string $purpose = 'verification'): int
    {
        /** @var array{code: string, attempts: int, created_at: string}|null $data */
        $data = $this->cache->get($this->getCacheKey($phone, $purpose));

        if ($data === null) {
            return 0;
        }

        return max(0, $this->maxAttempts - $data['attempts']);
    }

    public function invalidate(string $phone, string $purpose = 'verification'): void
    {
        $this->cache->forget($this->getCacheKey($phone, $purpose));
    }

    private function generateCode(): string
    {
        return mb_str_pad(
            (string) random_int(0, (int) (10 ** $this->length) - 1),
            $this->length,
            '0',
            STR_PAD_LEFT
        );
    }

    private function getCacheKey(string $phone, string $purpose): string
    {
        return sprintf('messaging:otp:%s:%s', $purpose, $phone);
    }

    private function buildMessage(string $code): string
    {
        return str_replace(
            ['{code}', '{expiry}'],
            [$code, (string) $this->expiryMinutes],
            $this->messageTemplate
        );
    }
}
