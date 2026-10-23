

\# Currency Converter



A currency conversion web application built with Symfony 7, PHP 8.4 and MariaDB.



\## Features



\- User registration and login

\- Protected currency converter

\- Currency list retrieved from the Frankfurter API

\- Live exchange rates

\- Input validation and CSRF protection

\- Graceful handling of exchange-rate API errors

\- Automated tests



\## Tech stack



\- PHP 8.4

\- Symfony 7

\- Twig

\- Doctrine ORM and Doctrine Migrations

\- MariaDB 11.4

\- Symfony HttpClient

\- PHPUnit

\- Docker Compose



\## Requirements



\- Docker Desktop with Docker Compose

\- Internet access to retrieve exchange rates from Frankfurter



\## Getting started



\### 1. Start the application



```bash

docker compose up -d --build

```



\### 2. Install PHP dependencies



```bash

docker compose exec app composer install

```



\### 3. Run database migrations



```bash

docker compose exec app php bin/console doctrine:migrations:migrate --no-interaction

```



\### 4. Open the application



Visit \[http://localhost:8000](http://localhost:8000).



Create an account, sign in, and use the converter.



\## Running tests



```bash

docker compose exec app php bin/phpunit

```



\## Exchange rate API



The application uses the \[Frankfurter API](https://frankfurter.dev/) to retrieve supported currencies and exchange rates.



Exchange rates depend on the data provided by the external service. Internet access is required for conversions.



\## Stopping the application



```bash

docker compose down

```



To remove the local database volume as well, run:



```bash

docker compose down -v

```



\*\*Warning:\*\* the `-v` option deletes the local database data.



