<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Vacations\Controllers;

use App\Domains\Authentication\Models\AuthenticatedUserModel;
use App\Domains\Authentication\Services\AccessControlService;
use App\Domains\Vacations\Controllers\VacationsController;
use App\Domains\Vacations\Models\VacationModel;
use App\Domains\Vacations\Models\VacationStatus;
use App\Domains\Vacations\Repositories\VacationsRepository;
use App\Domains\Users\Models\UserRole;
use App\Shared\ModelCollection;
use DateInterval;
use DateMalformedStringException;
use DateTime;
use JsonException;
use Laminas\Diactoros\ServerRequest;
use Laminas\Diactoros\Response\JsonResponse;
use Laminas\Diactoros\Stream;
use League\Route\Http\Exception\ForbiddenException;
use PHPUnit\Framework\TestCase;

class VacationsControllerTest extends TestCase
{
    /**
     * @throws DateMalformedStringException
     */
    public function testListAsManager(): void
    {
        $collection = new ModelCollection();
        $collection->add($this->createMock(VacationModel::class));
        $serverRequest = $this->createMock(ServerRequest::class);
        $accessControl = $this->getMockAccessControlService(UserRole::Manager);
        $vacationsRepository = $this->createMock(VacationsRepository::class);
        $vacationsRepository->expects($this->once())
            ->method('findPendingRequests')
            ->willReturn($collection);

        $controller = new VacationsController($vacationsRepository, $accessControl);
        $response = $controller->list($serverRequest);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(200, $response->getStatusCode());
    }

    /**
     * @throws DateMalformedStringException
     */
    public function testListAsEmployee(): void
    {
        $collection = new ModelCollection();
        $collection->add($this->createMock(VacationModel::class));
        $serverRequest = $this->createMock(ServerRequest::class);
        $accessControl = $this->getMockAccessControlService(UserRole::Employee);
        $vacationsRepository = $this->createMock(VacationsRepository::class);
        $vacationsRepository->expects($this->once())
            ->method('findUserRequests')
            ->with(1)
            ->willReturn($collection);

        $controller = new VacationsController($vacationsRepository, $accessControl);
        $response = $controller->list($serverRequest);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(200, $response->getStatusCode());
    }

    /**
     * @throws JsonException|ForbiddenException|DateMalformedStringException
     */
    public function testCreate(): void
    {
        $expectedStartDate = new DateTime()->add(new DateInterval('P1D'));
        $expectedEndDate = new DateTime()->add(new DateInterval('P2D'));
        $expectedReason = 'Holiday';

        $accessControl = $this->getMockAccessControlService(UserRole::Employee);

        $expectedVacation = new VacationModel(
            null,
            1,
            null,
            $expectedStartDate,
            $expectedEndDate,
            $expectedReason,
        );
        $vacationsRepository = $this->createMock(VacationsRepository::class);
        $vacationsRepository->expects($this->once())
            ->method('create')
            ->with($this->callback(function ($param) use (
                $expectedStartDate,
                $expectedEndDate,
                $expectedReason,
            ) {
                return $param instanceof VacationModel
                    && $param->getUserId() === 1
                    && $param->getFrom()->format('Y-m-d') === $expectedStartDate->format('Y-m-d')
                    && $param->getTo()->format('Y-m-d') === $expectedEndDate->format('Y-m-d')
                    && $param->getReason() === $expectedReason;
            }))
            ->willReturn($expectedVacation);

        $body = new Stream('php://temp', 'rw');
        $body->write(json_encode([
            'startDate' => $expectedStartDate->format('Y-m-d'),
            'endDate' => $expectedEndDate->format('Y-m-d'),
            'reason' => $expectedReason,
        ], JSON_THROW_ON_ERROR));
        $request = new ServerRequest([], [], null, null, $body);

        $controller = new VacationsController($vacationsRepository, $accessControl);
        $response = $controller->create($request);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(201, $response->getStatusCode());

        /** @var array<string, string> $responseData */
        $responseData = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame($expectedVacation->getReason(), $responseData['reason']);
    }

    /**
     * @throws ForbiddenException|DateMalformedStringException
     */
    public function testApprove(): void
    {
        $accessControl = $this->getMockAccessControlService(UserRole::Manager);

        $pendingVacation = $this->createMock(VacationModel::class);
        $pendingVacation->method('getStatus')->willReturn(VacationStatus::Pending);
        $updatedVacation = $this->createMock(VacationModel::class);
        $vacationsRepository = $this->createMock(VacationsRepository::class);
        $vacationsRepository->method('findById')->with(5)->willReturn($pendingVacation);
        $vacationsRepository->method('updateStatus')->with(5, 1, VacationStatus::Approved)->willReturn($updatedVacation);

        $controller = new VacationsController($vacationsRepository, $accessControl);
        $response = $controller->approve($this->createMock(ServerRequest::class), ['id' => 5]);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(200, $response->getStatusCode());
    }

    /**
     * @throws ForbiddenException|DateMalformedStringException
     */
    public function testReject(): void
    {
        $accessControl = $this->getMockAccessControlService(UserRole::Manager);

        $pendingVacation = $this->createMock(VacationModel::class);
        $pendingVacation->method('getStatus')->willReturn(VacationStatus::Pending);
        $updatedVacation = $this->createMock(VacationModel::class);
        $vacationsRepository = $this->createMock(VacationsRepository::class);
        $vacationsRepository->method('findById')->with(5)->willReturn($pendingVacation);
        $vacationsRepository->method('updateStatus')->with(5, 1, VacationStatus::Rejected)->willReturn($updatedVacation);

        $controller = new VacationsController($vacationsRepository, $accessControl);
        $response = $controller->reject($this->createMock(ServerRequest::class), ['id' => 5]);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(200, $response->getStatusCode());
    }

    /**
     * @throws ForbiddenException|DateMalformedStringException
     */
    public function testDeleteSuccess(): void
    {
        $accessControl = $this->getMockAccessControlService(UserRole::Employee);

        $pendingVacation = $this->createMock(VacationModel::class);
        $pendingVacation->method('getUserId')->willReturn(1);
        $pendingVacation->method('getStatus')->willReturn(VacationStatus::Pending);
        $vacationsRepository = $this->createMock(VacationsRepository::class);
        $vacationsRepository->method('findById')->with(7)->willReturn($pendingVacation);
        $vacationsRepository->expects($this->once())
            ->method('delete')
            ->with(7);

        $controller = new VacationsController($vacationsRepository, $accessControl);
        $response = $controller->delete($this->createMock(ServerRequest::class), ['id' => 7]);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(204, $response->getStatusCode());
    }

    /**
     * @throws ForbiddenException|DateMalformedStringException
     */
    public function testDeleteUnauthorized(): void
    {
        $accessControl = $this->getMockAccessControlService(UserRole::Employee);

        $pendingVacation = $this->createMock(VacationModel::class);
        $pendingVacation->method('getUserId')->willReturn(99);
        $pendingVacation->method('getStatus')->willReturn(VacationStatus::Pending);
        $vacationsRepository = $this->createMock(VacationsRepository::class);
        $vacationsRepository->method('findById')->with(8)->willReturn($pendingVacation);

        $controller = new VacationsController($vacationsRepository, $accessControl);
        $response = $controller->delete($this->createMock(ServerRequest::class), ['id' => 8]);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(400, $response->getStatusCode());
    }

    /**
     * @throws ForbiddenException|DateMalformedStringException
     */
    public function testDeleteNonPending(): void
    {
        $accessControl = $this->getMockAccessControlService(UserRole::Employee);

        $approvedVacation = $this->createMock(VacationModel::class);
        $approvedVacation->method('getUserId')->willReturn(1);
        $approvedVacation->method('getStatus')->willReturn(VacationStatus::Approved);
        $vacationsRepository = $this->createMock(VacationsRepository::class);
        $vacationsRepository->method('findById')->with(9)->willReturn($approvedVacation);

        $controller = new VacationsController($vacationsRepository, $accessControl);
        $response = $controller->delete($this->createMock(ServerRequest::class), ['id' => 9]);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(400, $response->getStatusCode());
    }

    /**
     * @throws ForbiddenException|DateMalformedStringException|JsonException
     */
    public function testDeleteForbidden(): void
    {
        $this->expectException(ForbiddenException::class);

        $accessControl = $this->getMockAccessControlService(UserRole::Manager);
        $vacationsRepository = $this->createMock(VacationsRepository::class);

        $controller = new VacationsController($vacationsRepository, $accessControl);
        $controller->delete($this->createMock(ServerRequest::class), ['id' => 9]);
    }

    /**
     * @throws ForbiddenException|DateMalformedStringException|JsonException
     */
    public function testCreateForbidden(): void
    {
        $this->expectException(ForbiddenException::class);

        $accessControl = $this->getMockAccessControlService(UserRole::Manager);
        $vacationsRepository = $this->createMock(VacationsRepository::class);

        $controller = new VacationsController($vacationsRepository, $accessControl);
        $controller->create($this->createMock(ServerRequest::class));
    }

    /**
     * @throws ForbiddenException|DateMalformedStringException
     */
    public function testApproveNonPending(): void
    {
        $accessControl = $this->getMockAccessControlService(UserRole::Manager);

        $approvedVacation = $this->createMock(VacationModel::class);
        $approvedVacation->method('getStatus')->willReturn(VacationStatus::Approved);
        $vacationsRepository = $this->createMock(VacationsRepository::class);
        $vacationsRepository->method('findById')->with(10)->willReturn($approvedVacation);

        $controller = new VacationsController($vacationsRepository, $accessControl);
        $response = $controller->approve($this->createMock(ServerRequest::class), ['id' => 10]);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(400, $response->getStatusCode());
    }

    /**
     * @throws ForbiddenException|DateMalformedStringException
     */
    public function testApproveForbidden(): void
    {
        $this->expectException(ForbiddenException::class);

        $accessControl = $this->getMockAccessControlService(UserRole::Employee);
        $vacationsRepository = $this->createMock(VacationsRepository::class);

        $controller = new VacationsController($vacationsRepository, $accessControl);
        $controller->approve($this->createMock(ServerRequest::class), ['id' => 10]);
    }

    private function getMockAccessControlService(UserRole $role): AccessControlService
    {
        $authenticatedUser = new AuthenticatedUserModel(
            1,
            'John Doe',
            'user@example.com',
            $role,
        );

        $isManager = $role === UserRole::Manager;
        $isEmployee = $role === UserRole::Employee;

        $accessControlService = $this->createMock(AccessControlService::class);
        $accessControlService->method('isManagerFromRequest')->willReturn($isManager);
        $accessControlService->method('isEmployeeFromRequest')->willReturn($isEmployee);
        $accessControlService->method('getAuthenticatedUserModelFromRequest')->willReturn($authenticatedUser);

        return $accessControlService;
    }
}
