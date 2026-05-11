<?php

namespace App\Exceptions;

use Exception;

class InvalidOperationException extends Exception
{
    public function __construct(string $message = 'Operasi tidak diizinkan.')
    {
        parent::__construct($message);
    }
}
