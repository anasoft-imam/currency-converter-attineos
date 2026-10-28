# Currency Converter

A currency conversion web application built with **PHP 8.4, Symfony 7.4, and MariaDB 11.4**.

The application allows authenticated users to convert amounts between supported currencies using exchange rates provided by the [Frankfurter API](https://frankfurter.dev/).

The project focuses on clean code, separation of responsibilities, security, testability, and a straightforward user experience.

## Features

- User registration and authentication
- Currency converter accessible to authenticated users
- Supported currencies retrieved from the Frankfurter API
- Exchange rates retrieved on demand
- Currency list cached for 24 hours to reduce external API calls
- Amount and currency validation
- CSRF protection for form submissions
- User-friendly error handling for API failures and invalid input
- Loading indicator during form submission
- Automated unit and functional tests

## Tech Stack

| Component | Technology |
|---|---|
| Backend | PHP 8.4, Symfony 7.4 |
| Frontend | Twig, HTML, CSS, JavaScript |
| Database | MariaDB 11.4 |
| ORM & Migrations | Doctrine ORM, Doctrine Migrations |
| HTTP Client | Symfony HttpClient |
| Caching | Symfony Cache (PSR-6) |
| Testing | PHPUnit, Symfony WebTestCase, MockHttpClient |
| Infrastructure | Docker Compose |

## Requirements

- Docker Desktop with Docker Compose
- Internet access to retrieve exchange rates from Frankfurter

No local PHP, Composer, or MariaDB installation is required.

## Getting Started

### 1. Clone the repository

```bash
git clone https://github.com/anasoft-imam/currency-converter-attineos.git
cd currency-converter-attineos
```

### 2. Start the containers

```bash
docker compose up -d --build
```

Docker Compose starts the Symfony application and a MariaDB database.

### 3. Install PHP dependencies

```bash
docker compose exec app composer install
```

### 4. Run database migrations

```bash
docker compose exec app php bin/console doctrine:migrations:migrate --no-interaction
```

### 5. Open the application

Visit **http://localhost:8000**.

Register a user account, sign in, and start converting currencies.

## Test Environment Setup

The application uses a dedicated test database to keep test data separate from the development environment.

Before running the tests for the first time, follow these steps.

### 1. Create the `.env.test` file

Create a file named `.env.test` at the root of the project.

Add the following configuration:

```dotenv
APP_ENV=test
APP_SECRET=test_secret_key
DATABASE_URL="mysql://app:app@db:3306/currency_converter_test?serverVersion=mariadb-11.4.0&charset=utf8mb4"
```

**Important:** The database username, password, hostname, and database name must match your Docker Compose and Doctrine configuration.

The example above assumes:

- Database username: `app`
- Database password: `app`
- Database hostname: `db`
- Test database name: `currency_converter_test`

Do not use the development or production database for tests.

If a `.env.test.example` file is provided in the repository, you can copy it instead of creating the file manually:

```bash
cp .env.test.example .env.test
```

### 2. Create the test database

```bash
docker compose exec app php bin/console doctrine:database:create --env=test --if-not-exists
```

### 3. Apply database migrations in the test environment

```bash
docker compose exec app php bin/console doctrine:migrations:migrate --env=test --no-interaction
```

The test environment is now ready.

## Running Tests

The project includes unit and functional tests covering authentication, currency conversion, validation, API failures, and caching.

Run the complete test suite:

```bash
docker compose exec app php bin/phpunit --testdox
```

The tests use a dedicated test database and Symfony's `MockHttpClient` to simulate external API responses without depending on live exchange rates.

The test suite includes unit and functional tests. Test and assertion counts can be checked in the PHPUnit output.

### Test Coverage

| Area | Scenarios |
|---|---|
| Authentication | Protected routes, login and registration page accessibility |
| Currency conversion | Successful conversion, invalid amount, unsupported currency |
| Security | CSRF token validation |
| External API | Successful responses, unavailable API, malformed exchange rates |
| Caching | Reuse of cached currency data without additional HTTP requests |
| Logging | API failures logged with exception context |

## Architecture and Design Decisions

### Separation of Responsibilities

The application separates HTTP request handling from exchange-rate retrieval:

- `ConverterController` handles form display, submission, validation, and user-facing responses.
- `ExchangeRateService` communicates with the Frankfurter API, validates external data, and manages currency caching.
- Twig templates handle presentation.

GET and POST requests use dedicated controller actions, keeping form rendering and submission processing distinct.

### External API Integration

The application uses the Frankfurter API to retrieve:

- Supported currencies: `GET /v2/currencies`
- Exchange rates: `GET /v2/rate/{from}/{to}`

External API responses are validated before use.

Network failures and invalid responses are handled gracefully, with technical details logged and user-friendly messages displayed.

### Caching Strategy

The list of supported currencies is cached for **24 hours** using Symfony Cache.

This avoids unnecessary HTTP requests whenever users open or submit the conversion form.

Exchange rates are retrieved on demand rather than cached, keeping the implementation straightforward and avoiding application-level rate staleness.

When the source and target currencies are identical, the exchange-rate service returns `1.0` without making an external request.

### Security

- Converter routes require authentication.
- Form submissions are protected by Symfony CSRF tokens.
- User-provided amounts and currency codes are validated server-side.
- Authentication is managed by Symfony Security.

### Testing Strategy

The application combines service-level unit tests with functional HTTP tests.

**Unit tests** validate the exchange-rate service independently, including valid and invalid rates, currency validation, API errors, and caching behavior.

**Functional tests** use Symfony's `WebTestCase` to verify HTTP responses, authenticated access, form submissions, error messages, CSRF protection, and logging.

External API calls are simulated using `MockHttpClient`, allowing deterministic tests without requiring a live connection to Frankfurter.

An in-memory cache adapter is used in service tests to verify caching behavior independently.

## Docker Development Environment

Docker Compose provides a consistent development environment using PHP 8.4 and MariaDB 11.4.

The application source code is mounted into the container for local development.

Dedicated Docker volumes are used for Composer dependencies, Symfony cache, and logs to reduce filesystem overhead, particularly when developing with Docker Desktop on Windows.

After changing Composer dependencies, run:

```bash
docker compose exec app composer install
```

Dependencies inside the Docker volume are managed separately from any local `vendor` directory.

## Stopping the Application

Stop the containers without deleting the database:

```bash
docker compose down
```

To remove containers and their associated named volumes:

```bash
docker compose down -v
```

**Warning:** The `-v` option permanently deletes local MariaDB data and other named volumes, including cached files and container-managed dependencies.

## Limitations

- Exchange rates depend on the availability of the Frankfurter API.
- Internet access is required for retrieving uncached currency data and exchange rates.
- Converted amounts are displayed with two decimal places.
- The application is intended as a technical exercise and demonstration project, not a financial trading platform.

## License

This project was developed as a technical assessment.
