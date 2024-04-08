<?php

namespace Project\Services;

use JetBrains\PhpStorm\ArrayShape;
use CurlHandle;
use Project\Exceptions\ConnException;

class Conn
{
    private false|CurlHandle $conn;
    private string $url;

    /**
     * @param string $url
     */
    public function __construct(string $url)
    {
        $this->conn = curl_init();
        $this->url = $url;
    }

    /**
     * @param array $query
     * @param string $method
     * @return bool|mixed|string
     * @throws ConnException
     */
    public function getResult(array $query, string $method): mixed
    {
        $result = $this->exec($query, $method);

        if ($msg = curl_error($this->conn)) {
            throw new ConnException($msg);
        }

        if (!$result) {
            throw new ConnException("Ошибка в URL");
        }

        return json_decode($result);
    }

    /**
     * @param array $data
     * @return array
     */
    #[ArrayShape(shape: [CURLOPT_URL => "string", CURLOPT_POST => "bool", CURLOPT_POSTFIELDS => "", CURLOPT_RETURNTRANSFER => "bool", CURLOPT_TIMEOUT => "int", CURLOPT_CONNECTTIMEOUT_MS => "int"])]
    private function post(array $data): array
    {
        return [
            CURLOPT_URL => $this->url,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $data,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_CONNECTTIMEOUT_MS => 6000
        ];
    }

    /**
     * @param array $data
     * @return array
     */
    #[ArrayShape(shape: [CURLOPT_URL => "string", CURLOPT_RETURNTRANSFER => "bool"])]
    private function get(array $data): array
    {
        return [
            CURLOPT_URL => $this->url . http_build_query($data),
            CURLOPT_RETURNTRANSFER => true
        ];
    }

    /**
     * @param array $data
     * @param string $method
     * @return bool|string
     */
    private function exec(array $data, string $method): bool|string
    {
        curl_setopt_array($this->conn, $this->$method($data));
        return curl_exec($this->conn);
    }
}