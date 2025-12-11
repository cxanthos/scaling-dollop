<?php

declare(strict_types=1);

namespace App\Domains\Users\Routes;

use App\Domains\Users\Controllers\UsersController;
use League\Route\Router;

readonly class UsersRoutes
{
    public function __construct(private Router $router)
    {
    }

    public function register(): void
    {
        // Create user
        $this->router->post('/users', [UsersController::class, 'create']);

        // Get all users
        $this->router->get('/users', [UsersController::class, 'list']);

        // Get single user by ID
        $this->router->get('/users/{id:number}', [UsersController::class, 'get']);

        $this->router->get('/users/{id:number}/vacations', [UsersController::class, 'getAll']);

        // Update user by ID
        $this->router->put('/users/{id:number}', [UsersController::class, 'update']);

        // Delete user by ID
        $this->router->delete('/users/{id:number}', [UsersController::class, 'delete']);
    }
}
