<?php

namespace App\Exceptions;

use RuntimeException;
use Throwable;

/**
 * Base class for every business-rule violation raised from a Service.
 *
 * Throw it (or a domain subclass) instead of abort(), a generic Exception or a
 * `return false`. The global handler in bootstrap/app.php renders it:
 * a toast + redirect back for Inertia requests, JSON for API requests.
 */
class BusinessException extends RuntimeException
{
    public function __construct(
        string $message,
        protected int $status = 422,
        protected ?string $errorCode = null,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }

    public function status(): int
    {
        return $this->status;
    }

    public function errorCode(): ?string
    {
        return $this->errorCode;
    }
}
