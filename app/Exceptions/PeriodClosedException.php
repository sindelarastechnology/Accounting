<?php

namespace App\Exceptions;

use RuntimeException;

class PeriodClosedException extends RuntimeException
{
    public function __construct(string $message = 'Periode sudah ditutup.', int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
