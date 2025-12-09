<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Authentication\Models;

use App\Domains\Authentication\Models\AuthenticatedUserModel;
use App\Domains\Users\Models\UserRole;
use PHPUnit\Framework\TestCase;

class AuthenticatedUserModelTest extends TestCase
{
    public function testConstructorAndGetters(): void
    {
        $id = 10;
        $name = 'Alice Example';
        $email = 'alice@example.com';
        $password = 'hashed_pass';
        $role = UserRole::Manager;

        $user = new AuthenticatedUserModel($id, $name, $email, $role, $password);

        $this->assertSame($id, $user->getId());
        $this->assertSame($name, $user->getName());
        $this->assertSame($email, $user->getEmail());
        $this->assertSame($password, $user->getHashedPassword());
        $this->assertSame($role, $user->getRole());
    }

    public function testPasswordCanBeNull(): void
    {
        $id = 40;
        $name = 'Null Pass';
        $email = 'nullpass@example.com';
        $password = null;
        $role = UserRole::Employee;

        $user = new AuthenticatedUserModel($id, $name, $email, $role, $password);
        $this->assertNull($user->getHashedPassword());
    }

    public function testToArray(): void
    {
        $id = 20;
        $name = 'Bob Example';
        $email = 'bob@example.com';
        $role = UserRole::Employee;

        $user = new AuthenticatedUserModel($id, $name, $email, $role);
        $array = $user->toArray();

        $this->assertSame([
            'id' => $id,
            'name' => $name,
            'email' => $email,
            'role' => $role->value,
        ], $array);
    }

    public function testJsonSerialize(): void
    {
        $id = 30;
        $name = 'Carol Example';
        $email = 'carol@example.com';
        $role = UserRole::Employee;

        $user = new AuthenticatedUserModel($id, $name, $email, $role);
        $json = json_encode($user, JSON_THROW_ON_ERROR);
        $this->assertJson($json);
        /** @var array{id: int, name: string, email: string, role: string} $decoded */
        $decoded = json_decode($json, true);

        $this->assertSame([
            'id' => $id,
            'name' => $name,
            'email' => $email,
            'role' => $role->value,
        ], $decoded);
    }
}
