<?php

namespace DeliveryDash\Resources;

use DeliveryDash\Http\ApiClient;
use DeliveryDash\Exceptions\ApiException;

/**
 * Restaurant Resource for restaurant-specific operations
 */
class RestaurantResource
{
    private ApiClient $client;

    public function __construct(ApiClient $client)
    {
        $this->client = $client;
    }

    /**
     * Get restaurant profile information
     */
    public function getProfile(): array
    {
        return $this->client->get('/restaurant-profile');
    }

    /**
     * Update restaurant configuration
     */
    public function updateConfig(array $config): array
    {
        return $this->client->put('/restaurant-config', $config);
    }

    /**
     * Get restaurant statistics
     */
    public function getStats(array $filters = []): array
    {
        return $this->client->get('/restaurant-stats', $filters);
    }

    /**
     * Update restaurant operating hours
     */
    public function updateOperatingHours(array $hours): array
    {
        return $this->client->put('/restaurant-hours', [
            'operating_hours' => $hours
        ]);
    }

    /**
     * Toggle restaurant availability
     */
    public function setAvailability(bool $isAvailable): array
    {
        return $this->client->put('/restaurant-availability', [
            'is_delivery_available' => $isAvailable
        ]);
    }
}