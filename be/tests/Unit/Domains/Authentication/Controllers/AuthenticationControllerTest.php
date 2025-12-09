<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Authentication\Controllers;

use App\Domains\Authentication\Controllers\AuthenticationController;
use App\Domains\Authentication\Repositories\AuthenticationRepository;
use App\Domains\Authentication\Services\AuthenticationService;
use App\Domains\Authentication\Models\AuthenticatedUserModel;
use App\Shared\PasswordHasherArgon2id;
use App\Shared\Ports\PasswordHasher;
use Laminas\Diactoros\ServerRequest;
use Laminas\Diactoros\Response\JsonResponse;
use Laminas\Diactoros\Stream;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use InvalidArgumentException;

class AuthenticationControllerTest extends TestCase
{
    public function testLoginSuccess(): void
    {
        $email = 'test@example.com';
        $password = 'password123';
        $token = 'jwt.token';

        $passwordHasher = new PasswordHasherArgon2id();
        $hashedPassword = $passwordHasher->hash($password);

        $user = $this->createMock(AuthenticatedUserModel::class);
        $user->method('getHashedPassword')->willReturn($hashedPassword);

        $repo = $this->createMock(AuthenticationRepository::class);
        $repo->expects($this->once())
            ->method('findByEmail')
            ->with($email)
            ->willReturn($user);

        $authService = $this->createMock(AuthenticationService::class);
        $authService->expects($this->once())
            ->method('createToken')
            ->with($user)
            ->willReturn($token);

        $body = new Stream('php://temp', 'rw');
        $body->write(json_encode([
            'email' => $email,
            'password' => $password,
        ], JSON_THROW_ON_ERROR));
        $request = new ServerRequest([], [], null, null, $body);

        $controller = new AuthenticationController($repo, $authService, $passwordHasher);
        $response = $controller->login($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(200, $response->getStatusCode());
        /** @var array{token: string} $data */
        $data = json_decode((string)$response->getBody(), true, JSON_THROW_ON_ERROR);
        $this->assertSame($token, $data['token']);
    }

    public function testLoginInvalidCredentialsUserNotFound(): void
    {
        $repo = $this->createMock(AuthenticationRepository::class);
        $repo->expects($this->once())
            ->method('findByEmail')
            ->willThrowException(new InvalidArgumentException());
        $hasher = $this->createMock(PasswordHasher::class);
        $authService = $this->createMock(AuthenticationService::class);

        $body = new Stream('php://temp', 'rw');
        $body->write(json_encode([
            'email' => 'notfound@example.com',
            'password' => 'irrelevant'
        ], JSON_THROW_ON_ERROR));
        $request = new ServerRequest([], [], null, null, $body);

        $controller = new AuthenticationController($repo, $authService, $hasher);
        $response = $controller->login($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(401, $response->getStatusCode());
        /** @var array{status_code: int, reason_phrase: string} $data */
        $data = json_decode((string)$response->getBody(), true, JSON_THROW_ON_ERROR);
        $this->assertSame('Invalid credentials', $data['reason_phrase']);
    }

    public function testLoginInvalidCredentialsWrongPassword(): void
    {
        $email = 'test@example.com';
        $password = 'wrongpassword';
        $hashedPassword = 'hashed';

        $user = $this->createMock(AuthenticatedUserModel::class);
        $user->method('getHashedPassword')->willReturn($hashedPassword);
        $repo = $this->createMock(AuthenticationRepository::class);
        $repo->expects($this->once())
            ->method('findByEmail')
            ->with($email)
            ->willReturn($user);
        $hasher = $this->createMock(PasswordHasher::class);
        $hasher->expects($this->once())
            ->method('verify')
            ->with($password, $hashedPassword)
            ->willReturn(false);
        $authService = $this->createMock(AuthenticationService::class);

        $body = new Stream('php://temp', 'rw');
        $body->write(json_encode([
            'email' => $email,
            'password' => $password,
        ], JSON_THROW_ON_ERROR));
        $request = new ServerRequest([], [], null, null, $body);

        $controller = new AuthenticationController($repo, $authService, $hasher);
        $response = $controller->login($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(401, $response->getStatusCode());
        /** @var array{status_code: int, reason_phrase: string} $data */
        $data = json_decode((string)$response->getBody(), true, JSON_THROW_ON_ERROR);
        $this->assertSame('Invalid credentials', $data['reason_phrase']);
    }

    public function testLoginInvalidCredentialsNoPassword(): void
    {
        $email = 'test@example.com';
        $user = $this->createMock(AuthenticatedUserModel::class);
        $user->method('getHashedPassword')->willReturn(null);
        $repo = $this->createMock(AuthenticationRepository::class);
        $repo->expects($this->once())
            ->method('findByEmail')
            ->with($email)
            ->willReturn($user);
        $hasher = $this->createMock(PasswordHasher::class);
        $authService = $this->createMock(AuthenticationService::class);

        $body = new Stream('php://temp', 'rw');
        $body->write(json_encode([
            'email' => $email,
            'password' => 'irrelevant',
        ], JSON_THROW_ON_ERROR));
        $request = new ServerRequest([], [], null, null, $body);

        $controller = new AuthenticationController($repo, $authService, $hasher);
        $response = $controller->login($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(401, $response->getStatusCode());
        /** @var array{status_code: int, reason_phrase: string} $data */
        $data = json_decode((string)$response->getBody(), true, JSON_THROW_ON_ERROR);
        $this->assertSame('Invalid credentials', $data['reason_phrase']);
    }

    public function testRenewSuccess(): void
    {
        $jwt = 'jwt.token';
        $newToken = 'new.jwt.token';

        $repo = $this->createMock(AuthenticationRepository::class);
        $hasher = $this->createMock(PasswordHasher::class);
        $request = $this->createMock(ServerRequestInterface::class);
        $request->expects($this->once())
            ->method('getHeaderLine')
            ->with('Authorization')
            ->willReturn('Bearer ' . $jwt);

        $user = $this->createMock(AuthenticatedUserModel::class);
        $authService = $this->createMock(AuthenticationService::class);
        $authService->expects($this->once())
            ->method('parseToken')
            ->with($jwt)
            ->willReturn($user);
        $authService->expects($this->once())
            ->method('createToken')
            ->with($user)
            ->willReturn($newToken);

        $controller = new AuthenticationController($repo, $authService, $hasher);
        $response = $controller->renew($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(200, $response->getStatusCode());
        /** @var array{token: string} $data */
        $data = json_decode((string)$response->getBody(), true, JSON_THROW_ON_ERROR);
        $this->assertSame($newToken, $data['token']);
    }

    public function testRenewThrowsOnMissingHeader(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $repo = $this->createMock(AuthenticationRepository::class);
        $authService = $this->createMock(AuthenticationService::class);
        $hasher = $this->createMock(PasswordHasher::class);
        $request = $this->createMock(ServerRequestInterface::class);
        $request->expects($this->once())
            ->method('getHeaderLine')
            ->with('Authorization')
            ->willReturn('');

        $controller = new AuthenticationController($repo, $authService, $hasher);
        $controller->renew($request);
    }

    public function testRenewThrowsOnInvalidHeader(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $repo = $this->createMock(AuthenticationRepository::class);
        $authService = $this->createMock(AuthenticationService::class);
        $hasher = $this->createMock(PasswordHasher::class);
        $request = $this->createMock(ServerRequestInterface::class);
        $request->expects($this->once())
            ->method('getHeaderLine')
            ->with('Authorization')
            ->willReturn('Token abc');

        $controller = new AuthenticationController($repo, $authService, $hasher);
        $controller->renew($request);
    }
}
