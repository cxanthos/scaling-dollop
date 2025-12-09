<?php

declare(strict_types=1);

namespace App\Domains\Authentication\Controllers;

use App\Domains\Authentication\Repositories\AuthenticationRepository;
use App\Domains\Authentication\Services\AuthenticationService;
use App\Shared\JsonErrorResponse;
use App\Shared\Ports\PasswordHasher;
use InvalidArgumentException;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use JsonException;

readonly class AuthenticationController
{
    public function __construct(private AuthenticationRepository $authenticationRepository, private AuthenticationService $authenticationService, private PasswordHasher $passwordHasher)
    {
    }

    /**
     * Handle user login
     *
     * @throws JsonException
     */
    public function login(ServerRequestInterface $request): ResponseInterface
    {
        /** @var array<string, string> $body */
        $body = json_decode((string) $request->getBody(), true, 512, JSON_THROW_ON_ERROR);
        $email = $body['email'] ?? '';
        $password = $body['password'] ?? '';

        // Find user by email
        try {
            $user = $this->authenticationRepository->findByEmail($email);
        } catch (InvalidArgumentException) {
            return new JsonErrorResponse(401, 'Invalid credentials');
        }

        // Verify password
        if (!$user->getHashedPassword() || !$this->passwordHasher->verify($password, $user->getHashedPassword())) {
            return new JsonErrorResponse(401, 'Invalid credentials');
        }

        return new JsonResponse([
            'token' => $this->authenticationService->createToken($user),
        ], 200);
    }

    /**
     * Handle token renewal
     */
    public function renew(ServerRequestInterface $request): ResponseInterface
    {
        $authHeader = $request->getHeaderLine('Authorization');
        if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
            throw new InvalidArgumentException('Missing or invalid Authorization header');
        }
        $authHeader = trim(substr($authHeader, 7));

        $authenticatedUser = $this->authenticationService->parseToken($authHeader);

        return new JsonResponse([
            'token' => $this->authenticationService->createToken($authenticatedUser),
        ], 200);
    }
}
