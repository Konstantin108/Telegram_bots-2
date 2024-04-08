<?php

namespace Project\Models\Users;

use Project\Models\ActiveRecordEntity;

class User extends ActiveRecordEntity
{
    protected string $chatId;
    protected bool|null $isBot;
    protected string|null $firstName;
    protected string|null $lastName;
    protected string|null $username;
    protected bool $isAdmin;
    protected string $status;
    protected bool|null $notification;
    protected string|null $languageCode;
    protected string|null $createdAt;

    protected static function getTableName(): string
    {
        return "users";
    }

    /**
     * @param string $chatId
     */
    public function setChatId(string $chatId): void
    {
        $this->chatId = $chatId;
    }

    /**
     * @param bool|null $isBot
     */
    public function setIsBot(?bool $isBot): void
    {
        $this->isBot = $isBot;
    }

    /**
     * @param string|null $firstName
     */
    public function setFirstName(?string $firstName): void
    {
        $this->firstName = $firstName;
    }

    /**
     * @param string|null $lastName
     */
    public function setLastName(?string $lastName): void
    {
        $this->lastName = $lastName;
    }

    /**
     * @param string|null $username
     */
    public function setUsername(?string $username): void
    {
        $this->username = $username;
    }

    /**
     * @param bool $isAdmin
     */
    public function setIsAdmin(bool $isAdmin): void
    {
        $this->isAdmin = $isAdmin;
    }

    /**
     * @param string $status
     */
    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    /**
     * @param string|null $languageCode
     */
    public function setLanguageCode(?string $languageCode): void
    {
        $this->languageCode = $languageCode;
    }

    /**
     * @return string
     */
    public function getChatId(): string
    {
        return $this->chatId;
    }

    /**
     * @return string|null
     */
    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    /**
     * @return string|null
     */
    public function getLastName(): ?string
    {
        return $this->lastName;
    }
}