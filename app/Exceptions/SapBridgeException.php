<?php

namespace App\Exceptions;

use RuntimeException;

class SapBridgeException extends RuntimeException
{
    protected int $statusCode;

    public function __construct(string $message, int $statusCode = 500, protected ?array $responseBody = null, ?\Throwable $previous = null)
    {
        $this->statusCode = $statusCode;

        parent::__construct($message, $statusCode, $previous);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getResponseBody(): ?array
    {
        return $this->responseBody;
    }
}

