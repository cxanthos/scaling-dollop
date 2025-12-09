<?php

declare(strict_types=1);

namespace App\Domains\Users\Models;

use DateTime;
use InvalidArgumentException;
use JsonSerializable;

readonly class UserModel implements JsonSerializable
{
    private ?int $id;
    private string $email;
    private ?string $password;
    private string $name;
    private string $employeeCode;
    private UserRole $role;
    private ?DateTime $createdAt;
    private ?DateTime $updatedAt;

    public function __construct(
        ?int      $id,
        string    $email,
        ?string   $password,
        string    $name,
        string    $employeeCode,
        UserRole  $role,
        ?DateTime $createdAt = null,
        ?DateTime $updatedAt = null
    ) {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Invalid email address.');
        }

        if ($password !== null && strlen($password) < 8) {
            throw new InvalidArgumentException('Password must be at least 8 characters if provided.');
        }

        if (empty(trim($name))) {
            throw new InvalidArgumentException('Name must not be empty.');
        }

        if (!preg_match('/^\d{7}$/', $employeeCode)) {
            throw new InvalidArgumentException('Employee code must be a 7 digit number.');
        }

        $this->id = $id;
        $this->email = $email;
        $this->password = $password;
        $this->name = trim($name);
        $this->employeeCode = $employeeCode;
        $this->role = $role;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getEmployeeCode(): string
    {
        return $this->employeeCode;
    }

    public function getRole(): UserRole
    {
        return $this->role;
    }

    public function getCreatedAt(): ?DateTime
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?DateTime
    {
        return $this->updatedAt;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'email' => $this->email,
            'name' => $this->name,
            'employeeCode' => $this->employeeCode,
            'role' => $this->role->value,
            'createdAt' => $this->createdAt,
            'updatedAt' => $this->updatedAt,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
