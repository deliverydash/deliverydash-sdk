<?php

namespace DeliveryDash\Resources;

use DeliveryDash\Http\ApiClient;

/**
 * Order resource for managing orders from WhatsApp
 */
class OrderResource
{
    private ApiClient $client;

    public function __construct(ApiClient $client)
    {
        $this->client = $client;
    }

    /**
     * Get new orders from WhatsApp
     */
    public function getNew(): array
    {
        return $this->client->get('/orders/new');
    }

    /**
     * Get all orders with optional filters
     */
    public function getAll(array $filters = []): array
    {
        return $this->client->get('/orders', $filters);
    }

    /**
     * Get specific order by ID
     */
    public function get(string $orderId): array
    {
        return $this->client->get("/orders/{$orderId}");
    }

    /**
     * Update order status
     */
    public function updateStatus(string $orderId, string $status, string $notes = ''): array
    {
        return $this->client->put("/orders/{$orderId}/status", [
            'status' => $status,
            'notes' => $notes
        ]);
    }

    /**
     * Confirm order (restaurant accepted)
     */
    public function confirm(string $orderId, array $details = []): array
    {
        return $this->updateStatus($orderId, 'confirmed', $details['notes'] ?? 'Order confirmed by restaurant');
    }

    /**
     * Mark order as preparing
     */
    public function preparing(string $orderId, int $estimatedMinutes = null): array
    {
        $notes = $estimatedMinutes ? "Order is being prepared. Estimated time: {$estimatedMinutes} minutes" : 'Order is being prepared';
        return $this->updateStatus($orderId, 'preparing', $notes);
    }

    /**
     * Mark order as ready for pickup
     */
    public function readyForPickup(string $orderId): array
    {
        return $this->updateStatus($orderId, 'ready_for_pickup', 'Order is ready for pickup');
    }

    /**
     * Mark order as out for delivery
     */
    public function outForDelivery(string $orderId, array $driverDetails = []): array
    {
        $data = [
            'status' => 'out_for_delivery',
            'notes' => 'Order is out for delivery'
        ];

        if (!empty($driverDetails)) {
            $data['driver_details'] = $driverDetails;
        }

        return $this->client->put("/orders/{$orderId}/status", $data);
    }

    /**
     * Mark order as delivered
     */
    public function delivered(string $orderId, array $deliveryDetails = []): array
    {
        $data = [
            'status' => 'delivered',
            'notes' => 'Order has been delivered'
        ];

        if (!empty($deliveryDetails)) {
            $data['delivery_details'] = $deliveryDetails;
        }

        return $this->client->put("/orders/{$orderId}/status", $data);
    }

    /**
     * Cancel order
     */
    public function cancel(string $orderId, string $reason = ''): array
    {
        return $this->updateStatus($orderId, 'cancelled', $reason ?: 'Order cancelled by restaurant');
    }

    /**
     * Send custom message to customer via WhatsApp
     */
    public function sendMessage(string $orderId, string $message): array
    {
        return $this->client->post("/orders/{$orderId}/message", [
            'message' => $message
        ]);
    }

    /**
     * Get order statistics
     */
    public function getStats(array $filters = []): array
    {
        return $this->client->get('/orders/stats', $filters);
    }

    /**
     * Convert order to local format
     */
    public function toLocalFormat(array $order): array
    {
        return [
            'external_order_id' => $order['id'],
            'customer_name' => $order['customer_name'],
            'customer_phone' => $order['customer_phone'],
            'items' => $this->formatOrderItems($order['items'] ?? []),
            'subtotal' => $order['subtotal'] ?? 0,
            'tax_amount' => $order['tax_amount'] ?? 0,
            'delivery_fee' => $order['delivery_fee'] ?? 0,
            'total_amount' => $order['total_amount'] ?? 0,
            'delivery_address' => $order['delivery_address'] ?? '',
            'special_instructions' => $order['special_instructions'] ?? '',
            'payment_method' => $order['payment_method'] ?? 'cash_on_delivery',
            'order_source' => 'whatsapp',
            'status' => 'pending',
            'created_at' => $order['created_at'] ?? now()
        ];
    }

    /**
     * Format order items for local system
     */
    private function formatOrderItems(array $items): array
    {
        return array_map(function ($item) {
            return [
                'external_item_id' => $item['item_id'] ?? null,
                'item_name' => $item['item_name'],
                'price' => $item['item_price'],
                'quantity' => $item['quantity'],
                'modifiers' => $item['modifiers'] ?? [],
                'total_price' => $item['total_price'] ?? ($item['item_price'] * $item['quantity'])
            ];
        }, $items);
    }
}