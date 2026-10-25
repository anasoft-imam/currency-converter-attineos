<?php

namespace App\Tests\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Doctrine\ORM\EntityManagerInterface;
use App\Service\ExchangeRateService;

final class ConverterControllerTest extends WebTestCase
{
    private ?int $testUserId = null;

    private function createAuthenticatedClient(
        MockHttpClient $httpClient,
    ): KernelBrowser {
        $client = static::createClient();

        // Preserve mocked services between GET and POST requests.
        $client->disableReboot();

        static::getContainer()->set(
            ExchangeRateService::class,
            new ExchangeRateService(
                $httpClient,
                new ArrayAdapter(),
            ),
        );

        $entityManager = static::getContainer()->get(
            EntityManagerInterface::class,
        );

        $user = new User();
        $user->setEmail(
            'converter-' . bin2hex(random_bytes(8)) . '@example.com',
        );
        $user->setPassword('unused-test-password');

        $entityManager->persist($user);
        $entityManager->flush();

        $this->testUserId = $user->getId();

        $client->loginUser($user);

        return $client;
    }

    private function createHttpClient(
        float $rate = 1.12,
    ): MockHttpClient {
        return new MockHttpClient(
            static function (
                string $method,
                string $url,
            ) use ($rate): MockResponse {
                if (str_ends_with($url, '/currencies')) {
                    return new MockResponse(
                        json_encode([
                            [
                                'iso_code' => 'EUR',
                                'name' => 'Euro',
                                'symbol' => '€',
                            ],
                            [
                                'iso_code' => 'USD',
                                'name' => 'US Dollar',
                                'symbol' => '$',
                            ],
                        ], JSON_THROW_ON_ERROR),
                    );
                }

                if (str_ends_with($url, '/rate/EUR/USD')) {
                    return new MockResponse(
                        json_encode([
                            'rate' => $rate,
                        ], JSON_THROW_ON_ERROR),
                    );
                }

                throw new \LogicException(
                    sprintf('Unexpected API request: %s %s', $method, $url),
                );
            },
        );
    }

    public function testSuccessfulConversion(): void
    {
        $client = $this->createAuthenticatedClient(
            $this->createHttpClient(1.12),
        );

        $crawler = $client->request('GET', '/');

        self::assertResponseIsSuccessful();

        $form = $crawler
            ->selectButton('Convertir le montant')
            ->form([
                'amount' => '100',
                'from' => 'EUR',
                'to' => 'USD',
            ]);

        $client->submit($form);

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains(
            '.result-amount',
            '112,00 USD',
        );
    }

    public function testInvalidAmountDisplaysError(): void
    {
        $client = $this->createAuthenticatedClient(
            $this->createHttpClient(),
        );

        $crawler = $client->request('GET', '/');

        $token = $crawler
            ->filter('input[name="_token"]')
            ->attr('value');

        // Direct POST bypasses browser-side number validation.
        $client->request('POST', '/', [
            '_token' => $token,
            'amount' => '-10',
            'from' => 'EUR',
            'to' => 'USD',
        ]);

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains(
            '[role="alert"]',
            'Veuillez saisir un montant valide supérieur à zéro.',
        );
        self::assertSelectorNotExists('.result-card');
    }

    public function testInvalidCurrencyDisplaysError(): void
    {
        $client = $this->createAuthenticatedClient(
            $this->createHttpClient(),
        );

        $crawler = $client->request('GET', '/');

        $token = $crawler
            ->filter('input[name="_token"]')
            ->attr('value');

        $client->request('POST', '/', [
            '_token' => $token,
            'amount' => '100',
            'from' => 'INVALID',
            'to' => 'USD',
        ]);

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains(
            '[role="alert"]',
            'Veuillez sélectionner des devises valides.',
        );
        self::assertSelectorNotExists('.result-card');
    }

    public function testApiUnavailableDisplaysError(): void
    {
        $httpClient = new MockHttpClient(
            static fn (): MockResponse => new MockResponse(
                '{"message":"Service unavailable"}',
                ['http_code' => 503],
            ),
        );

        $client = $this->createAuthenticatedClient($httpClient);

        $client->request('GET', '/');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains(
            '[role="alert"]',
            'Le service de devises est temporairement indisponible.',
        );
    }

    public function testInvalidCsrfTokenIsRejected(): void
    {
        $client = $this->createAuthenticatedClient(
            $this->createHttpClient(),
        );

        $client->request('POST', '/', [
            '_token' => 'invalid-token',
            'amount' => '100',
            'from' => 'EUR',
            'to' => 'USD',
        ]);

        self::assertResponseStatusCodeSame(403);
    }

    protected function tearDown(): void
    {
        if ($this->testUserId !== null) {
            $container = static::getContainer();

            $entityManager = $container->get(
                EntityManagerInterface::class,
            );

            $user = $entityManager->find(
                User::class,
                $this->testUserId,
            );

            if ($user !== null) {
                $entityManager->remove($user);
                $entityManager->flush();
            }
        }

        $this->testUserId = null;

        parent::tearDown();
    }
}
