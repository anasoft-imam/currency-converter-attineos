<?php

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class ConverterAccessTest extends WebTestCase
{
    public function testUnauthenticatedUserIsRedirectedToLogin(): void
    {
        $client = static::createClient();

        $client->request('GET', '/');

        self::assertResponseRedirects('/login');
    }

    public function testLoginPageIsAccessibleWithoutAuthentication(): void
    {
        $client = static::createClient();

        $client->request('GET', '/login');

        self::assertResponseIsSuccessful();
    }

    public function testRegistrationPageIsAccessibleWithoutAuthentication(): void
    {
        $client = static::createClient();

        $client->request('GET', '/register');

        self::assertResponseIsSuccessful();
    }

    public function testConverterPageContainsCsrfToken(): void
    {
        $client = static::createClient();

        // Un utilisateur non connecté est redirigé vers la connexion.
        $client->request('GET', '/');

        $client->followRedirect();

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('input[name="_csrf_token"]');
    }
}
