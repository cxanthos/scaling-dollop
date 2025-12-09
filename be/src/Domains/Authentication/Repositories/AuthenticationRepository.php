<?php

declare(strict_types=1);

namespace App\Domains\Authentication\Repositories;

use App\Domains\Authentication\Models\AuthenticatedUserModel;
use App\Domains\Users\Models\UserRole;
use App\Shared\Database;
use InvalidArgumentException;

readonly class AuthenticationRepository
{
    public function __construct(private Database $db)
    {
    }

    public function findByEmail(string $email): AuthenticatedUserModel
    {
        $sql = "SELECT id, email, name, password, role FROM users WHERE email = :email";

        $row = $this->db->query($sql, ['email' => $email]);

        if (empty($row)) {
            throw new InvalidArgumentException("User not found.");
        }

        /** @var array{id: string, name: string, password: string, email: string, role: string} $data */
        $data = $row[0];

        return new AuthenticatedUserModel(
            id: intval($data['id']),
            name: $data['name'],
            email: $data['email'],
            role: UserRole::from($data['role']),
            hashedPassword: $data['password'],
        );
    }
}
