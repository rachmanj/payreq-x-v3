<?php

namespace App\Exceptions;

use RuntimeException;

class OpenRouterException extends RuntimeException
{
    public function __construct(string $message, int $statusCode = 500, protected ?array $responseBody = null, ?\Throwable $previous = null)
    {
        parent::__construct($message, $statusCode, $previous);
    }

    public function getStatusCode(): int
    {
        return $this->code;
    }

    public function getResponseBody(): ?array
    {
        return $this->responseBody;
    }
}
