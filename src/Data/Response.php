<?php

declare(strict_types=1);

namespace Ratoufa\Messaging\Data;

use Ratoufa\Messaging\Enums\ResponseCode;

final readonly class Response
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(
        public bool $success,
        public ResponseCode $code,
        public string $message,
        public ?string $resourceId = null,
        public array $data = [],
    ) {}

    /**
     * @param  array<string, mixed>  $response
     */
    public static function fromApiResponse(array $response): self
    {
        $rawCode = $response['code'] ?? 999;
        $code = ResponseCode::tryFrom($rawCode) ?? ResponseCode::UNKNOWN;

        return new self(
            success: $code->isSuccess(),
            code: $code,
            message: $response['message'] ?? 'Unknown error',
            resourceId: $response['resourceId'] ?? null,
            data: $response['data'] ?? [],
        );
    }

    public static function success(string $message = 'Success', ?string $resourceId = null): self
    {
        return new self(
            success: true,
            code: ResponseCode::SUCCESS,
            message: $message,
            resourceId: $resourceId,
        );
    }

    public static function error(ResponseCode $code, string $message): self
    {
        return new self(
            success: false,
            code: $code,
            message: $message,
        );
    }

    public function failed(): bool
    {
        return ! $this->success;
    }
}
