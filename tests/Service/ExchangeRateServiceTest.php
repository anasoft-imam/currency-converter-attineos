<?php

namespace App\Tests\Service;

use App\Service\ExchangeRateService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class ExchangeRateServiceTest extends TestCase
{
    public function testSameCurrencyReturnsRateOfOneWithoutCallingApi(): void
    {
        $httpClient = new MockHttpClient(
            static function () {
                self::fail('L’API ne doit pas être appelée pour une même devise.');
            }
        );

        $service = new ExchangeRateService($httpClient, new ArrayAdapter());

        self::assertSame(1.0, $service->getRate('EUR', 'EUR'));
    }

    public function testGetRateReturnsRateProvidedByApi(): void
    {
        $httpClient = new MockHttpClient([
            new MockResponse(
                '{"date":"2026-10-24","base":"EUR","quote":"USD","rate":1.12}'
            ),
        ]);

        $service = new ExchangeRateService($httpClient, new ArrayAdapter());

        self::assertSame(1.12, $service->getRate('EUR', 'USD'));
    }

    public function testGetRateThrowsExceptionWhenApiReturnsAnError(): void
    {
        $httpClient = new MockHttpClient([
            new MockResponse(
                '{"message":"Service unavailable"}',
                ['http_code' => 503]
            ),
        ]);

        $service = new ExchangeRateService($httpClient, new ArrayAdapter());

        $this->expectException(\Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface::class);

        $service->getRate('EUR', 'USD');
    }


    public function testGetRateRejectsMissingRate(): void
    {
        $httpClient = new MockHttpClient([
            new MockResponse('{"message":"Rate unavailable"}'),
        ]);

        $service = new ExchangeRateService($httpClient, new ArrayAdapter());

        $this->expectException(\UnexpectedValueException::class);

        $service->getRate('EUR', 'USD');
    }

    public function testGetRateRejectsInvalidRate(): void
    {
        $httpClient = new MockHttpClient([
            new MockResponse('{"rate":"invalid"}'),
        ]);

        $service = new ExchangeRateService($httpClient, new ArrayAdapter());

        $this->expectException(\UnexpectedValueException::class);

        $service->getRate('EUR', 'USD');
    }

    public function testGetRateRejectsZeroOrNegativeRate(): void
    {
        foreach ([0, -1.5] as $invalidRate) {
            $httpClient = new MockHttpClient([
                new MockResponse(json_encode(['rate' => $invalidRate])),
            ]);

            $service = new ExchangeRateService($httpClient, new ArrayAdapter());

            try {
                $service->getRate('EUR', 'USD');
                self::fail('An invalid exchange rate should be rejected.');
            } catch (\UnexpectedValueException) {
                self::assertTrue(true);
            }
        }
    }

    public function testGetRateRejectsInvalidCurrencyCode(): void
    {
        $httpClient = new MockHttpClient(
            static function () {
                self::fail('The API must not be called for invalid currency codes.');
            }
        );

        $service = new ExchangeRateService($httpClient, new ArrayAdapter());

        $this->expectException(\InvalidArgumentException::class);

        $service->getRate('INVALID', 'USD');
    }

    public function testGetCurrenciesReturnsSupportedCurrencies(): void
    {
        $httpClient = new MockHttpClient([
            new MockResponse(json_encode([
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
            ], JSON_THROW_ON_ERROR)),
        ]);

        $service = new ExchangeRateService($httpClient, new ArrayAdapter());

        self::assertSame(
            [
                'EUR' => ['name' => 'Euro', 'symbol' => '€'],
                'USD' => ['name' => 'US Dollar', 'symbol' => '$'],
            ],
            $service->getCurrencies(),
        );
    }


    /**
     * Ensures that the currency list is fetched only once
     * when the cache already contains a valid response.
     */
    public function testGetCurrenciesUsesCache(): void
    {
        $requestCount = 0;

        $httpClient = new MockHttpClient(
            static function () use (&$requestCount): MockResponse {
                ++$requestCount;

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
            },
        );

        $service = new ExchangeRateService(
            $httpClient,
            new ArrayAdapter(),
        );

        $firstResult = $service->getCurrencies();
        $secondResult = $service->getCurrencies();

        self::assertSame($firstResult, $secondResult);
        self::assertCount(2, $firstResult);
        self::assertSame(1, $requestCount);
    }

}
