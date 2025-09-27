<?php

namespace DeliveryDash\Config;

use DeliveryDash\Exceptions\ConfigurationException;

/**
 * Configuration class for DeliveryDash SDK
 */
class Configuration
{
    private string $apiKey;
    private string $baseUrl;
    private int $timeout;
    private bool $debug;
    private array $defaultHeaders;

    public function __construct(string $apiKey, array $options = [])
    {
        if (empty($apiKey)) {
            throw new ConfigurationException('API key is required');
        }

        $this->apiKey = $apiKey;
        $this->baseUrl = $options['base_url'] ?? 'https://guqfrxvdxuabjjzzrnai.supabase.co/functions/v1';
        $this->timeout = $options['timeout'] ?? 30;
        $this->debug = $options['debug'] ?? false;
        
        $this->defaultHeaders = [
            'X-API-Key' => $this->apiKey,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'User-Agent' => 'DeliveryDash-Restaurant-SDK/1.0.0'
        ];

        if (isset($options['headers']) && is_array($options['headers'])) {
            $this->defaultHeaders = array_merge($this->defaultHeaders, $options['headers']);
        }
    }

    public function getApiKey(): string
    {
        return $this->apiKey;
    }

    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    public function getTimeout(): int
    {
        return $this->timeout;
    }

    public function isDebug(): bool
    {
        return $this->debug;
    }

    public function getDefaultHeaders(): array
    {
        return $this->defaultHeaders;
    }

    /**
     * Validate configuration
     */
    public function validate(): bool
    {
        if (empty($this->apiKey)) {
            throw new ConfigurationException('API key cannot be empty');
        }

        if (!filter_var($this->baseUrl, FILTER_VALIDATE_URL)) {
            throw new ConfigurationException('Base URL must be a valid URL');
        }

        if ($this->timeout <= 0) {
            throw new ConfigurationException('Timeout must be greater than 0');
        }

        return true;
    }
}