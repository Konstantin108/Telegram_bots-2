<?php

namespace Project\Services;

use JetBrains\PhpStorm\ArrayShape;
use Project\Exceptions\DbException;
use PDOException;
use PDOStatement;

use PDO;

class DB
{
    private static null|DB $instance = null;
    private mixed $config;
    private PDO $conn;

    private function __construct()
    {
        $this->config = (require __DIR__ . "/../../config.php")["bots"]["pets"]["db"];
    }

    /**
     * @return DB|null
     */
    public static function getInstance(): ?DB
    {
        return !self::$instance
            ? self::$instance = new self()
            : self::$instance;
    }

    /**
     * @return PDO
     * @throws DbException
     */
    private function getConnection(): PDO
    {
        if (empty($this->conn)) {
            try {
                $this->conn = new PDO(
                    $this->getSdn(),
                    $this->config["user"],
                    $this->config["password"],
                    $this->getOptions()
                );
            } catch (PDOException $e) {
                throw new DbException($e->getMessage());
            }
        }
        return $this->conn;
    }

    /**
     * @return string
     */
    private function getSdn(): string
    {
        return sprintf(
            "%s:host=%s;dbname=%s;charset=%s",
            $this->config["driver"],
            $this->config["host"],
            $this->config["dbname"],
            $this->config["charset"]
        );
    }

    /**
     * @return array
     */
    #[ArrayShape(shape: [PDO::ATTR_ERRMODE => "int", PDO::ATTR_DEFAULT_FETCH_MODE => "int"])]
    private function getOptions(): array
    {
        return [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_CLASS
        ];
    }

    /**
     * @param string $sql
     * @param array $params
     * @return bool|PDOStatement|null
     * @throws DbException
     */
    private function exec(string $sql, array $params = []): bool|PDOStatement|null
    {
        $PDOStatement = $this->getConnection()->prepare($sql);
        return $PDOStatement->execute($params)
            ? $PDOStatement
            : null;
    }

    /**
     * @param string $sql
     * @param array $params
     * @param string $className
     * @return array|false|null
     * @throws DbException
     */
    public function query(string $sql, array $params = [], string $className = "stdClass"): bool|array|null
    {
        try {
            $PDOStatement = $this->exec($sql, $params);
            return $PDOStatement
                ? $PDOStatement->fetchAll(PDO::FETCH_CLASS, $className)
                : null;
        } catch (PDOException $e) {
            throw new DbException($e->getMessage());
        }
    }

    /**
     * @return bool|string
     * @throws DbException
     */
    public function getLastInsertId(): bool|string
    {
        return $this->getConnection()->lastInsertId();
    }
}