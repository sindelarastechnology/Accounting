<?php

namespace App\Exceptions;

use Exception;

class InvalidAccountException extends Exception
{
    public function __construct(string $message = 'Akun tidak valid atau merupakan akun header.')
    {
        parent::__construct($message);
    }
}
