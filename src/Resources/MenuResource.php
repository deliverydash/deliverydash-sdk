<?php

namespace DeliveryDash\Resources;

use DeliveryDash\Http\ApiClient;
use DeliveryDash\Models\MenuItem;
use DeliveryDash\Models\MenuModifier;

/**
 * Menu resource for managing restaurant menus
 */
class MenuResource
{
    private ApiClient $client;

    public function __construct(ApiClient $client)
    {
        $this->client = $client;
    }

    /**
     * Sync menu items from local system to DeliveryDash
     */
    public function sync(array $menuItems): array
    {
        $formattedItems = array_map(function ($item) {
            return $this->formatMenuItem($item);
        }, $menuItems);

        return $this->client->post('/sync-menu', [
            'menu_items' => $formattedItems
        ]);
    }

    /**
     * Create a single menu item
     */
    public function createItem(array $itemData): array
    {
        $formattedItem = $this->formatMenuItem($itemData);
        return $this->client->post('/menu-items', $formattedItem);
    }

    /**
     * Update menu item
     */
    public function updateItem(string $itemId, array $itemData): array
    {
        $formattedItem = $this->formatMenuItem($itemData);
        return $this->client->put("/menu-items/{$itemId}", $formattedItem);
    }

    /**
     * Delete menu item
     */
    public function deleteItem(string $itemId): array
    {
        return $this->client->delete("/menu-items/{$itemId}");
    }

    /**
     * Get all menu items for restaurant
     */
    public function getItems(string $restaurantId): array
    {
        return $this->client->get('/menu-items', ['restaurant_id' => $restaurantId]);
    }

    /**
     * Update item availability
     */
    public function updateAvailability(string $itemId, bool $available): array
    {
        return $this->client->put("/menu-items/{$itemId}/availability", [
            'is_available' => $available
        ]);
    }

    /**
     * Bulk update availability
     */
    public function bulkUpdateAvailability(array $updates): array
    {
        return $this->client->post('/menu-items/bulk-availability', [
            'updates' => $updates
        ]);
    }

    /**
     * Add modifiers to menu item
     */
    public function addModifiers(string $itemId, array $modifiers): array
    {
        $formattedModifiers = array_map(function ($modifier) {
            return $this->formatModifier($modifier);
        }, $modifiers);

        return $this->client->post("/menu-items/{$itemId}/modifiers", [
            'modifiers' => $formattedModifiers
        ]);
    }

    /**
     * Format menu item for API - maps to restaurant_menu_items table
     */
    private function formatMenuItem(array $item): array
    {
        $formatted = [
            'name' => $item['name'] ?? '',
            'description' => $item['description'] ?? '',
            'price' => (float) ($item['price'] ?? 0),
            'category' => $item['category'] ?? 'main',
            'is_available' => $item['available'] ?? $item['is_available'] ?? true,
            'image_url' => $item['image_url'] ?? $item['image'] ?? null,
            'preparation_time_minutes' => $item['preparation_time_minutes'] ?? $item['prep_time'] ?? 15,
            'ingredients' => $item['ingredients'] ?? null,
            'allergens' => $item['allergens'] ?? null,
            'dietary_info' => $item['dietary_info'] ?? null,
        ];

        // Handle modifiers
        if (isset($item['modifiers']) && is_array($item['modifiers'])) {
            $formatted['modifiers'] = array_map(function ($modifier) {
                return $this->formatModifier($modifier);
            }, $item['modifiers']);
        }

        // Handle external ID mapping for your system
        if (isset($item['id']) || isset($item['external_id'])) {
            $formatted['external_id'] = $item['id'] ?? $item['external_id'];
        }

        return $formatted;
    }

    /**
     * Format modifier for API - maps to restaurant_menu_modifiers table
     */
    private function formatModifier(array $modifier): array
    {
        return [
            'name' => $modifier['name'] ?? '',
            'description' => $modifier['description'] ?? null,
            'price' => (float) ($modifier['price'] ?? 0),
            'is_required' => $modifier['required'] ?? $modifier['is_required'] ?? false,
            'modifier_type' => $modifier['type'] ?? $modifier['modifier_type'] ?? 'addon',
            'display_order' => $modifier['display_order'] ?? $modifier['order'] ?? 0,
            'is_available' => $modifier['available'] ?? $modifier['is_available'] ?? true,
            'external_id' => $modifier['id'] ?? $modifier['external_id'] ?? null,
        ];
    }
}