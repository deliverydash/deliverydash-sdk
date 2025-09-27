<?php

namespace DeliveryDash\Resources;

use DeliveryDash\Http\ApiClient;

/**
 * Webhook resource for handling DeliveryDash webhooks
 */
class WebhookResource
{
    private ApiClient $client;

    public function __construct(ApiClient $client)
    {
        $this->client = $client;
    }

    /**
     * Register webhook endpoint
     */
    public function register(string $url, array $events = ['order.created', 'order.updated']): array
    {
        return $this->client->post('/webhooks', [
            'url' => $url,
            'events' => $events,
            'active' => true
        ]);
    }

    /**
     * Update webhook configuration
     */
    public function update(string $webhookId, array $data): array
    {
        return $this->client->put("/webhooks/{$webhookId}", $data);
    }

    /**
     * Delete webhook
     */
    public function delete(string $webhookId): array
    {
        return $this->client->delete("/webhooks/{$webhookId}");
    }

    /**
     * List all webhooks
     */
    public function list(): array
    {
        return $this->client->get('/webhooks');
    }

    /**
     * Test webhook endpoint
     */
    public function test(string $webhookId): array
    {
        return $this->client->post("/webhooks/{$webhookId}/test");
    }

    /**
     * Verify webhook signature
     */
    public function verifySignature(string $payload, string $signature, string $secret): bool
    {
        $expectedSignature = hash_hmac('sha256', $payload, $secret);
        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Parse webhook payload
     */
    public function parsePayload(string $payload): array
    {
        $data = json_decode($payload, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \InvalidArgumentException('Invalid JSON payload');
        }

        return $data;
    }

    /**
     * Handle incoming webhook
     */
    public function handle(string $payload, string $signature = null, string $secret = null): array
    {
        // Verify signature if provided
        if ($signature && $secret) {
            if (!$this->verifySignature($payload, $signature, $secret)) {
                throw new \InvalidArgumentException('Invalid webhook signature');
            }
        }

        $data = $this->parsePayload($payload);
        
        // Process based on event type
        return $this->processWebhookEvent($data);
    }

    /**
     * Process webhook event
     */
    private function processWebhookEvent(array $data): array
    {
        $eventType = $data['event_type'] ?? $data['type'] ?? 'unknown';
        $eventData = $data['data'] ?? $data;

        return [
            'event_type' => $eventType,
            'data' => $eventData,
            'timestamp' => $data['timestamp'] ?? time(),
            'processed_at' => time()
        ];
    }
}