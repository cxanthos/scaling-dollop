<?php

declare(strict_types=1);

namespace App\Domains\Vacations\Controllers;

use App\Domains\Authentication\Services\AccessControlService;
use App\Domains\Vacations\Models\VacationModel;
use App\Domains\Vacations\Models\VacationStatus;
use App\Domains\Vacations\Repositories\VacationsRepository;
use App\Shared\JsonErrorResponse;
use DateMalformedStringException;
use DateTime;
use JsonException;
use Laminas\Diactoros\Response\JsonResponse;
use League\Route\Http\Exception\ForbiddenException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

readonly class VacationsController
{
    public function __construct(private VacationsRepository $vacationsRepository, private AccessControlService $accessControl)
    {
    }

    /**
     * @throws DateMalformedStringException
     */
    public function list(ServerRequestInterface $request): ResponseInterface
    {
        // Just checking the isManagerFromRequest also validates that the user is authenticated. If we introduce
        // more roles in the future, we will need to adjust this logic.
        if ($this->accessControl->isManagerFromRequest($request)) {
            $vacationsCollection = $this->vacationsRepository->findPendingRequests();
        } else {
            $authenticatedUser = $this->accessControl->getAuthenticatedUserModelFromRequest($request);
            $vacationsCollection = $this->vacationsRepository->findUserRequests($authenticatedUser->getId());
        }

        return new JsonResponse($vacationsCollection, 200);
    }

    /**
     * @param array{id: int} $params
     * @throws ForbiddenException|DateMalformedStringException
     */
    public function approve(ServerRequestInterface $request, array $params): ResponseInterface
    {
        return $this->updateStatus($request, $params, VacationStatus::Approved);
    }

    /**
     * @param array{id: int} $params
     * @throws ForbiddenException|DateMalformedStringException
     */
    public function reject(ServerRequestInterface $request, array $params): ResponseInterface
    {
        return $this->updateStatus($request, $params, VacationStatus::Rejected);
    }

    /**
     * @throws JsonException|ForbiddenException|DateMalformedStringException
     */
    public function create(ServerRequestInterface $request): ResponseInterface
    {
        $this->requireEmployee($request);
        $authenticatedUser = $this->accessControl->getAuthenticatedUserModelFromRequest($request);

        /** @var array<string, string> $body */
        $body = json_decode((string) $request->getBody(), true, 512, JSON_THROW_ON_ERROR);

        $vacationModel = new VacationModel(
            null,
            $authenticatedUser->getId(),
            null,
            new DateTime($body['startDate'] ?? ''),
            new DateTime($body['endDate'] ?? ''),
            $body['reason'] ?? '',
            VacationStatus::Pending
        );

        $vacationModel = $this->vacationsRepository->create($vacationModel);

        return new JsonResponse($vacationModel, 201);
    }

    /**
     * @param array{id: int} $params
     * @throws ForbiddenException|DateMalformedStringException
     */
    public function delete(ServerRequestInterface $request, array $params): ResponseInterface
    {
        $this->requireEmployee($request);
        $authenticatedUser = $this->accessControl->getAuthenticatedUserModelFromRequest($request);
        $vacationId = (int) $params['id'];

        $vacationModel = $this->vacationsRepository->findById($vacationId);

        if ($authenticatedUser->getId() !== $vacationModel->getUserId()) {
            return new JsonErrorResponse(400, 'Vacation with ID '.$params['id'].' not found.');
        }

        if ($vacationModel->getStatus() != VacationStatus::Pending) {
            return new JsonErrorResponse(400, 'Only pending vacations can be deleted.');
        }

        $this->vacationsRepository->delete($vacationId);

        return new JsonResponse('', 204);
    }

    /**
     * @param array{id: int} $params
     * @throws ForbiddenException|DateMalformedStringException
     */
    private function updateStatus(ServerRequestInterface $request, array $params, VacationStatus $status): ResponseInterface
    {
        $this->requireManager($request);
        $authenticatedUser = $this->accessControl->getAuthenticatedUserModelFromRequest($request);
        $vacationId = (int) $params['id'];

        $vacationModel = $this->vacationsRepository->findById($vacationId);

        if ($vacationModel->getStatus() != VacationStatus::Pending) {
            return new JsonErrorResponse(400, 'Only pending vacations can be updated.');
        }

        $vacationModel = $this->vacationsRepository->updateStatus($vacationId, $authenticatedUser->getId(), $status);

        return new JsonResponse($vacationModel, 200);
    }

    /**
     * @param ServerRequestInterface $request
     * @return void
     * @throws ForbiddenException
     */
    private function requireManager(ServerRequestInterface $request): void
    {
        if (!$this->accessControl->isManagerFromRequest($request)) {
            throw new ForbiddenException();
        }
    }

    /**
     * @param ServerRequestInterface $request
     * @return void
     * @throws ForbiddenException
     */
    private function requireEmployee(ServerRequestInterface $request): void
    {
        if (!$this->accessControl->isEmployeeFromRequest($request)) {
            throw new ForbiddenException();
        }
    }
}
