# DeliveryDash PHP SDK

Official PHP SDK for integrating with DeliveryDash API to enable WhatsApp ordering and menu synchronization.

## Installation

```bash
composer require deliverydash/php-sdk
```

## Quick Start

```php
<?php
require_once 'vendor/autoload.php';

use DeliveryDash\DeliveryDash;

// Initialize SDK with your Restaurant API Key
$deliveryDash = new DeliveryDash('your-restaurant-api-key');

// Sync your menu
$menuItems = [
    [
        'name' => 'Margherita Pizza',
        'description' => 'Fresh tomato, mozzarella, basil',
        'price' => 120.00,
        'category' => 'Pizza',
        'available' => true,
        'modifiers' => [
            ['name' => 'Extra Cheese', 'price' => 15.00],
            ['name' => 'Large Size', 'price' => 25.00]
        ]
    ]
];

$response = $deliveryDash->menus()->sync($menuItems);

// Get new orders from WhatsApp
$orders = $deliveryDash->orders()->getNew();
foreach($orders as $order) {
    // Process order in your system
    echo "New order: " . $order['id'] . "\n";
    
    // Update order status
    $deliveryDash->orders()->confirm($order['id']);
}
```

## Configuration

```php
$deliveryDash = new DeliveryDash('your-restaurant-api-key', [
    'base_url' => 'https://guqfrxvdxuabjjzzrnai.supabase.co/functions/v1',
    'timeout' => 30,
    'debug' => true,
    'headers' => [
        'X-Restaurant-ID' => 'your-restaurant-id'
    ]
]);
```

### Getting Your API Key

1. Log into the DeliveryDash admin panel
2. Go to **Restaurant Management**
3. Select your restaurant
4. Navigate to the **"API Keys"** tab
5. Click **"Generate New API Key"**
6. Set the required permissions:
   - ✅ `menu_sync`: For syncing menu items
   - ✅ `orders`: For receiving and managing orders
   - ✅ `read/write`: For general operations
7. Copy the generated API key and store it securely in your `.env` file

**Important:** Store your API key in your `.env` file as:
```bash
DELIVERYDASH_API_KEY=your_generated_api_key_here
```

## Menu Management

### Sync Menu Items

```php
// Sync entire menu
$response = $deliveryDash->menus()->sync($menuItems);

// Create single item
$item = $deliveryDash->menus()->createItem([
    'name' => 'New Pizza',
    'price' => 100.00,
    'category' => 'Pizza'
]);

// Update availability
$deliveryDash->menus()->updateAvailability('item-id', false);

// Bulk update availability
$deliveryDash->menus()->bulkUpdateAvailability([
    ['id' => 'item-1', 'available' => false],
    ['id' => 'item-2', 'available' => true]
]);
```

### Menu Item Format

```php
$menuItem = [
    'name' => 'Item Name',           // Required
    'description' => 'Description',  // Optional
    'price' => 99.99,               // Required (float)
    'category' => 'Category',       // Optional
    'available' => true,            // Optional (default: true)
    'image_url' => 'https://...',   // Optional
    'external_id' => 'your-id',     // Optional - your system's ID
    'modifiers' => [                // Optional
        [
            'name' => 'Modifier Name',
            'price' => 10.00,
            'required' => false,
            'type' => 'addon'
        ]
    ]
];
```

## Order Management

### Get Orders

```php
// Get new orders
$newOrders = $deliveryDash->orders()->getNew();

// Get all orders with filters
$orders = $deliveryDash->orders()->getAll([
    'status' => 'pending',
    'date_from' => '2024-01-01',
    'date_to' => '2024-01-31'
]);

// Get specific order
$order = $deliveryDash->orders()->get('order-id');
```

### Update Order Status

```php
// Confirm order
$deliveryDash->orders()->confirm('order-id');

// Mark as preparing
$deliveryDash->orders()->preparing('order-id', 30); // 30 minutes

// Ready for pickup
$deliveryDash->orders()->readyForPickup('order-id');

// Out for delivery
$deliveryDash->orders()->outForDelivery('order-id', [
    'driver_name' => 'John Doe',
    'driver_phone' => '+27123456789'
]);

// Delivered
$deliveryDash->orders()->delivered('order-id');

// Cancel order
$deliveryDash->orders()->cancel('order-id', 'Out of stock');
```

### Send Custom Messages

```php
// Send message to customer via WhatsApp
$deliveryDash->orders()->sendMessage('order-id', 'Your order will be ready in 10 minutes!');
```

### Convert to Local Format

```php
$orders = $deliveryDash->orders()->getNew();
foreach($orders as $order) {
    $localOrder = $deliveryDash->orders()->toLocalFormat($order);
    
    // Create in your local database
    $localOrderId = YourOrderModel::create($localOrder);
}
```

## Webhook Management

### Register Webhook

```php
$webhook = $deliveryDash->webhooks()->register('https://yoursite.com/webhook', [
    'order.created',
    'order.updated',
    'order.cancelled'
]);
```

### Handle Webhook

```php
// In your webhook endpoint
$payload = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_DELIVERYDASH_SIGNATURE'] ?? '';

try {
    $event = $deliveryDash->webhooks()->handle($payload, $signature, 'your-webhook-secret');
    
    switch($event['event_type']) {
        case 'order.created':
            handleNewOrder($event['data']);
            break;
        case 'order.updated':
            handleOrderUpdate($event['data']);
            break;
    }
    
    http_response_code(200);
    echo 'OK';
} catch (Exception $e) {
    http_response_code(400);
    echo 'Error: ' . $e->getMessage();
}
```

## Error Handling

```php
use DeliveryDash\Exceptions\ApiException;
use DeliveryDash\Exceptions\AuthenticationException;
use DeliveryDash\Exceptions\RateLimitException;

try {
    $response = $deliveryDash->menus()->sync($menuItems);
} catch (AuthenticationException $e) {
    echo 'Authentication failed: ' . $e->getMessage();
} catch (RateLimitException $e) {
    echo 'Rate limit exceeded: ' . $e->getMessage();
} catch (ApiException $e) {
    echo 'API error: ' . $e->getMessage();
}
```

## Testing Connection

```php
if ($deliveryDash->testConnection()) {
    echo 'Connection successful!';
} else {
    echo 'Connection failed!';
}
```

## Requirements

- PHP 7.4 or higher
- Guzzle HTTP client
- JSON extension

## Framework Integration Examples

### Laravel Integration

#### 1. Install and Configure

```bash
composer require deliverydash/php-sdk
```

Add to your `.env`:
```bash
DELIVERYDASH_API_KEY=your_restaurant_api_key_here
DELIVERYDASH_DEBUG=false
```

#### 2. Create a Service Provider

```php
<?php
// app/Providers/DeliveryDashServiceProvider.php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use DeliveryDash\DeliveryDash;

class DeliveryDashServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton(DeliveryDash::class, function ($app) {
            return new DeliveryDash(config('services.deliverydash.api_key'), [
                'debug' => config('services.deliverydash.debug', false),
                'timeout' => 60
            ]);
        });
    }
}
```

Add to `config/services.php`:
```php
'deliverydash' => [
    'api_key' => env('DELIVERYDASH_API_KEY'),
    'debug' => env('DELIVERYDASH_DEBUG', false),
],
```

Register the provider in `config/app.php`:
```php
'providers' => [
    // ...
    App\Providers\DeliveryDashServiceProvider::class,
],
```

#### 3. Create a Controller

```php
<?php
// app/Http/Controllers/MenuController.php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DeliveryDash\DeliveryDash;
use App\Models\MenuItem;

class MenuController extends Controller
{
    protected $deliveryDash;

    public function __construct(DeliveryDash $deliveryDash)
    {
        $this->deliveryDash = $deliveryDash;
    }

    public function syncMenu()
    {
        $menuItems = MenuItem::where('is_active', true)->get()->map(function ($item) {
            return [
                'external_id' => $item->id,
                'name' => $item->name,
                'description' => $item->description,
                'price' => $item->price,
                'category' => $item->category,
                'available' => $item->is_available,
                'image_url' => $item->image_url,
                'preparation_time' => $item->prep_time_minutes,
            ];
        })->toArray();

        try {
            $response = $this->deliveryDash->menus()->sync($menuItems);
            return response()->json(['success' => true, 'synced' => count($menuItems)]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function getOrders()
    {
        try {
            $orders = $this->deliveryDash->orders()->getNew();
            
            foreach ($orders as $order) {
                // Create local order record
                $localOrder = \App\Models\Order::create([
                    'deliverydash_id' => $order['id'],
                    'customer_name' => $order['customer_name'],
                    'customer_phone' => $order['customer_phone'],
                    'items' => json_encode($order['items']),
                    'total_amount' => $order['total_amount'],
                    'status' => 'received'
                ]);

                // Confirm with DeliveryDash
                $this->deliveryDash->orders()->confirm($order['id']);
            }

            return response()->json(['orders_processed' => count($orders)]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
```

#### 4. Webhook Handler

```php
<?php
// app/Http/Controllers/WebhookController.php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DeliveryDash\DeliveryDash;

class WebhookController extends Controller
{
    public function handle(Request $request, DeliveryDash $deliveryDash)
    {
        $payload = $request->getContent();
        $signature = $request->header('X-DeliveryDash-Signature');

        try {
            $event = $deliveryDash->webhooks()->handle(
                $payload, 
                $signature, 
                config('services.deliverydash.webhook_secret')
            );

            switch ($event['event_type']) {
                case 'order.created':
                    $this->handleNewOrder($event['data']);
                    break;
                case 'order.updated':
                    $this->handleOrderUpdate($event['data']);
                    break;
            }

            return response('OK', 200);
        } catch (\Exception $e) {
            \Log::error('DeliveryDash webhook error: ' . $e->getMessage());
            return response('Error', 400);
        }
    }

    private function handleNewOrder($orderData)
    {
        // Handle new order logic
        \Log::info('New DeliveryDash order received', $orderData);
    }

    private function handleOrderUpdate($orderData)
    {
        // Handle order update logic
        \Log::info('DeliveryDash order updated', $orderData);
    }
}
```

#### 5. Artisan Commands

```php
<?php
// app/Console/Commands/SyncMenuCommand.php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use DeliveryDash\DeliveryDash;

class SyncMenuCommand extends Command
{
    protected $signature = 'deliverydash:sync-menu';
    protected $description = 'Sync menu items with DeliveryDash';

    public function handle(DeliveryDash $deliveryDash)
    {
        $this->info('Syncing menu with DeliveryDash...');
        
        // Your menu sync logic here
        
        $this->info('Menu sync completed!');
    }
}
```

### CodeIgniter Integration

#### 1. Install SDK

```bash
composer require deliverydash/php-sdk
```

#### 2. Environment Configuration

Add to your `.env`:
```bash
DELIVERYDASH_API_KEY=your_restaurant_api_key_here
```

#### 3. Create a Library

```php
<?php
// application/libraries/Deliverydash_lib.php

defined('BASEPATH') OR exit('No direct script access allowed');

require_once FCPATH . 'vendor/autoload.php';

use DeliveryDash\DeliveryDash;

class Deliverydash_lib
{
    private $deliveryDash;
    private $CI;

    public function __construct()
    {
        $this->CI =& get_instance();
        $this->CI->load->library('session');
        
        $apiKey = getenv('DELIVERYDASH_API_KEY');
        if (!$apiKey) {
            show_error('DeliveryDash API key not configured');
        }

        $this->deliveryDash = new DeliveryDash($apiKey, [
            'debug' => ENVIRONMENT === 'development'
        ]);
    }

    public function syncMenu($menuItems)
    {
        try {
            return $this->deliveryDash->menus()->sync($menuItems);
        } catch (Exception $e) {
            log_message('error', 'DeliveryDash menu sync failed: ' . $e->getMessage());
            throw $e;
        }
    }

    public function getNewOrders()
    {
        try {
            return $this->deliveryDash->orders()->getNew();
        } catch (Exception $e) {
            log_message('error', 'DeliveryDash get orders failed: ' . $e->getMessage());
            throw $e;
        }
    }

    public function confirmOrder($orderId)
    {
        try {
            return $this->deliveryDash->orders()->confirm($orderId);
        } catch (Exception $e) {
            log_message('error', 'DeliveryDash confirm order failed: ' . $e->getMessage());
            throw $e;
        }
    }

    public function updateOrderStatus($orderId, $status, $data = [])
    {
        try {
            return $this->deliveryDash->orders()->updateStatus($orderId, $status, $data);
        } catch (Exception $e) {
            log_message('error', 'DeliveryDash update status failed: ' . $e->getMessage());
            throw $e;
        }
    }
}
```

#### 4. Create Controllers

```php
<?php
// application/controllers/Menu.php

defined('BASEPATH') OR exit('No direct script access allowed');

class Menu extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->library('deliverydash_lib');
        $this->load->model('menu_model');
    }

    public function sync()
    {
        try {
            $menuItems = $this->menu_model->get_active_items();
            
            $formattedItems = array_map(function($item) {
                return [
                    'external_id' => $item['id'],
                    'name' => $item['name'],
                    'description' => $item['description'],
                    'price' => (float)$item['price'],
                    'category' => $item['category'],
                    'available' => (bool)$item['is_available'],
                    'image_url' => $item['image_url']
                ];
            }, $menuItems);

            $response = $this->deliverydash_lib->syncMenu($formattedItems);
            
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'success' => true,
                    'message' => 'Menu synced successfully',
                    'items_synced' => count($formattedItems)
                ]));
                
        } catch (Exception $e) {
            $this->output
                ->set_status_header(500)
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'success' => false,
                    'error' => $e->getMessage()
                ]));
        }
    }
}
```

```php
<?php
// application/controllers/Orders.php

defined('BASEPATH') OR exit('No direct script access allowed');

class Orders extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->library('deliverydash_lib');
        $this->load->model('order_model');
    }

    public function fetch_new()
    {
        try {
            $orders = $this->deliverydash_lib->getNewOrders();
            
            foreach ($orders as $order) {
                // Save to local database
                $localOrderId = $this->order_model->create_order([
                    'deliverydash_id' => $order['id'],
                    'customer_name' => $order['customer_name'],
                    'customer_phone' => $order['customer_phone'],
                    'items' => json_encode($order['items']),
                    'total_amount' => $order['total_amount'],
                    'status' => 'received',
                    'created_at' => date('Y-m-d H:i:s')
                ]);

                // Confirm with DeliveryDash
                $this->deliverydash_lib->confirmOrder($order['id']);
            }

            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'success' => true,
                    'orders_processed' => count($orders)
                ]));
                
        } catch (Exception $e) {
            $this->output
                ->set_status_header(500)
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'success' => false,
                    'error' => $e->getMessage()
                ]));
        }
    }

    public function webhook()
    {
        try {
            $payload = file_get_contents('php://input');
            $signature = $this->input->get_request_header('X-DeliveryDash-Signature', TRUE);

            // Handle webhook (implement webhook handling logic)
            
            $this->output
                ->set_content_type('text/plain')
                ->set_output('OK');
                
        } catch (Exception $e) {
            log_message('error', 'Webhook error: ' . $e->getMessage());
            $this->output
                ->set_status_header(400)
                ->set_content_type('text/plain')
                ->set_output('Error');
        }
    }
}
```

### Generic PHP Integration

#### 1. Basic Setup

```php
<?php
// config.php

require_once 'vendor/autoload.php';

use DeliveryDash\DeliveryDash;

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Initialize DeliveryDash
$deliveryDash = new DeliveryDash($_ENV['DELIVERYDASH_API_KEY'], [
    'debug' => $_ENV['APP_ENV'] === 'development',
    'timeout' => 60
]);

return $deliveryDash;
```

#### 2. Menu Sync Script

```php
<?php
// sync_menu.php

$deliveryDash = require_once 'config.php';

// Your menu data (from database, API, etc.)
$menuItems = [
    [
        'external_id' => 'pizza-001',
        'name' => 'Margherita Pizza',
        'description' => 'Fresh tomatoes, mozzarella, basil',
        'price' => 120.00,
        'category' => 'Pizzas',
        'available' => true,
        'image_url' => 'https://yoursite.com/images/margherita.jpg',
        'preparation_time' => 25
    ],
    [
        'external_id' => 'burger-001',
        'name' => 'Classic Beef Burger',
        'description' => 'Beef patty, lettuce, tomato, onion',
        'price' => 85.00,
        'category' => 'Burgers',
        'available' => true,
        'image_url' => 'https://yoursite.com/images/burger.jpg',
        'preparation_time' => 15
    ]
];

try {
    $response = $deliveryDash->menus()->sync($menuItems);
    echo "Menu synced successfully!\n";
    echo "Items synced: " . count($menuItems) . "\n";
} catch (Exception $e) {
    echo "Error syncing menu: " . $e->getMessage() . "\n";
    exit(1);
}
```

#### 3. Order Processing Script

```php
<?php
// process_orders.php

$deliveryDash = require_once 'config.php';

try {
    // Get new orders
    $newOrders = $deliveryDash->orders()->getNew();
    
    echo "Found " . count($newOrders) . " new orders\n";
    
    foreach ($newOrders as $order) {
        echo "Processing order: " . $order['id'] . "\n";
        
        // Save to your database/system
        saveOrderToDatabase($order);
        
        // Confirm with DeliveryDash
        $deliveryDash->orders()->confirm($order['id']);
        
        echo "Order confirmed: " . $order['id'] . "\n";
        
        // Update status as you process
        sleep(2); // Simulate processing time
        $deliveryDash->orders()->preparing($order['id'], 20); // 20 minutes
        
        echo "Order marked as preparing: " . $order['id'] . "\n";
    }
    
} catch (Exception $e) {
    echo "Error processing orders: " . $e->getMessage() . "\n";
    exit(1);
}

function saveOrderToDatabase($order)
{
    // Your database saving logic here
    // Example using PDO:
    
    $pdo = new PDO('mysql:host=localhost;dbname=restaurant', $username, $password);
    
    $stmt = $pdo->prepare("
        INSERT INTO orders (deliverydash_id, customer_name, customer_phone, items, total_amount, status) 
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $order['id'],
        $order['customer_name'],
        $order['customer_phone'],
        json_encode($order['items']),
        $order['total_amount'],
        'received'
    ]);
}
```

#### 4. Webhook Endpoint

```php
<?php
// webhook.php

$deliveryDash = require_once 'config.php';

// Get webhook data
$payload = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_DELIVERYDASH_SIGNATURE'] ?? '';

try {
    $event = $deliveryDash->webhooks()->handle($payload, $signature, $_ENV['DELIVERYDASH_WEBHOOK_SECRET']);
    
    // Log the event
    error_log("DeliveryDash webhook: " . $event['event_type']);
    
    switch ($event['event_type']) {
        case 'order.created':
            handleNewOrder($event['data']);
            break;
            
        case 'order.updated':
            handleOrderUpdate($event['data']);
            break;
            
        case 'order.cancelled':
            handleOrderCancellation($event['data']);
            break;
            
        default:
            error_log("Unknown event type: " . $event['event_type']);
    }
    
    http_response_code(200);
    echo 'OK';
    
} catch (Exception $e) {
    error_log("Webhook error: " . $e->getMessage());
    http_response_code(400);
    echo 'Error: ' . $e->getMessage();
}

function handleNewOrder($orderData)
{
    // Handle new order
    error_log("New order received: " . $orderData['id']);
}

function handleOrderUpdate($orderData)
{
    // Handle order update
    error_log("Order updated: " . $orderData['id']);
}

function handleOrderCancellation($orderData)
{
    // Handle order cancellation
    error_log("Order cancelled: " . $orderData['id']);
}
```

#### 5. Cron Job Setup

```bash
# Add to your crontab (crontab -e)
# Check for new orders every 2 minutes
*/2 * * * * /usr/bin/php /path/to/your/process_orders.php

# Sync menu daily at 3 AM
0 3 * * * /usr/bin/php /path/to/your/sync_menu.php
```

## Support

For support, email developers@deliverydash.co.za or visit our documentation at https://docs.deliverydash.co.za


## DEV - initialising repo 

git init
git add README.md
git commit -m "first commit"
git branch -M main
git remote add origin https://github.com/deliverydash/deliverydash-sdk.git
git push -u origin main

or

git remote add origin https://github.com/deliverydash/deliverydash-sdk.git
git branch -M main
git push -u origin main