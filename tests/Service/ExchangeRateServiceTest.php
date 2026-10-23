<?php

namespace App\Tests\Service;

use App\Service\ExchangeRateService;
use PHPUnit\Framework\TestCase;
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

        $service = new ExchangeRateService($httpClient);

        self::assertSame(1.0, $service->getRate('EUR', 'EUR'));
    }

    public function testGetRateReturnsRateProvidedByApi(): void
    {
        $httpClient = new MockHttpClient([
            new MockResponse(
                '{"date":"2026-10-24","base":"EUR","quote":"USD","rate":1.12}'
            ),
        ]);

        $service = new ExchangeRateService($httpClient);

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

        $service = new ExchangeRateService($httpClient);

        $this->expectException(\Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface::class);

        $service->getRate('EUR', 'USD');
    }
}
