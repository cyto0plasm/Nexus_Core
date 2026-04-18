<?php

namespace App\Exceptions;

use Exception;

class ApiException extends Exception
{
    public function __construct(string $message, int $status = 400)
    {
        parent::__construct($message, $status);
    }
}
