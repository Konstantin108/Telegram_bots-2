<?php

namespace Project\Exceptions;

use Exception;

class TypeErrorException extends MainException
{
    /**
     * @param string $errorMessage
     */
    public function __construct(string $errorMessage)
    {
        Exception::__construct($errorMessage);
        parent::__construct();
    }
}