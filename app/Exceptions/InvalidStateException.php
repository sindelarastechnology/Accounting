<?php

namespace App\Exceptions;

use RuntimeException;

class InvalidStateException extends RuntimeException
{
    public function __construct(string $message = 'Status dokumen tidak valid untuk operasi ini.', int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
