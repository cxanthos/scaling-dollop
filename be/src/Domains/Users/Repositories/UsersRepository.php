<?php

declare(strict_types=1);

namespace App\Domains\Users\Repositories;

use App\Domains\Users\Models\UserModel;
use App\Domains\Users\Models\UserRole;
use App\Shared\Database;
use App\Shared\ModelCollection;
use App\Shared\Ports\PasswordHasher;
use DateMalformedStringException;
use DateTime;
use InvalidArgumentException;
use PDOException;

readonly class UsersRepository
{
    public function __construct(private Database $db, private PasswordHasher $hasher)
    {
    }

    /**
     * @throws DateMalformedStringException
     */
    public function create(UserModel $user): UserModel
    {
        $sql = "INSERT INTO users (email, password, name, employee_code, role)
                VALUES (:email, :password, :name, :employee_code, :role)";

        assert(is_string($user->getPassword()));

        try {
            $this->db->execute($sql, [
                'email' => $user->getEmail(),
                'password' => $this->hasher->hash($user->getPassword()),
                'name' => $user->getName(),
                'employee_code' => $user->getEmployeeCode(),
                'role' => $user->getRole()->value,
            ]);
        } catch (PDOException $e) {
            if ($e->getCode() === 23000) {
                throw new InvalidArgumentException("Email ('{$user->getEmail()}') or Employee code ('{$user->getEmployeeCode()}') is already in use.");
            }

            throw $e;
        }

        $id = (int) $this->db->lastInsertId();

        // Fetch created_at and updated_at from DB
        $row = $this->db->query(
            "SELECT created_at, updated_at FROM users WHERE id = :id",
            ['id' => $id]
        )[0];

        $createdAt = isset($row['created_at']) && is_string($row['created_at']) ? new DateTime($row['created_at']) : null;
        $updatedAt = isset($row['updated_at']) && is_string($row['updated_at']) ? new DateTime($row['updated_at']) : null;

        return new UserModel(
            $id,
            $user->getEmail(),
            null,
            $user->getName(),
            $user->getEmployeeCode(),
            $user->getRole(),
            $createdAt,
            $updatedAt
        );
    }

    /**
     * @throws DateMalformedStringException
     */
    public function findById(int $id): UserModel
    {
        $sql = "SELECT id, email, name, employee_code, role, created_at, updated_at FROM users WHERE id = :id";

        $row = $this->db->query($sql, ['id' => $id]);

        if (empty($row)) {
            throw new InvalidArgumentException("User with ID $id not found.");
        }

        return self::mapToUserModel($row[0]);
    }

    public function delete(int $id): void
    {
        $sql = "DELETE FROM users WHERE id = :id";
        $rowCount = $this->db->execute($sql, ['id' => $id]);

        if ($rowCount === 0) {
            throw new InvalidArgumentException("User with ID $id not found.");
        }
    }

    /**
     * @throws DateMalformedStringException
     */
    public function findAll(): ModelCollection
    {
        $sql = "SELECT id, email, name, employee_code, role, created_at, updated_at FROM users ORDER BY name ASC";

        $rows = $this->db->query($sql);

        $users = new ModelCollection();

        foreach ($rows as $data) {
            $user = self::mapToUserModel($data);
            $users->add($user);
        }

        return $users;
    }

    /**
     * @throws DateMalformedStringException
     */
    public function update(UserModel $user): UserModel
    {
        assert(is_int($user->getId()));

        $fields = [
            'email = :email',
            'name = :name',
            'role = :role',
        ];
        $params = [
            'email' => $user->getEmail(),
            'name' => $user->getName(),
            'role' => $user->getRole()->value,
            'id' => $user->getId(),
        ];

        if ($user->getPassword() !== null) {
            $fields[] = 'password = :password';
            $params['password'] = $this->hasher->hash($user->getPassword());
        }

        $sql = 'UPDATE users SET ' . implode(', ', $fields) . ' WHERE id = :id';

        try {
            $this->db->execute($sql, $params);
        } catch (PDOException $e) {
            if ($e->getCode() === 23000) {
                throw new InvalidArgumentException("Email '{$user->getEmail()}' is already in use.");
            }

            throw $e;
        }

        return $this->findById($user->getId());
    }

    /**
     * @param array<string, mixed> $data
     * @throws DateMalformedStringException
     */
    public static function mapToUserModel(array $data): UserModel
    {
        $id = (isset($data['id']) && is_scalar($data['id'])) ? intval($data['id']) : null;
        $email = isset($data['email']) && is_string($data['email']) ? $data['email'] : '';
        $name = isset($data['name']) && is_string($data['name']) ? $data['name'] : '';
        $employeeCode = isset($data['employee_code']) && is_string($data['employee_code']) ? $data['employee_code'] : '';
        $role = isset($data['role']) && is_string($data['role']) ? UserRole::from($data['role']) : UserRole::Employee;
        $createdAt = isset($data['created_at']) && is_string($data['created_at']) ? new DateTime($data['created_at']) : null;
        $updatedAt = isset($data['updated_at']) && is_string($data['updated_at']) ? new DateTime($data['updated_at']) : null;

        return new UserModel(
            $id,
            $email,
            null,
            $name,
            $employeeCode,
            $role,
            $createdAt,
            $updatedAt
        );
    }
}
