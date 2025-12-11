<?php declare(strict_types=1);

namespace App\Domains\Users\Controllers;

use App\Domains\Authentication\Services\AccessControlService;
use App\Domains\Users\Models\UserModel;
use App\Domains\Users\Models\UserRole;
use App\Domains\Users\Repositories\UsersRepository;
use App\Domains\Vacations\Repositories\VacationsRepository;
use App\Shared\JsonErrorResponse;
use Laminas\Diactoros\Response\JsonResponse;
use League\Container\Exception\NotFoundException;
use League\Route\Http\Exception\ForbiddenException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use DateMalformedStringException;
use JsonException;

readonly class UsersController
{
    public function __construct(
        private UsersRepository $usersRepository,
        private AccessControlService $accessControl
    ) {}

    /**
     * @throws JsonException|DateMalformedStringException|ForbiddenException
     */
    public function create(ServerRequestInterface $request): ResponseInterface
    {
        $this->requireManager($request);

        /** @var array<string, string> $body */
        $body = json_decode((string) $request->getBody(), true, 512, JSON_THROW_ON_ERROR);

        $userModel = new UserModel(
            null,
            $body['email'] ?? '',
            $body['password'] ?? '',  // Do not allow the password to be null
            $body['name'] ?? '',
            $body['employeeCode'] ?? '',
            UserRole::from($body['role'] ?? 'employee')
        );

        $userModel = $this->usersRepository->create($userModel);

        return new JsonResponse($userModel, 201);
    }

    /**
     * @throws DateMalformedStringException|ForbiddenException
     */
    public function list(ServerRequestInterface $request): ResponseInterface
    {
        $this->requireManager($request);

        $usersCollection = $this->usersRepository->findAll();

        return new JsonResponse($usersCollection, 200);
    }

    /**
     * @param array{id: int} $params
     * @throws DateMalformedStringException|ForbiddenException
     */
    public function get(ServerRequestInterface $request, array $params): ResponseInterface
    {
        $this->requireManager($request);

        $userModel = $this->usersRepository->findById((int) $params['id']);

        return new JsonResponse($userModel, 200);
    }

    /**/
    public function getAll(ServerRequestInterface $request, array $params): ResponseInterface {

        $this->requireManager($request);

        $userModel = $this->usersRepository->findById(id: (int) $params['id']);

        $vacationRepo = new VacationsRepository();
        $vacations = $vacationRepo->findUserRequests($params['id']);

        return new JsonResponse($vacations, 200);
    }


    /**
     * @param array{id: int} $params
     * @throws JsonException|DateMalformedStringException|ForbiddenException
     */
    public function update(ServerRequestInterface $request, array $params): ResponseInterface
    {
        $this->requireManager($request);

        /** @var array<string, string> $body */
        $body = json_decode((string) $request->getBody(), true, 512, JSON_THROW_ON_ERROR);
        $existingUserModel = $this->usersRepository->findById((int) $params['id']);

        $userModel = new UserModel(
            $existingUserModel->getId(),
            $body['email'] ?? $existingUserModel->getEmail(),
            $body['password'] ?? '',
            $body['name'] ?? $existingUserModel->getName(),
            $existingUserModel->getEmployeeCode(),
            UserRole::from($body['role'] ?? $existingUserModel->getRole()->value)
        );

        $userModel = $this->usersRepository->update($userModel);

        return new JsonResponse($userModel, 200);
    }

    /**
     * @param array{id: int} $params
     * @throws ForbiddenException
     */
    public function delete(ServerRequestInterface $request, array $params): ResponseInterface
    {
        $this->requireManager($request);
        $authenticatedUser = $this->accessControl->getAuthenticatedUserModelFromRequest($request);
        $userId = (int) $params['id'];

        if ($authenticatedUser->getId() === $userId) {
            return new JsonErrorResponse(400, 'You cannot delete your own account');
        }

        $this->usersRepository->delete($userId);

        return new JsonResponse('', 204);
    }

    /**
     * @param ServerRequestInterface $request
     * @return void
     * @throws ForbiddenException
     */
    public function requireManager(ServerRequestInterface $request): void
    {
        if (!$this->accessControl->isManagerFromRequest($request)) {
            throw new ForbiddenException();
        }
    }
}
