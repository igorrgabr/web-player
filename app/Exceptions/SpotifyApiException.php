<?php

namespace App\Exceptions;

use RuntimeException;

class SpotifyApiException extends RuntimeException
{
    private int $statusCode;
    private ?int $retryAfterSeconds;

    public function __construct(string $message, int $statusCode = 500, ?int $retryAfterSeconds = null)
    {
        parent::__construct($message, $statusCode);
        $this->statusCode = $statusCode;
        $this->retryAfterSeconds = $retryAfterSeconds;
    }

    public function statusCode(): int
    {
        return $this->statusCode;
    }

    public function retryAfterSeconds(): ?int
    {
        return $this->retryAfterSeconds;
    }
}
