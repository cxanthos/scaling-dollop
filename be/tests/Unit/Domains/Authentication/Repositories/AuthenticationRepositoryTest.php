<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Authentication\Repositories;

use App\Domains\Authentication\Repositories\AuthenticationRepository;
use App\Domains\Users\Models\UserRole;
use App\Shared\Database;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class AuthenticationRepositoryTest extends TestCase
{
    public function testFindByEmailReturnsUser(): void
    {
        $expectedId = 1;
        $expectedName = 'Test User';
        $expectedEmail = 'test@example.com';
        $expectedPassword = 'hashedpassword';
        $expectedRole = UserRole::Manager;

        $dbMock = $this->createMock(Database::class);
        $dbMock->expects($this->once())
            ->method('query')
            ->with($this->stringContains('SELECT id, email, name, password, role FROM users WHERE email = :email'), ['email' => $expectedEmail])
            ->willReturn([
                [
                    'id' => (string)$expectedId,
                    'name' => $expectedName,
                    'email' => $expectedEmail,
                    'password' => $expectedPassword,
                    'role' => $expectedRole->value,
                ]
            ]);

        $repo = new AuthenticationRepository($dbMock);
        $user = $repo->findByEmail($expectedEmail);

        $this->assertSame($expectedId, $user->getId());
        $this->assertSame($expectedName, $user->getName());
        $this->assertSame($expectedEmail, $user->getEmail());
        $this->assertSame($expectedPassword, $user->getHashedPassword());
        $this->assertSame($expectedRole, $user->getRole());
    }

    public function testFindByEmailThrowsIfNotFound(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('User not found.');

        $dbMock = $this->createMock(Database::class);
        $dbMock->expects($this->once())
            ->method('query')
            ->willReturn([]);

        $repo = new AuthenticationRepository($dbMock);
        $repo->findByEmail('notfound@example.com');
    }
}
