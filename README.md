
# Currency Converter

A currency conversion web application built with **Symfony 7**, **PHP 8.4**, and **MariaDB**.

## Features

- User registration and login
- Protected currency converter
- Supported currencies retrieved from the Frankfurter API
- Live exchange-rate retrieval
- Input validation and CSRF protection
- Graceful handling of external API errors
- Automated tests

## Tech Stack

- **Backend:** PHP 8.4, Symfony 7
- **Templating:** Twig
- **Database:** MariaDB 11.4
- **ORM & Migrations:** Doctrine ORM, Doctrine Migrations
- **HTTP Client:** Symfony HttpClient
- **Testing:** PHPUnit
- **Infrastructure:** Docker Compose

## Requirements

- Docker Desktop with Docker Compose
- Internet access to retrieve exchange rates from Frankfurter

## Getting Started

### 1. Start the application

```bash
docker compose up -d --build
```

### 2. Install PHP dependencies

```bash
docker compose exec app composer install
```

### 3. Run database migrations

```bash
docker compose exec app php bin/console doctrine:migrations:migrate --no-interaction
```

### 4. Open the application

Visit [http://localhost:8000](http://localhost:8000).

Create an account, sign in, and start converting currencies.

## Running Tests

```bash
docker compose exec app php bin/phpunit
```

## Exchange Rate API

The application uses the [Frankfurter API](https://frankfurter.dev/) to retrieve supported currencies and exchange rates.

Exchange rates depend on data provided by the external service. Internet access is required for currency conversions.

## Stopping the Application

Stop the containers:

```bash
docker compose down
```

To remove the local database volume as well, run:

```bash
docker compose down -v
```

**Warning:** The `-v` option deletes the local database data.
