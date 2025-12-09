<?php

declare(strict_types=1);

namespace App\Shared;

use InvalidArgumentException;
use JsonException;
use League\Route\Http\Exception\ForbiddenException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Throwable;

class ErrorHandlingMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            return $handler->handle($request);
        } catch (InvalidArgumentException|JsonException $e) {
            return new JsonErrorResponse(400, $e->getMessage());
        } catch (ForbiddenException $e) {
            return new JsonErrorResponse(403, $e->getMessage());
        } catch (Throwable $e) {
            // Those can be replaced by proper logging calls
            // echo $e->getMessage();
            // echo $e->getTraceAsString();
            return new JsonErrorResponse(500, 'Internal Server Error');
        }
    }
}
