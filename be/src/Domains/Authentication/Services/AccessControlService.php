<?php

declare(strict_types=1);

namespace App\Domains\Authentication\Services;

use App\Domains\Authentication\Models\AuthenticatedUserModel;
use App\Domains\Users\Models\UserRole;
use InvalidArgumentException;
use Psr\Http\Message\ServerRequestInterface;

readonly class AccessControlService
{
    public function __construct(private AuthenticationService $authenticationService)
    {
    }

    public function isEmployeeFromRequest(ServerRequestInterface $request): bool
    {
        $jwt = $this->extractJwtFromRequest($request);
        return $this->isEmployee($jwt);
    }

    public function isManagerFromRequest(ServerRequestInterface $request): bool
    {
        $jwt = $this->extractJwtFromRequest($request);
        return $this->isManager($jwt);
    }

    public function isEmployee(string $jwt): bool
    {
        return $this->extractRoleFromJwt($jwt) === UserRole::Employee->value;
    }

    public function isManager(string $jwt): bool
    {
        return $this->extractRoleFromJwt($jwt) === UserRole::Manager->value;
    }

    public function getAuthenticatedUserModelFromRequest(ServerRequestInterface $request): AuthenticatedUserModel
    {
        $jwt = $this->extractJwtFromRequest($request);
        return $this->authenticationService->parseToken($jwt);
    }

    private function extractJwtFromRequest(ServerRequestInterface $request): string
    {
        $authHeader = $request->getHeaderLine('Authorization');
        if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
            throw new InvalidArgumentException('Missing or invalid Authorization header');
        }
        return trim(substr($authHeader, 7));
    }

    private function extractRoleFromJwt(string $jwt): string
    {
        $user = $this->authenticationService->parseToken($jwt);
        return $user->getRole()->value;
    }
}
