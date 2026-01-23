<?php

namespace App\Services;

/**
 * Paystack Service
 *
 * Handles Paystack payment processing
 */
class PaystackService
{
    private string $baseUrl = 'https://api.paystack.co';
    private string $apiKey;

    public function __construct()
    {
        $this->apiKey = env('PAYSTACK_SECRET_KEY');

        if (empty($this->apiKey)) {
            throw new \RuntimeException('Paystack secret key not configured');
        }
    }

    /**
     * Initialize a Paystack transaction
     */
    public function initializeTransaction(array $data): array
    {
        if (empty($data['email']) || empty($data['amount'])) {
            throw new \InvalidArgumentException('Email and amount are required');
        }

        $payload = [
            'email'        => $data['email'],
            'amount'       => (int) ($data['amount'] * 100), // kobo
            'reference'    => $data['reference'] ?? $this->generateReference(),
            'callback_url' => $data['callback_url']
                ?? env('PAYSTACK_CALLBACK_URL', 'http://localhost/payment/verify?method=paystack'),
            'metadata'     => [
                'cancel_url' => $data['cancel_url']
                    ?? env('PAYSTACK_CANCEL_URL', 'http://localhost/payment/cancel'),
            ],
        ];

        return $this->request(
            '/transaction/initialize',
            'POST',
            $payload
        );
    }

    /**
     * Verify a Paystack transaction
     */
    public function verifyTransaction(string $reference): array
    {
        if (empty($reference)) {
            throw new \InvalidArgumentException('Transaction reference is required');
        }

        return $this->request(
            '/transaction/verify/' . urlencode($reference),
            'GET'
        );
    }

    /**
     * Make HTTP request to Paystack
     */
    private function request(string $endpoint, string $method = 'GET', array $payload = []): array
    {
        $url = $this->baseUrl . $endpoint;

        $ch = curl_init($url);

        $headers = [
            'Authorization: Bearer ' . $this->apiKey,
            'Content-Type: application/json',
            'Accept: application/json',
        ];

        curl_setopt_array($ch, [
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        }

        $response = curl_exec($ch);

        if ($response === false) {
            throw new \RuntimeException('Paystack CURL error: ' . curl_error($ch));
        }

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $result = json_decode($response, true);

        if (!is_array($result)) {
            error_logger('Paystack error', 'Invalid JSON response from Paystack', ['response' => $response]);
            throw new \RuntimeException('Invalid Paystack response');
        }

        if ($httpCode >= 400 || ($result['status'] ?? false) !== true) {
            error_logger('Paystack error', $result['message'] ?? 'Unknown error', $result);
            throw new \RuntimeException(
                $result['message'] ?? 'Paystack request failed'
            );
        }

        return $result;
    }

    /**
     * Generate secure transaction reference
     */
    private function generateReference(): string
    {
        return 'paystack_' . bin2hex(random_bytes(8));
    }
}
