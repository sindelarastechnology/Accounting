<?php

namespace App\Exceptions;

use RuntimeException;

class AccountingImbalanceException extends RuntimeException
{
    public function __construct(string $message = 'Total debit dan kredit tidak seimbang.', int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
