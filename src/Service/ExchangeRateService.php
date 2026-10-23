<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

final class ExchangeRateService
{
    private const API_BASE_URL = 'https://api.frankfurter.dev/v2';

    public function __construct(
        private readonly HttpClientInterface $httpClient,
    ) {
    }

    /**
     * @return array<string, array{name: string, symbol?: string}>
     */
    public function getCurrencies(): array
    {
        $response = $this->httpClient->request(
            'GET',
            self::API_BASE_URL . '/currencies',
        );

        $currencies = [];

        foreach ($response->toArray() as $currency) {
            $code = $currency['iso_code'] ?? $currency['currency'] ?? null;

            if ($code === null) {
                continue;
            }

            $currencies[$code] = [
                'name' => $currency['name'] ?? $code,
                'symbol' => $currency['symbol'] ?? '',
            ];
        }

        return $currencies;
    }

    public function getRate(string $from, string $to): float
    {
        $from = strtoupper($from);
        $to = strtoupper($to);

        if ($from === $to) {
            return 1.0;
        }

        $response = $this->httpClient->request(
            'GET',
            sprintf(
                '%s/rate/%s/%s',
                self::API_BASE_URL,
                $from,
                $to,
            ),
        );

        $data = $response->toArray();

        return (float) $data['rate'];
    }
}
