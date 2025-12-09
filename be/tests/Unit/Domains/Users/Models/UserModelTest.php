<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Users\Models;

use App\Domains\Users\Models\UserModel;
use App\Domains\Users\Models\UserRole;
use DateTime;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class UserModelTest extends TestCase
{
    public function testValidUserModelCreation(): void
    {
        $now = new DateTime();
        $user = new UserModel(
            1,
            'test@example.com',
            'password123',
            'John Doe ',
            '1234567',
            UserRole::Employee,
            $now,
            $now
        );
        $this->assertSame(1, $user->getId());
        $this->assertSame('test@example.com', $user->getEmail());
        $this->assertSame('password123', $user->getPassword());
        $this->assertSame('John Doe', $user->getName());
        $this->assertSame('1234567', $user->getEmployeeCode());
        $this->assertSame(UserRole::Employee, $user->getRole());
        $this->assertSame($now, $user->getCreatedAt());
        $this->assertSame($now, $user->getUpdatedAt());
    }

    public function testNullIdIsAccepted(): void
    {
        $user = new UserModel(
            null,
            'test@example.com',
            'password123',
            'John Doe',
            '1234567',
            UserRole::Manager
        );
        $this->assertNull($user->getId());
        $this->assertSame(UserRole::Manager, $user->getRole());
        $this->assertNull($user->getCreatedAt());
        $this->assertNull($user->getUpdatedAt());
    }

    public function testInvalidEmailThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new UserModel(1, 'invalid-email', 'password123', 'John Doe', '1234567', UserRole::Employee);
    }

    public function testShortPasswordThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new UserModel(1, 'test@example.com', 'short', 'John Doe', '1234567', UserRole::Employee);
    }

    public function testEmptyNameThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new UserModel(1, 'test@example.com', 'password123', '   ', '1234567', UserRole::Employee);
    }

    public function testInvalidEmployeeCodeThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new UserModel(1, 'test@example.com', 'password123', 'John Doe', '12345', UserRole::Employee);
    }
}
