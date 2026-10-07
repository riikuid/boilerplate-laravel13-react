<?php

namespace App\Exceptions;

/**
 * The record exists but a business rule forbids changing it
 * (e.g. deleting yourself, removing the last super admin).
 */
class ProtectedRecordException extends BusinessException
{
    public function __construct(string $message, ?string $errorCode = 'protected_record')
    {
        parent::__construct($message, 409, $errorCode);
    }
}
