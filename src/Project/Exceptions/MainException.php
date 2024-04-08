<?php

namespace Project\Exceptions;

use Exception;

abstract class MainException extends Exception
{
    protected bool $writeLog;
    protected string $logFile;

    public function __construct()
    {
        $logSettings = (require __DIR__ . "/../../config.php")["log"];

        parent::__construct();
        $this->writeLog = $logSettings["writeLog"];
        $this->logFile = $logSettings["logFile"];
    }

    /**
     * @return void
     */
    public function writeLog(): void
    {
        if ($this->writeLog) {
            $text = PHP_EOL
                . "------------------------------ "
                . date("Y-m-d H:i:s")
                . " ------------------------------"
                . PHP_EOL
                . $this->__toString()
                . PHP_EOL;
            error_log($text, 3, $this->logFile);
        }
    }

    /**
     * @return void
     */
    public function showError(): void
    {
        echo "<pre>";
        print_r($this->__toString());
        echo "</pre>";
    }
}