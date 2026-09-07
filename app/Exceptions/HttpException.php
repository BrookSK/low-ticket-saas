<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Excecao HTTP com codigo de status associado.
 */
class HttpException extends RuntimeException
{
    protected int $statusCode;

    public function __construct(int $statusCode, string $message = '', ?\Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
        $this->statusCode = $statusCode;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }
}
