<?php

namespace Project\Exceptions;

use Exception;

class DbException extends MainException
{
    /**
     * @param string $errorMessage
     */
    public function __construct(string $errorMessage)
    {
        Exception::__construct("Проблема с БД $errorMessage");
        parent::__construct();
    }
}