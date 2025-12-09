<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Users\Controllers;

use App\Domains\Authentication\Models\AuthenticatedUserModel;
use App\Domains\Authentication\Services\AccessControlService;
use App\Domains\Users\Controllers\UsersController;
use App\Domains\Users\Models\UserModel;
use App\Domains\Users\Models\UserRole;
use App\Domains\Users\Repositories\UsersRepository;
use App\Shared\ModelCollection;
use DateMalformedStringException;
use JsonException;
use Laminas\Diactoros\ServerRequest;
use Laminas\Diactoros\Response\JsonResponse;
use Laminas\Diactoros\Stream;
use League\Route\Http\Exception\ForbiddenException;
use PHPUnit\Framework\TestCase;
use DateTime;

class UsersControllerTest extends TestCase
{
    /**
     * @throws DateMalformedStringException
     * @throws JsonException
     */
    public function testCreate(): void
    {
        $expectedEmail = 'test@example.com';
        $expectedPassword = 'password123';
        $expectedName = 'John Doe';
        $expectedEmployeeCode = '1234567';
        $expectedRole = UserRole::Employee;

        $userModel = new UserModel(
            1,
            $expectedEmail,
            $expectedPassword,
            $expectedName,
            $expectedEmployeeCode,
            $expectedRole,
            new DateTime(),
            new DateTime()
        );

        $usersRepository = $this->createMock(UsersRepository::class);
        $usersRepository->expects($this->once())
            ->method('create')
            ->with($this->callback(function ($param) use (
                $expectedEmail,
                $expectedPassword,
                $expectedName,
                $expectedEmployeeCode,
                $expectedRole
            ) {
                return $param instanceof UserModel
                    && $param->getEmail() === $expectedEmail
                    && $param->getPassword() === $expectedPassword
                    && $param->getName() === $expectedName
                    && $param->getEmployeeCode() === $expectedEmployeeCode
                    && $param->getRole() === $expectedRole;
            }))
            ->willReturn($userModel);

        $body = new Stream('php://temp', 'rw');
        $body->write(json_encode([
            'email' => $expectedEmail,
            'password' => $expectedPassword,
            'name' => $expectedName,
            'employeeCode' => $expectedEmployeeCode,
            'role' => 'employee'
        ], JSON_THROW_ON_ERROR));
        $request = new ServerRequest([], [], null, null, $body);

        $response = new UsersController($usersRepository, $this->getMockAccessControlService())->create($request);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(201, $response->getStatusCode());

        /** @var array<string, string> $responseData */
        $responseData = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame($userModel->getEmail(), $responseData['email']);
    }

    /**
     * @throws DateMalformedStringException
     */
    public function testList(): void
    {
        $collection = new ModelCollection();
        $collection->add($this->createMock(UserModel::class));
        $serverRequest = $this->createMock(ServerRequest::class);
        $usersRepository = $this->createMock(UsersRepository::class);
        $usersRepository->expects($this->once())
            ->method('findAll')
            ->willReturn($collection);

        $response = new UsersController($usersRepository, $this->getMockAccessControlService())->list($serverRequest);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(200, $response->getStatusCode());
    }

    /**
     * @throws DateMalformedStringException
     * @throws JsonException
     */
    public function testGet(): void
    {
        $userModel = new UserModel(
            1,
            'test@example.com',
            'password123',
            'John Doe',
            '1234567',
            UserRole::Employee,
            new DateTime(),
            new DateTime()
        );

        $serverRequest = $this->createMock(ServerRequest::class);
        $usersRepository = $this->createMock(UsersRepository::class);
        $usersRepository->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($userModel);

        $response = new UsersController($usersRepository, $this->getMockAccessControlService())->get($serverRequest, ['id' => 1]);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(200, $response->getStatusCode());

        /** @var array<string, string> $responseData */
        $responseData = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame($userModel->getEmail(), $responseData['email']);
    }

    /**
     * @throws DateMalformedStringException
     * @throws JsonException
     */
    public function testUpdate(): void
    {
        $expectedId = 1;
        $expectedEmail = 'test2@example.com';
        $expectedPassword = 'password456';
        $expectedName = 'Jane Doe';
        $expectedEmployeeCode = '1234567';
        $expectedRole = UserRole::Manager;

        $existingUserModel = new UserModel(
            $expectedId,
            'test@example.com',
            'password123',
            'John Doe',
            $expectedEmployeeCode,
            UserRole::Employee,
            new DateTime(),
            new DateTime()
        );

        $updatedUserModel = new UserModel(
            $existingUserModel->getId(),
            $expectedEmail,
            $expectedPassword,
            $expectedName,
            $existingUserModel->getEmployeeCode(),
            $expectedRole,
            $existingUserModel->getCreatedAt(),
            $existingUserModel->getUpdatedAt()
        );

        $usersRepository = $this->createMock(UsersRepository::class);
        $usersRepository->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($existingUserModel);
        $usersRepository->expects($this->once())
            ->method('update')
            ->with($this->callback(function ($param) use (
                $expectedId,
                $expectedEmail,
                $expectedPassword,
                $expectedName,
                $expectedEmployeeCode,
                $expectedRole
            ) {
                return $param instanceof UserModel
                    && $param->getId() === $expectedId
                    && $param->getEmail() === $expectedEmail
                    && $param->getPassword() === $expectedPassword
                    && $param->getName() === $expectedName
                    && $param->getEmployeeCode() === $expectedEmployeeCode
                    && $param->getRole() === $expectedRole;
            }))
            ->willReturn($updatedUserModel);

        $body = new Stream('php://temp', 'rw');
        $body->write(json_encode([
            'email' => $expectedEmail,
            'name' => $expectedName,
            'password' => $expectedPassword,
            'employeeCode' => '7654321',
            'role' => $expectedRole->value
        ], JSON_THROW_ON_ERROR));

        $request = new ServerRequest([], [], null, null, $body);

        $response = new UsersController($usersRepository, $this->getMockAccessControlService())->update($request, ['id' => 1]);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(200, $response->getStatusCode());

        /** @var array<string, string> $responseData */
        $responseData = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame($expectedEmail, $responseData['email']);
        $this->assertSame($expectedEmployeeCode, $responseData['employeeCode']);
    }

    public function testDelete(): void
    {
        $serverRequest = $this->createMock(ServerRequest::class);
        $usersRepository = $this->createMock(UsersRepository::class);
        $usersRepository->expects($this->once())
            ->method('delete')
            ->with(2);

        $response = new UsersController($usersRepository, $this->getMockAccessControlService())->delete($serverRequest, ['id' => 2]);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(204, $response->getStatusCode());
    }

    public function testDeleteSelf(): void
    {
        $serverRequest = $this->createMock(ServerRequest::class);
        $usersRepository = $this->createMock(UsersRepository::class);

        $response = new UsersController($usersRepository, $this->getMockAccessControlService())->delete($serverRequest, ['id' => 1]);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testDeleteForbidden(): void
    {
        $this->expectException(ForbiddenException::class);

        $accessControlService = $this->createMock(AccessControlService::class);
        $accessControlService->method('isManagerFromRequest')->willReturn(false);
        $serverRequest = $this->createMock(ServerRequest::class);
        $usersRepository = $this->createMock(UsersRepository::class);

        new UsersController($usersRepository, $accessControlService)->delete($serverRequest, ['id' => 1]);
    }

    private function getMockAccessControlService(): AccessControlService
    {
        $authenticatedUser = new AuthenticatedUserModel(
            1,
            'John Manager',
            'admin@example.com',
            UserRole::Manager,
        );

        $accessControlService = $this->createMock(AccessControlService::class);
        $accessControlService->method('isManagerFromRequest')->willReturn(true);
        $accessControlService->method('getAuthenticatedUserModelFromRequest')->willReturn($authenticatedUser);

        return $accessControlService;
    }
}
