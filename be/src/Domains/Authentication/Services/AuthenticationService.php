<?php

declare(strict_types=1);

namespace App\Domains\Authentication\Services;

use App\Domains\Authentication\Models\AuthenticatedUserModel;
use App\Domains\Users\Models\UserRole;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use InvalidArgumentException;
use Throwable;

readonly class AuthenticationService
{
    public function __construct(private string $jwtSecret, private string $jwtAlgo = 'HS256', private int $jwtTtl = 3600)
    {
    }

    /**
     * Create a JWT from an AuthenticatedUserModel
     */
    public function createToken(AuthenticatedUserModel $user): string
    {
        $now = time();
        $payload = [
            'sub' => $user->getId(),
            'name' => $user->getName(),
            'email' => $user->getEmail(),
            'role' => $user->getRole()->value,
            'iat' => $now,
            'exp' => $now + $this->jwtTtl,
        ];
        return JWT::encode($payload, $this->jwtSecret, $this->jwtAlgo);
    }

    /**
     * Parse a JWT and return an AuthenticatedUserModel
     */
    public function parseToken(string $jwt): AuthenticatedUserModel
    {
        try {
            /** @var array{sub: int, name: string, email: string, role: string} $payload */
            $payload = (array) JWT::decode($jwt, new Key($this->jwtSecret, $this->jwtAlgo));
        } catch (Throwable) {
            throw new InvalidArgumentException('Invalid or expired token');
        }

        if (!isset($payload['sub'], $payload['name'], $payload['email'], $payload['role'])) { // @phpstan-ignore-line
            throw new InvalidArgumentException('Invalid token payload');
        }

        return new AuthenticatedUserModel(
            $payload['sub'],
            $payload['name'],
            $payload['email'],
            UserRole::from($payload['role']),
        );
    }
}
