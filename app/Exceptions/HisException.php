<?php

namespace App\Exceptions;

use Exception;

class HisException extends Exception
{
    public function __construct($message = "系统出错", $code = 0, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
