<?php

declare(strict_types=1);

namespace App\Domains\Vacations\Routes;

use App\Domains\Vacations\Controllers\VacationsController;
use League\Route\Router;

readonly class VacationsRoutes
{
    public function __construct(private Router $router)
    {
    }

    public function register(): void
    {
        // Get all vacation requests (manager: pending, employee: own)
        $this->router->get('/vacations', [VacationsController::class, 'list']);

        // Approve/reject a vacation request
        $this->router->put('/vacations/{id:number}/approve', [VacationsController::class, 'approve']);
        $this->router->put('/vacations/{id:number}/reject', [VacationsController::class, 'reject']);

        // Create vacation request
        $this->router->post('/vacations', [VacationsController::class, 'create']);

        // Delete vacation request (own)
        $this->router->delete('/vacations/{id:number}', [VacationsController::class, 'delete']);
    }
}
