<?php

declare(strict_types=1);

namespace App\Domains\Authentication\Routes;

use App\Domains\Authentication\Controllers\AuthenticationController;
use League\Route\Router;

readonly class AuthenticationRoutes
{
    public function __construct(private Router $router)
    {
    }

    public function register(): void
    {
        $this->router->post('/auth/login', [AuthenticationController::class, 'login']);
        $this->router->post('/auth/renew', [AuthenticationController::class, 'renew']);
        //$this->router->post('/auth/logout', [AuthenticationController::class, 'logout']);
    }
}
