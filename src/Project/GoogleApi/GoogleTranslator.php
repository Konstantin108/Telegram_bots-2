<?php

namespace Project\GoogleApi;

use Project\Exceptions\ConnException;
use Project\Services\Conn;

class GoogleTranslator
{
    /**
     * @param string $text
     * @return mixed
     * @throws ConnException
     */
    public static function translate(string $text): mixed
    {
        $url = (require __DIR__ . "/../../config.php")["bots"]["googleTranslateBot"]["googleApiUrl"];
        $data = [
            "client" => "gtx",
            "sl" => "en",
            "tl" => "ru",
            "dt" => "t",
            "q" => $text
        ];
        $response = (new Conn($url))->getResult($data, "get");

        return array_reduce(array_shift($response), function ($a, $b) {
            return $a . $b[0];
        });
    }
}