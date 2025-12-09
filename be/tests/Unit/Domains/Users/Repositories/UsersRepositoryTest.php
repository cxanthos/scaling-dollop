<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Users\Repositories;

use App\Domains\Users\Models\UserModel;
use App\Domains\Users\Models\UserRole;
use App\Domains\Users\Repositories\UsersRepository;
use App\Shared\Database;
use App\Shared\PasswordHasherArgon2id;
use InvalidArgumentException;
use PDOException;
use PHPUnit\Framework\TestCase;

class UsersRepositoryTest extends TestCase
{
    public function testCreateSuccess(): void
    {
        $expectedId = 42;
        $expectedEmail = 'test@example.com';
        $expectedPassword = 'password123';
        $expectedName = 'John Doe';
        $expectedEmployeeCode = '1234567';
        $expectedRole = UserRole::Employee;
        $expectedCreatedAt = '2025-11-06 10:00:00';
        $expectedUpdatedAt = '2025-11-06 11:00:00';

        $dbMock = $this->createMock(Database::class);
        $repo = new UsersRepository($dbMock, new PasswordHasherArgon2id());
        $user = new UserModel(
            null,
            $expectedEmail,
            $expectedPassword,
            $expectedName,
            $expectedEmployeeCode,
            $expectedRole
        );
        $dbMock->expects($this->once())
            ->method('execute')
            ->with(
                $this->callback(function (string $sql) {
                    return str_starts_with($sql, 'INSERT INTO users (email, password, name, employee_code, role)')
                        && str_ends_with($sql, 'VALUES (:email, :password, :name, :employee_code, :role)');
                }),
                $this->callback(function (array $params) use ($expectedEmail, $expectedPassword, $expectedName, $expectedEmployeeCode, $expectedRole) {
                    return $params['email'] === $expectedEmail
                        && new PasswordHasherArgon2id()->verify($expectedPassword, $params['password']) // @phpstan-ignore-line
                        && $params['name'] === $expectedName
                        && $params['employee_code'] === $expectedEmployeeCode
                        && $params['role'] === $expectedRole->value;
                })
            )
            ->willReturn(1);
        $dbMock->expects($this->once())
            ->method('lastInsertId')
            ->willReturn((string)$expectedId);
        $dbMock->expects($this->once())
            ->method('query')
            ->with($this->equalTo('SELECT created_at, updated_at FROM users WHERE id = :id'), ['id' => $expectedId])
            ->willReturn([
                ['created_at' => $expectedCreatedAt, 'updated_at' => $expectedUpdatedAt]
            ]);
        $created = $repo->create($user);
        $this->assertSame($expectedId, $created->getId());
        $this->assertSame($expectedEmail, $created->getEmail());
        $this->assertSame($expectedName, $created->getName());
        $this->assertSame($expectedEmployeeCode, $created->getEmployeeCode());
        $this->assertSame($expectedRole, $created->getRole());
        $this->assertSame($expectedCreatedAt, $created->getCreatedAt()?->format('Y-m-d H:i:s'));
        $this->assertSame($expectedUpdatedAt, $created->getUpdatedAt()?->format('Y-m-d H:i:s'));
    }

    public function testCreateUniqueConstraint(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Email ('test@example.com') or Employee code ('1234567') is already in use.");

        $dbMock = $this->createMock(Database::class);
        $repo = new UsersRepository($dbMock, new PasswordHasherArgon2id());
        $user = new UserModel(null, 'test@example.com', 'password123', 'John Doe', '1234567', UserRole::Employee);
        $dbMock->expects($this->once())
            ->method('execute')
            ->willThrowException(new PDOException('Duplicate', 23000));
        $repo->create($user);
    }

    public function testFindByIdSuccess(): void
    {
        $expectedId = 1;
        $expectedEmail = 'test@example.com';
        $expectedName = 'John Doe';
        $expectedEmployeeCode = '1234567';
        $expectedRole = UserRole::Employee;
        $expectedCreatedAt = '2025-11-06 10:00:00';
        $expectedUpdatedAt = '2025-11-06 11:00:00';

        $dbMock = $this->createMock(Database::class);
        $repo = new UsersRepository($dbMock, new PasswordHasherArgon2id());
        $dbMock->expects($this->once())
            ->method('query')
            ->with($this->equalTo('SELECT id, email, name, employee_code, role, created_at, updated_at FROM users WHERE id = :id'), ['id' => 1])
            ->willReturn([
                [
                    'id' => $expectedId,
                    'email' => $expectedEmail,
                    'name' => $expectedName,
                    'employee_code' => $expectedEmployeeCode,
                    'role' => $expectedRole->value,
                    'created_at' => $expectedCreatedAt,
                    'updated_at' => $expectedUpdatedAt
                ]
            ]);
        $user = $repo->findById($expectedId);
        $this->assertSame($expectedId, $user->getId());
        $this->assertSame($expectedEmail, $user->getEmail());
        $this->assertSame($expectedName, $user->getName());
        $this->assertSame($expectedEmployeeCode, $user->getEmployeeCode());
        $this->assertSame($expectedRole, $user->getRole());
        $this->assertSame($expectedCreatedAt, $user->getCreatedAt()?->format('Y-m-d H:i:s'));
        $this->assertSame($expectedUpdatedAt, $user->getUpdatedAt()?->format('Y-m-d H:i:s'));
    }

    public function testFindByIdNotFound(): void
    {
        $dbMock = $this->createMock(Database::class);
        $repo = new UsersRepository($dbMock, new PasswordHasherArgon2id());
        $dbMock->expects($this->once())
            ->method('query')
            ->willReturn([]);
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('User with ID 1 not found.');
        $repo->findById(1);
    }

    public function testDeleteSuccess(): void
    {
        $dbMock = $this->createMock(Database::class);
        $repo = new UsersRepository($dbMock, new PasswordHasherArgon2id());
        $dbMock->expects($this->once())
            ->method('execute')
            ->with($this->equalTo('DELETE FROM users WHERE id = :id'), ['id' => 1])
            ->willReturn(1);
        $repo->delete(1);
    }

    public function testDeleteNotFound(): void
    {
        $dbMock = $this->createMock(Database::class);
        $repo = new UsersRepository($dbMock, new PasswordHasherArgon2id());
        $dbMock->expects($this->once())
            ->method('execute')
            ->willReturn(0);
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('User with ID 1 not found.');
        $repo->delete(1);
    }

    public function testFindAll(): void
    {
        $dbMock = $this->createMock(Database::class);
        $repo = new UsersRepository($dbMock, new PasswordHasherArgon2id());
        $dbMock->expects($this->once())
            ->method('query')
            ->with($this->equalTo('SELECT id, email, name, employee_code, role, created_at, updated_at FROM users ORDER BY name ASC'))
            ->willReturn([
                [
                    'id' => 1,
                    'email' => 'test@example.com',
                    'name' => 'John Doe',
                    'employee_code' => '1234567',
                    'role' => 'employee',
                    'created_at' => '2025-11-06 10:00:00',
                    'updated_at' => '2025-11-06 10:00:00'
                ],
                [
                    'id' => 2,
                    'email' => 'manager@example.com',
                    'name' => 'Jane Manager',
                    'employee_code' => '7654321',
                    'role' => 'manager',
                    'created_at' => '2025-11-06 11:00:00',
                    'updated_at' => '2025-11-06 11:00:00'
                ]
            ]);
        $collection = $repo->findAll();
        $this->assertCount(2, $collection->jsonSerialize());
        /** @var array<int, array<string, mixed>> $users */
        $users = $collection->jsonSerialize();
        $this->assertSame('test@example.com', $users[0]['email']);
        $this->assertSame('manager@example.com', $users[1]['email']);
    }

    public function testUpdateSuccessWithPassword(): void
    {
        $expectedId = 1;
        $expectedEmail = 'test@example.com';
        $expectedName = 'John Doe';
        $expectedEmployeeCode = '1234567';
        $expectedRole = UserRole::Employee;
        $expectedCreatedAt = '2025-11-06 10:00:00';
        $expectedUpdatedAt = '2025-11-06 11:00:00';

        $originalEmail = 'old@example.com';
        $originalPassword = 'oldpassword';
        $originalName = 'Jone Doe';
        $originalRole = UserRole::Manager;

        $dbMock = $this->createMock(Database::class);
        $repo = new UsersRepository($dbMock, new PasswordHasherArgon2id());
        $user = new UserModel(
            $expectedId,
            $originalEmail,
            $originalPassword,
            $originalName,
            $expectedEmployeeCode,
            $originalRole
        );
        $dbMock->expects($this->once())
            ->method('execute')
            ->with(
                $this->equalTo('UPDATE users SET email = :email, name = :name, role = :role, password = :password WHERE id = :id'),
                $this->callback(function (array $params) use ($originalEmail, $originalPassword, $originalName, $originalRole) {
                    return $params['email'] === $originalEmail
                        && new PasswordHasherArgon2id()->verify($originalPassword, $params['password']) // @phpstan-ignore-line
                        && $params['name'] === $originalName
                        && $params['role'] === $originalRole->value;
                })
            )
            ->willReturn(1);
        $dbMock->expects($this->once())
            ->method('query')
            ->willReturn([
                [
                    'id' => $expectedId,
                    'email' => $expectedEmail,
                    'name' => $expectedName,
                    'employee_code' => $expectedEmployeeCode,
                    'role' => $expectedRole->value,
                    'created_at' => $expectedCreatedAt,
                    'updated_at' => $expectedUpdatedAt
                ]
            ]);
        $updated = $repo->update($user);
        $this->assertSame($expectedId, $updated->getId());
        $this->assertSame($expectedEmail, $updated->getEmail());
        $this->assertSame($expectedName, $updated->getName());
        $this->assertSame($expectedEmployeeCode, $updated->getEmployeeCode());
        $this->assertSame($expectedRole, $updated->getRole());
        $this->assertSame($expectedCreatedAt, $updated->getCreatedAt()?->format('Y-m-d H:i:s'));
        $this->assertSame($expectedUpdatedAt, $updated->getUpdatedAt()?->format('Y-m-d H:i:s'));
    }

    public function testUpdateSuccessWithoutPassword(): void
    {
        $dbMock = $this->createMock(Database::class);
        $repo = new UsersRepository($dbMock, new PasswordHasherArgon2id());
        $user = new UserModel(1, 'test@example.com', null, 'John Doe', '1234567', UserRole::Employee);
        $dbMock->expects($this->once())
            ->method('execute')
            ->with($this->logicalNot($this->stringContains('password = :password')), $this->logicalNot($this->arrayHasKey('password')))
            ->willReturn(1);
        $dbMock->expects($this->once())
            ->method('query')
            ->willReturn([
                [
                    'id' => 1,
                    'email' => 'test@example.com',
                    'name' => 'John Doe',
                    'employee_code' => '1234567',
                    'role' => 'employee',
                    'created_at' => '2025-11-06 10:00:00',
                    'updated_at' => '2025-11-06 10:00:00'
                ]
            ]);
        $updated = $repo->update($user);
        $this->assertSame(1, $updated->getId());
    }

    public function testUpdateUniqueConstraint(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Email 'test@example.com' is already in use.");

        $dbMock = $this->createMock(Database::class);
        $repo = new UsersRepository($dbMock, new PasswordHasherArgon2id());
        $user = new UserModel(1, 'test@example.com', null, 'John Doe', '1234567', UserRole::Employee);
        $dbMock->expects($this->once())
            ->method('execute')
            ->willThrowException(new PDOException('Duplicate', 23000));
        $repo->update($user);
    }
}
