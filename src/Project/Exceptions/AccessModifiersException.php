<?php

namespace Project\Exceptions;

use Exception;

class AccessModifiersException extends MainException
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