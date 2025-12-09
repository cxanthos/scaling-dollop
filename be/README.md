# Backend structure

This project is structured following the principles of **Domain-Driven Design (DDD)**. The core logic is organized into distinct business domains, promoting a clear separation of concerns and making the codebase easier to understand and maintain.

Here is an overview of the project's directory structure:

*   `.`: Configuration files for tools like PHPStan, PHP CS Fixer, PHPUnit, and Phinx.
*   `db`: Database-related files, including migrations and seeds.
*   `docker`: Docker-related files, like the Dockerfile for building the application container.
*   `public`: The web server's document root. This is the only publicly accessible directory.
    *   `index.php`: The application's front controller.
*   `src`: The application's source code.
    *   `Domains`: Contains the different domains of the application, each with its own Models, Repositories, Controllers, and Routes. This is the heart of the DDD approach.
        *   `Authentication`: Handles user authentication.
        *   `Users`: Manages user-related operations.
        *   `Vacations`: Manages vacation-related operations.
    *   `Shared`: Contains code shared across different domains, like database connections, interfaces, and error handling.
*   `tests`: Contains the application's tests.
*   `vendor`: Contains the project's Composer dependencies.

# Quality Tools

## Coding Standards

This project follows the [PSR-12](https://www.php-fig.org/psr/psr-12/) coding standard.

To check for compliance, run the following command:

```bash
composer check:style
```

To automatically fix coding standard violations, run:

```bash
composer fix:style
```

## Static Analysis

The project uses [PHPStan](https://phpstan.org/) for static analysis to find potential bugs before they reach production.

To run the static analysis, use the following command:

```bash
composer check:static
```

## Testing

This project uses [PHPUnit](https://phpunit.de/) for unit and integration testing.

To run the tests, use the following command:

```bash
composer check:tests
```

# Technologies

* PHP 8.4
* Phinx for database migrations
* Composer for dependency management
* League Container for dependency injection
* League Route for routing
* Firebase JWT for JSON Web Token handling
* Dotenv for environment variable management
* Laminas Diactoros for PSR-7 HTTP message implementation

## Database migrations and seeding

Database migrations and seeding are managed using [Phinx](https://phinx.org/).

To run database migrations, use the following command:

```bash
composer db:migrate
```

To seed the database with initial data, use the following command:

```bash
composer db:seed
```

To create a new migration file, use the following command:

```bash
composer db:create-migration <MigrationName>
```

To create a new seed file, use the following command:

```bash
composer db:create-seed <SeedName>
```