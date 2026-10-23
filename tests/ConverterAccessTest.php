<?php

namespace App\Tests;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
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

    public function testAuthenticatedUserCanAccessConverterAndSeeCsrfToken(): void
    {
        $client = static::createClient();

        $user = new User();
        $user->setEmail(uniqid('test-', true) . '@example.com');
        $user->setPassword('test-password');

        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $entityManager->persist($user);
        $entityManager->flush();

        $client->loginUser($user);

        $client->request('GET', '/');

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('form[action="/"] input[name="_token"]');

        $entityManager->remove($user);
        $entityManager->flush();
    }
}
