<?php

namespace DevJax\Pesapal;

/**
 * Pesapal API 3.0 SDK
 *
 * @file     Pesapal/Pesapal.php
 * @author   DevJax
 * @license  MIT
 * @version  1.0.2
 */
class Pesapal {
    protected string $consumerKey;
    protected string $consumerSecret;
    protected string $baseURL;
    protected ?string $accessToken = null;

    /**
     * Writes logs to the standard error output, which is captured by Railway.
     * @param string $message The message to log.
     */
    protected function log(string $message): void {
        $timestamp = date('Y-m-d H:i:s');
        // Use php://stderr to send logs to Railway's log viewer
        file_put_contents('php://stderr', "[$timestamp] [Pesapal-SDK] " . $message . "\n");
    }

    public function __construct(string $consumerKey, string $consumerSecret, bool $isLive = false) {
        $this->consumerKey = $consumerKey;
        $this->consumerSecret = $consumerSecret;
        $this->baseURL = $isLive ? 'https://pay.pesapal.com/v3' : 'https://cybqa.pesapal.com/pesapalv3';
        $this->log("SDK Initialized. Environment: " . ($isLive ? 'LIVE' : 'SANDBOX'));
    }

    protected function authenticate(): string {
        $this->log("Attempting to authenticate...");
        $payload = [
            'consumer_key' => $this->consumerKey,
            'consumer_secret' => $this->consumerSecret,
        ];

        try {
            $response = $this->makeRequest('/api/Auth/RequestToken', 'POST', $payload);
            if (isset($response['token'])) {
                $this->accessToken = $response['token'];
                $this->log("Authentication successful. Token received.");
                return $this->accessToken;
            }
            throw new PesapalException('Authentication failed: ' . ($response['error'] ?? 'Unknown error'));
        } catch (PesapalException $e) {
            $this->log("Authentication FAILED. Reason: " . $e->getMessage());
            throw $e;
        }
    }

    protected function getAccessToken(): string {
        if ($this->accessToken === null) {
            $this->log("Access token is null. A new one will be requested.");
            return $this->authenticate();
        }
        $this->log("Using existing access token.");
        return $this->accessToken;
    }

    public function registerIPN(string $ipnUrl): array {
        $this->log("Attempting to register IPN URL: {$ipnUrl}");
        $payload = [
            'url' => $ipnUrl,
            'ipn_notification_type' => 'GET',
        ];
        try {
            $response = $this->makeRequest('/api/URLSetup/RegisterIPN', 'POST', $payload);
            $this->log("IPN Registration successful. Response: " . json_encode($response));
            return $response;
        } catch (PesapalException $e) {
            $this->log("IPN Registration FAILED. Reason: " . $e->getMessage());
            throw $e;
        }
    }

    public function submitOrder(array $orderDetails): array {
        $this->log("Attempting to submit order. Merchant Reference: {$orderDetails['id']}");
        try {
            $response = $this->makeRequest('/api/Transactions/SubmitOrderRequest', 'POST', $orderDetails);
            $this->log("Order submission successful. Response: " . json_encode($response));
            return $response;
        } catch (PesapalException $e) {
            $this->log("Order submission FAILED. Reason: " . $e->getMessage());
            throw $e;
        }
    }

    public function getTransactionStatus(string $orderTrackingId): array {
        $this->log("Attempting to get transaction status for OrderTrackingId: {$orderTrackingId}");
        try {
            $response = $this->makeRequest("/api/Transactions/GetTransactionStatus?orderTrackingId={$orderTrackingId}", 'GET');
            $this->log("Get transaction status successful. Response: " . json_encode($response));
            return $response;
        } catch (PesapalException $e) {
            $this->log("Get transaction status FAILED. Reason: " . $e->getMessage());
            throw $e;
        }
    }

    protected function makeRequest(string $endpoint, string $method, ?array $data = null): array {
        $url = $this->baseURL . $endpoint;
        $this->log("Making API request. Method: $method, URL: $url");
        if ($data) {
            $this->log("Request Payload: " . json_encode($data));
        }

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
            $this->log("cURL Error: " . $curlError);
            throw new PesapalException("cURL Error: " . $curlError);
        }
        
        $this->log("API Response Received. HTTP Code: $httpCode, Body: $response");
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
