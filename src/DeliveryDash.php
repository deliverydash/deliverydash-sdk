<?php

namespace DeliveryDash;

use DeliveryDash\Config\Configuration;
use DeliveryDash\Resources\MenuResource;
use DeliveryDash\Resources\OrderResource;
use DeliveryDash\Resources\WebhookResource;
use DeliveryDash\Resources\RestaurantResource;
use DeliveryDash\Http\ApiClient;

/**
 * DeliveryDash PHP SDK
 * 
 * Main entry point for the DeliveryDash API integration
 */
class DeliveryDash
{
    private Configuration $config;
    private ApiClient $client;
    private MenuResource $menus;
    private OrderResource $orders;
    private WebhookResource $webhooks;
    private RestaurantResource $restaurant;

    public function __construct(string $apiKey, array $options = [])
    {
        $this->config = new Configuration($apiKey, $options);
        $this->client = new ApiClient($this->config);
        
        $this->menus = new MenuResource($this->client);
        $this->orders = new OrderResource($this->client);
        $this->webhooks = new WebhookResource($this->client);
        $this->restaurant = new RestaurantResource($this->client);
    }

    /**
     * Get menu resource for syncing menus and items
     */
    public function menus(): MenuResource
    {
        return $this->menus;
    }

    /**
     * Get order resource for managing orders
     */
    public function orders(): OrderResource
    {
        return $this->orders;
    }

    /**
     * Get webhook resource for handling webhooks
     */
    public function webhooks(): WebhookResource
    {
        return $this->webhooks;
    }

    /**
     * Get restaurant resource for restaurant management
     */
    public function restaurant(): RestaurantResource
    {
        return $this->restaurant;
    }

    /**
     * Test API connection
     */
    public function testConnection(): bool
    {
        try {
            $response = $this->client->get('/health');
            return $response['status'] === 'ok';
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get current API configuration
     */
    public function getConfig(): Configuration
    {
        return $this->config;
    }
}