<?php

namespace DevJax\Pesapal;

/**
 * Pesapal API 3.0 SDK
 *
 * @file     Pesapal/Pesapal.php
 * @author   DevJax
 * @license  MIT
 * @version  1.0.0
 */
class Pesapal {
    protected string $consumerKey;
    protected string $consumerSecret;
    protected string $baseURL;
    protected ?string $accessToken = null;

    /**
     * Constructor
     * @param string $consumerKey
     * @param string $consumerSecret
     * @param bool $isLive - Set to true for live environment
     */
    public function __construct(string $consumerKey, string $consumerSecret, bool $isLive = false) {
        $this->consumerKey = $consumerKey;
        $this->consumerSecret = $consumerSecret;
        $this->baseURL = $isLive ? 'https://pay.pesapal.com/v3' : 'https://cybqa.pesapal.com/pesapalv3';
    }

    /**
     * Authenticates with Pesapal to get an access token.
     * @return string The access token.
     * @throws PesapalException
     */
    protected function authenticate(): string {
        $payload = [
            'consumer_key' => $this->consumerKey,
            'consumer_secret' => $this->consumerSecret,
        ];

        $response = $this->makeRequest('/api/Auth/RequestToken', 'POST', $payload);

        if (isset($response['token'])) {
            $this->accessToken = $response['token'];
            return $this->accessToken;
        }

        throw new PesapalException('Authentication failed: ' . ($response['error'] ?? 'Unknown error'));
    }

    /**
     * Gets a valid access token, authenticating if necessary.
     * @return string
     * @throws PesapalException
     */
    protected function getAccessToken(): string {
        if ($this->accessToken === null) {
            return $this->authenticate();
        }
        return $this->accessToken;
    }

    /**
     * Registers an IPN (Instant Payment Notification) URL.
     * @param string $ipnUrl
     * @return array The response from Pesapal.
     * @throws PesapalException
     */
    public function registerIPN(string $ipnUrl): array {
        $payload = [
            'url' => $ipnUrl,
            'ipn_notification_type' => 'GET',
        ];
        return $this->makeRequest('/api/URLSetup/RegisterIPN', 'POST', $payload);
    }

    /**
     * Submits a payment order request.
     * @param array $orderDetails
     * @return array The response from Pesapal.
     * @throws PesapalException
     */
    public function submitOrder(array $orderDetails): array {
        return $this->makeRequest('/api/Transactions/SubmitOrderRequest', 'POST', $orderDetails);
    }

    /**
     * Gets the status of a transaction.
     * @param string $orderTrackingId
     * @return array The response from Pesapal.
     * @throws PesapalException
     */
    public function getTransactionStatus(string $orderTrackingId): array {
        return $this->makeRequest("/api/Transactions/GetTransactionStatus?orderTrackingId={$orderTrackingId}", 'GET');
    }

    /**
     * Makes an HTTP request to the Pesapal API.
     * @param string $endpoint
     * @param string $method
     * @param array|null $data
     * @return array The decoded JSON response.
     * @throws PesapalException
     */
    protected function makeRequest(string $endpoint, string $method, ?array $data = null): array {
        $url = $this->baseURL . $endpoint;
        $headers = [
            'Content-Type: application/json',
            'Accept: application/json',
        ];

        if ($endpoint !== '/api/Auth/RequestToken') {
            $headers[] = 'Authorization: Bearer ' . $this->getAccessToken();
        }

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($data) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            throw new PesapalException("cURL Error: " . $curlError);
        }
        
        $decodedResponse = json_decode($response, true);

        if ($httpCode >= 400 || (isset($decodedResponse['status']) && $decodedResponse['status'] !== '200' && $decodedResponse['status'] !== '0')) {
             throw new PesapalException(
                "API Error: " . ($decodedResponse['error']['message'] ?? $decodedResponse['error'] ?? 'Request failed'),
                $httpCode
            );
        }
        
        return $decodedResponse;
    }
}
