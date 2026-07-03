<?php

namespace App\Exceptions;

use Exception;

class RingoverApiException extends Exception
{
    protected array $context = [];

    public function __construct(string $message = "", int $code = 0, ?Exception $previous = null, array $context = [])
    {
        parent::__construct($message, $code, $previous);
        $this->context = $context;
    }

    public static function unauthorized(array $context = []): self
    {
        return new self(
            'Ringover API unauthorized. Check API key or enable Monitoring for this endpoint.',
            401,
            null,
            $context
        );
    }

    public static function rateLimitExceeded(int $retryAfter = 60, array $context = []): self
    {
        return new self(
            "Ringover API rate limit exceeded. Retry after {$retryAfter} seconds.",
            429,
            null,
            array_merge($context, ['retry_after' => $retryAfter])
        );
    }

    public static function paymentRequired(array $context = []): self
    {
        return new self(
            'Ringover license level insufficient. Upgrade required.',
            402,
            null,
            $context
        );
    }

    public static function notAcceptable(string $reason = 'Invalid data', array $context = []): self
    {
        return new self(
            "Ringover API request not acceptable: {$reason}",
            406,
            null,
            array_merge($context, ['reason' => $reason])
        );
    }

    public function getContext(): array
    {
        return $this->context;
    }
}
