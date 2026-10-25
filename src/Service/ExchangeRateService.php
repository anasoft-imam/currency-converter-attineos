<?php

declare(strict_types=1);

namespace App\Service;

use Psr\Cache\CacheItemPoolInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class ExchangeRateService
{
    private const API_BASE_URL = 'https://api.frankfurter.dev/v2';
    private const REQUEST_TIMEOUT = 5;
    private const CURRENCIES_CACHE_KEY = 'frankfurter_currencies_v1';
    private const CURRENCIES_CACHE_TTL = 86400;

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly CacheItemPoolInterface $cache,
    ) {
    }

    /**
     * Returns supported currencies indexed by their ISO 4217 code.
     *
     * The list is cached for 24 hours to avoid an external HTTP request
     * every time the conversion form is displayed.
     *
     * @return array<string, array{name: string, symbol: string}>
     *
     * @throws \UnexpectedValueException If the API response is invalid.
     */
    public function getCurrencies(): array
    {
        $item = $this->cache->getItem(self::CURRENCIES_CACHE_KEY);

        if ($item->isHit()) {
            $cached = $item->get();

            if (is_array($cached) && $cached !== []) {
                return $cached;
            }
        }

        $currencies = $this->fetchCurrencies();

        // Cache only validated API responses.
        $item->set($currencies);
        $item->expiresAfter(self::CURRENCIES_CACHE_TTL);
        $this->cache->save($item);

        return $currencies;
    }

    /**
     * Returns the exchange rate for one unit of the source currency.
     *
     * No HTTP request is required when both currencies are identical.
     *
     * @throws \InvalidArgumentException If a currency code is malformed.
     * @throws \UnexpectedValueException If the API returns an invalid rate.
     */
    public function getRate(string $from, string $to): float
    {
        $from = strtoupper(trim($from));
        $to = strtoupper(trim($to));

        foreach ([$from, $to] as $currency) {
            if (!preg_match('/^[A-Z]{3}$/', $currency)) {
                throw new \InvalidArgumentException(
                    'Invalid currency code.',
                );
            }
        }

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
            ['timeout' => self::REQUEST_TIMEOUT],
        );

        $data = $response->toArray();
        $rate = $data['rate'] ?? null;

        if (
            !is_int($rate)
            && !is_float($rate)
            && !(is_string($rate) && is_numeric($rate))
        ) {
            throw new \UnexpectedValueException(
                'The currency API returned an invalid exchange rate.',
            );
        }

        $rate = (float) $rate;

        if (!is_finite($rate) || $rate <= 0) {
            throw new \UnexpectedValueException(
                'The currency API returned an invalid exchange rate.',
            );
        }

        return $rate;
    }

    /**
     * Fetches and validates the currency list from Frankfurter.
     *
     * @return array<string, array{name: string, symbol: string}>
     *
     * @throws \UnexpectedValueException If no valid currency is returned.
     */
    private function fetchCurrencies(): array
    {
        $response = $this->httpClient->request(
            'GET',
            self::API_BASE_URL . '/currencies',
            ['timeout' => self::REQUEST_TIMEOUT],
        );

        $data = $response->toArray();
        $currencies = [];

        foreach ($data as $currency) {
            if (!is_array($currency)) {
                continue;
            }

            $code = $currency['iso_code']
                ?? $currency['currency']
                ?? null;

            if (
                !is_string($code)
                || !preg_match('/^[A-Z]{3}$/', $code)
            ) {
                continue;
            }

            $name = $currency['name'] ?? $code;
            $symbol = $currency['symbol'] ?? '';

            $currencies[$code] = [
                'name' => is_string($name) ? $name : $code,
                'symbol' => is_string($symbol) ? $symbol : '',
            ];
        }

        if ($currencies === []) {
            throw new \UnexpectedValueException(
                'The currency API returned no valid currencies.',
            );
        }

        ksort($currencies);

        return $currencies;
    }
}
