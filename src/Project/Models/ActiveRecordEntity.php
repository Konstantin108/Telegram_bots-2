<?php

namespace Project\Models;

use Error;
use Project\Exceptions\AccessModifiersException;
use Project\Exceptions\DbException;
use Project\Services\DB;
use ReflectionObject;

abstract class ActiveRecordEntity
{
    protected int $id;

    /**
     * @param string $name
     * @param ?string $value
     * @return void
     * @throws AccessModifiersException
     */
    public function __set(string $name, string|null $value = "")
    {
        try {
            $camelCaseName = $this->underscoreToCamelCase($name);
            $this->$camelCaseName = $value;
        } catch (Error $e) {
            throw new AccessModifiersException($e->getMessage());
        }
    }

    /**
     * @param string $name
     * @return string
     */
    private function underscoreToCamelCase(string $name): string
    {
        return lcfirst(str_replace("_", "", ucwords($name, "_")));
    }

    /**
     * @param string $name
     * @return string
     */
    private function camelCaseToUnderscore(string $name): string
    {
        return strtolower(preg_replace("/(?<!^)[A-Z]/", "_$0", $name));
    }

    /**
     * @param string $value
     * @param string $param
     * @return mixed|null
     * @throws DbException
     */
    public static function getByParam(string $value, string $param): mixed
    {
        $result = static::getDB()->query(
            "/** @lang text */SELECT * FROM `" . static::getTableName() . "` WHERE `$param` = :$param;",
            [$param => $value],
            static::class
        );
        return $result
            ? array_shift($result)
            : null;
    }

    /**
     * @param int $id
     * @return mixed
     * @throws DbException
     */
    public static function getById(int $id): mixed
    {
        return static::getByParam($id, "id");
    }

    /**
     * @param string $chatId
     * @return mixed|null
     * @throws DbException
     */
    public static function getByChatId(string $chatId): mixed
    {
        return static::getByParam($chatId, "chat_id");
    }

    /**
     * @param array $filterArray
     * @return array|bool|null
     * @throws DbException
     */
    public static function findAllByParams(array $filterArray = []): bool|array|null
    {
        $filter = "";
        $values = [];
        if ($filterArray) {
            foreach ($filterArray as $key => $value) {
                [$operator, $fieldName] = explode("|", $key);
                $filter .= " AND `$fieldName` $operator :$fieldName";
                $values[$fieldName] = $value;
            }
        }

        return static::getDB()->query(
            "/** @lang text */SELECT * FROM `" . static::getTableName() . "` WHERE 1 = 1" . $filter . ";",
            $values,
            static::class
        );
    }

    /**
     * @return void
     * @throws DbException
     */
    public function save(): void
    {
        empty($this->id)
            ? $this->insert()
            : $this->update();
    }

    /**
     * @return void
     * @throws DbException
     */
    private function insert(): void
    {
        $fields = [];
        $values = [];
        $columns = [];
        foreach ($this as $fieldName => $value) {
            if (!$value) continue;
            $fieldName = $this->camelCaseToUnderscore($fieldName);
            $fields[] = ":$fieldName";
            $values[$fieldName] = $value;
            $columns[] = "`$fieldName`";
        }
        $sql = sprintf(
            "/** @lang text */INSERT INTO `%s` (%s) VALUES (%s);",
            static::getTableName(),
            implode(", ", $columns),
            implode(", ", $fields)
        );
        static::getDB()->query($sql, $values, static::class);
        $this->id = static::getDB()->getLastInsertId();
        $this->refresh();
    }

    /**
     * @return void
     * @throws DbException
     */
    private function refresh(): void
    {
        $objectFromDb = static::getById($this->id);
        $reflector = new ReflectionObject($objectFromDb);
        $properties = $reflector->getProperties();

        foreach ($properties as $property) {
            $propertyName = $property->getName();
            $this->$propertyName = $property->getValue($objectFromDb);
        }
    }

    /**
     * @return void
     * @throws DbException
     */
    private function update(): void
    {
        $fields = [];
        $values = [];
        foreach ($this as $fieldName => $value) {
            if ($fieldName === "id") continue;
            $fieldName = $this->camelCaseToUnderscore($fieldName);
            $fields[] = "`$fieldName` = :$fieldName";
            $values[$fieldName] = $value;
        }
        $sql = "UPDATE `" . static::getTableName() . "` SET " . implode(", ", $fields) . " WHERE `id` = " . $this->id . ";";
        static::getDB()->query($sql, $values, static::class);
    }

    /**
     * @return string
     */
    abstract protected static function getTableName(): string;

    /**
     * @return DB
     */
    protected static function getDB(): DB
    {
        return DB::getInstance();
    }
}