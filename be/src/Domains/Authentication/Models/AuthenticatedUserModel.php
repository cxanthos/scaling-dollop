<?php

declare(strict_types=1);

namespace App\Domains\Authentication\Models;

use App\Domains\Users\Models\UserRole;
use JsonSerializable;

readonly class AuthenticatedUserModel implements JsonSerializable
{
    public function __construct(private int $id, private string $name, private string $email, private UserRole $role, private ?string $hashedPassword = null)
    {
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getRole(): UserRole
    {
        return $this->role;
    }

    public function getHashedPassword(): ?string
    {
        return $this->hashedPassword;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role->value,
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
