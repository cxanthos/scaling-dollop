<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Authentication\Services;

use App\Domains\Authentication\Models\AuthenticatedUserModel;
use App\Domains\Authentication\Services\AuthenticationService;
use App\Domains\Users\Models\UserRole;
use Firebase\JWT\JWT;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class AuthenticationServiceTest extends TestCase
{
    private string $secret = 'test_secret';
    private string $algo = 'HS256';
    private int $ttl = 3600;

    public function testCreateTokenAndParseToken(): void
    {
        $user = new AuthenticatedUserModel(1, 'Test User', 'test@example.com', UserRole::Manager);
        $service = new AuthenticationService($this->secret, $this->algo, $this->ttl);

        $token = $service->createToken($user);
        $parsedUser = $service->parseToken($token);

        $this->assertSame($user->getId(), $parsedUser->getId());
        $this->assertSame($user->getName(), $parsedUser->getName());
        $this->assertSame($user->getEmail(), $parsedUser->getEmail());
        $this->assertSame($user->getRole(), $parsedUser->getRole());
    }

    public function testParseTokenThrowsOnInvalidToken(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid or expired token');

        $service = new AuthenticationService($this->secret, $this->algo, $this->ttl);
        $service->parseToken('invalid.token.value');
    }

    public function testParseTokenThrowsOnMissingFields(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid token payload');

        $service = new AuthenticationService($this->secret, $this->algo, $this->ttl);
        $payload = [
            'sub' => 1,
            // 'name' => 'Test User',
            'email' => 'test@example.com',
            'role' => 'manager',
            'iat' => time(),
            'exp' => time() + 3600,
        ];
        $token = JWT::encode($payload, $this->secret, $this->algo);
        $service->parseToken($token);
    }
}
