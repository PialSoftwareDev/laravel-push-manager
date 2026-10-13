<?php

namespace Pialsoft\LaravelPushManager\Exceptions;

use RuntimeException;

class FcmRequestException extends RuntimeException
{
    public function __construct(
        string $message,
        private readonly int $httpStatus,
        private readonly ?string $fcmErrorCode = null,
        private readonly bool $retryable = false,
        private readonly bool $invalidToken = false
    ) {
        parent::__construct($message, $httpStatus);
    }

    public function httpStatus(): int
    {
        return $this->httpStatus;
    }

    public function fcmErrorCode(): ?string
    {
        return $this->fcmErrorCode;
    }

    public function isRetryable(): bool
    {
        return $this->retryable;
    }

    public function isInvalidToken(): bool
    {
        return $this->invalidToken;
    }
}
