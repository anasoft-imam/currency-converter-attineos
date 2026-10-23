<?php

namespace App\Controller;

use App\Service\ExchangeRateService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
final class ConverterController extends AbstractController
{
    #[Route('/', name: 'app_converter', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        ExchangeRateService $exchangeRateService,
    ): Response {
        $error = null;

        try {
            $currencies = $exchangeRateService->getCurrencies();
        } catch (\Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface | \Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface $exception) {
            $currencies = [];
            $error = 'Le service de devises est temporairement indisponible. Veuillez réessayer.';
        }

        $amount = '1';
        $from = 'EUR';
        $to = 'USD';
        $result = null;
        $rate = null;

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid(
                'currency_conversion',
                $request->request->get('_token')
            )) {
                throw $this->createAccessDeniedException('Jeton CSRF invalide.');
            }

            $amount = trim((string) $request->request->get('amount', ''));
            $from = strtoupper(trim((string) $request->request->get('from', '')));
            $to = strtoupper(trim((string) $request->request->get('to', '')));

            if (!is_numeric($amount) || (float) $amount <= 0) {
                $error = 'Veuillez saisir un montant supérieur à zéro.';
            } elseif (!isset($currencies[$from], $currencies[$to])) {
                $error = 'Veuillez sélectionner des devises valides.';
            } else {
                try {
                    $rate = $exchangeRateService->getRate($from, $to);
                    $result = (float) $amount * $rate;
                } catch (\Throwable) {
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
}
