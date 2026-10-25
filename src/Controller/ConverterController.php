<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\ExchangeRateService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

#[IsGranted('ROLE_USER')]
final class ConverterController extends AbstractController
{
    /**
     * Displays the currency conversion form.
     */
    #[Route('/', name: 'app_converter', methods: ['GET'])]
    public function index(
        ExchangeRateService $exchangeRateService,
    ): Response {
        return $this->renderConverter(
            $exchangeRateService,
            amount: '1',
            from: 'EUR',
            to: 'USD',
        );
    }

    /**
     * Validates the submitted form and performs the conversion.
     */
    #[Route('/', name: 'app_converter_submit', methods: ['POST'])]
    public function convert(
        Request $request,
        ExchangeRateService $exchangeRateService,
    ): Response {
        // Reject invalid CSRF tokens before any external API call.
        if (!$this->isCsrfTokenValid(
            'currency_conversion',
            $request->request->get('_token'),
        )) {
            throw $this->createAccessDeniedException(
                'Jeton CSRF invalide.',
            );
        }

        $amount = trim((string) $request->request->get('amount', ''));
        $from = strtoupper(trim(
            (string) $request->request->get('from', ''),
        ));
        $to = strtoupper(trim(
            (string) $request->request->get('to', ''),
        ));

        return $this->renderConverter(
            $exchangeRateService,
            amount: $amount,
            from: $from,
            to: $to,
            shouldConvert: true,
        );
    }

    /**
     * Builds the view data shared by GET and POST requests.
     *
     * @param string $amount User-provided decimal amount.
     */
    private function renderConverter(
        ExchangeRateService $exchangeRateService,
        string $amount,
        string $from,
        string $to,
        bool $shouldConvert = false,
    ): Response {
        $currencies = [];
        $error = null;
        $result = null;
        $rate = null;

        try {
            $currencies = $exchangeRateService->getCurrencies();
        } catch (
        TransportExceptionInterface |
        HttpExceptionInterface |
        DecodingExceptionInterface |
        \UnexpectedValueException
        ) {
            $error = 'Le service de devises est temporairement indisponible. Veuillez réessayer.';
        }

        if ($shouldConvert && $error === null) {
            if (!$this->isValidAmount($amount)) {
                $error = 'Veuillez saisir un montant valide supérieur à zéro.';
            } elseif (!isset($currencies[$from], $currencies[$to])) {
                $error = 'Veuillez sélectionner des devises valides.';
            } else {
                try {
                    $rate = $exchangeRateService->getRate($from, $to);
                    $result = (float) $amount * $rate;

                    if (!is_finite($result)) {
                        $result = null;
                        $rate = null;
                        $error = 'Le montant est trop élevé pour être converti.';
                    }
                } catch (
                TransportExceptionInterface |
                HttpExceptionInterface |
                DecodingExceptionInterface |
                \UnexpectedValueException
                ) {
                    $error = 'Impossible de récupérer le taux de change. Veuillez réessayer.';
                }
            }
        }

        return $this->render('converter/index.html.twig', [
            'currencies' => $currencies,
            'amount' => $amount,
            'from' => $from,
            'to' => $to,
            'result' => $result,
            'rate' => $rate,
            'error' => $error,
        ]);
    }

    /**
     * Accepts positive decimal amounts with up to two decimal places.
     */
    private function isValidAmount(string $amount): bool
    {
        if (!preg_match('/^\d+(?:\.\d{1,2})?$/', $amount)) {
            return false;
        }

        $value = (float) $amount;

        return is_finite($value) && $value > 0;
    }
}
