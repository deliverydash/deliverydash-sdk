<?php

namespace DeliveryDash\Http;

use DeliveryDash\Config\Configuration;
use DeliveryDash\Exceptions\ApiException;
use DeliveryDash\Exceptions\AuthenticationException;
use DeliveryDash\Exceptions\RateLimitException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\ResponseInterface;

/**
 * HTTP client for DeliveryDash API
 */
class ApiClient
{
    private Configuration $config;
    private Client $httpClient;

    public function __construct(Configuration $config)
    {
        $this->config = $config;
        $this->httpClient = new Client([
            'base_uri' => $config->getBaseUrl(),
            'timeout' => $config->getTimeout(),
            'headers' => $config->getDefaultHeaders()
        ]);
    }

    /**
     * Make GET request
     */
    public function get(string $endpoint, array $params = []): array
    {
        return $this->makeRequest('GET', $endpoint, ['query' => $params]);
    }

    /**
     * Make POST request
     */
    public function post(string $endpoint, array $data = []): array
    {
        return $this->makeRequest('POST', $endpoint, ['json' => $data]);
    }

    /**
     * Make PUT request
     */
    public function put(string $endpoint, array $data = []): array
    {
        return $this->makeRequest('PUT', $endpoint, ['json' => $data]);
    }

    /**
     * Make DELETE request
     */
    public function delete(string $endpoint): array
    {
        return $this->makeRequest('DELETE', $endpoint);
    }

    /**
     * Make HTTP request
     */
    private function makeRequest(string $method, string $endpoint, array $options = []): array
    {
        try {
            $response = $this->httpClient->request($method, $endpoint, $options);
            return $this->handleResponse($response);
        } catch (RequestException $e) {
            $this->handleRequestException($e);
        }
    }

    /**
     * Handle HTTP response
     */
    private function handleResponse(ResponseInterface $response): array
    {
        $statusCode = $response->getStatusCode();
        $body = $response->getBody()->getContents();
        
        if ($this->config->isDebug()) {
            error_log("DeliveryDash API Response: {$statusCode} - {$body}");
        }

        $data = json_decode($body, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new ApiException('Invalid JSON response from API');
        }

        if ($statusCode >= 400) {
            $this->handleErrorResponse($statusCode, $data);
        }

        return $data;
    }

    /**
     * Handle request exception
     */
    private function handleRequestException(RequestException $e): void
    {
        if ($e->hasResponse()) {
            $response = $e->getResponse();
            $statusCode = $response->getStatusCode();
            $body = json_decode($response->getBody()->getContents(), true);
            $this->handleErrorResponse($statusCode, $body);
        } else {
            throw new ApiException('Network error: ' . $e->getMessage());
        }
    }

    /**
     * Handle error response
     */
    private function handleErrorResponse(int $statusCode, ?array $data): void
    {
        $message = $data['message'] ?? $data['error'] ?? 'Unknown API error';
        
        switch ($statusCode) {
            case 401:
                throw new AuthenticationException($message);
            case 429:
                throw new RateLimitException($message);
            default:
                throw new ApiException($message, $statusCode);
        }
    }
}