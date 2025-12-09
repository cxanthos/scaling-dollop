<?php

declare(strict_types=1);

use App\Domains\Authentication\Services\AuthenticationService;
use App\Shared\ErrorHandlingMiddleware;
use App\Shared\PasswordHasherArgon2id;
use App\Shared\Ports\PasswordHasher;
use Laminas\Diactoros\ResponseFactory;
use League\Route\Strategy\JsonStrategy;

require_once __DIR__ . '/../vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../..');
$dotenv->load();

// Create a new container
$container = new League\Container\Container();

// Database configuration
$dbConfig = [
    'driver'   => 'mysql',
    'host'     => $_ENV['DB_HOST'],
    'port'     => $_ENV['DB_PORT'],
    'database' => $_ENV['DB_DATABASE'],
    'username' => $_ENV['DB_USERNAME'],
    'password' => $_ENV['DB_PASSWORD'],
    'charset'  => $_ENV['DB_CHARSET'],
];

// Add the database to the container
$container->add(App\Shared\Database::class, function () use ($dbConfig) {
    return new App\Shared\Database($dbConfig);
});

// Add the request and response to the container
$container->add(Psr\Http\Message\RequestInterface::class, function () {
    return Laminas\Diactoros\ServerRequestFactory::fromGlobals(
        $_SERVER,
        $_GET,
        $_POST,
        $_COOKIE,
        $_FILES
    );
});
$container->add(Psr\Http\Message\ResponseInterface::class, Laminas\Diactoros\Response::class);
$container->add(PasswordHasher::class, PasswordHasherArgon2id::class);
$container->add(AuthenticationService::class, function () {
    return new AuthenticationService(
        $_ENV['JWT_SECRET'],
    );
});

// Add the router to the container
$container->add(League\Route\Router::class, function () use ($container) {
    $strategy = new JsonStrategy(new ResponseFactory());
    $strategy->setContainer($container);

    $router = new League\Route\Router();
    $router->setStrategy($strategy); // Set the strategy here

    // Add your routes here
    new App\Domains\Authentication\Routes\AuthenticationRoutes($router)->register();
    new App\Domains\Users\Routes\UsersRoutes($router)->register();
    new App\Domains\Vacations\Routes\VacationsRoutes($router)->register();

    return $router;
});
// Enable autowiring
$container->delegate(new League\Container\ReflectionContainer());

// Get the router from the container
$router = $container->get(League\Route\Router::class);
$router->middleware(new ErrorHandlingMiddleware());

// Dispatch the request
$request = $container->get(Psr\Http\Message\RequestInterface::class);
$response = $router->dispatch($request);

// Send the response
new Laminas\HttpHandlerRunner\Emitter\SapiEmitter()->emit($response);
