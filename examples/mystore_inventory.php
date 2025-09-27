<?php

declare(strict_types=1);

require_once __DIR__ . '/../autoload.php';

use BitrixProLib\MyStore\MyStoreClient;
use BitrixProLib\MyStore\Exceptions\MyStoreException;
use BitrixProLib\MyStore\Exceptions\TransportException;

/**
 * MyStore Inventory Management Example
 * 
 * This example demonstrates how to use the MyStoreClient class
 * to manage inventory and business operations.
 */

try {
    // Initialize MyStore client
    $mystore = new MyStoreClient(
        login: 'your_mystore_login',
        password: 'your_mystore_password',
        logDirectory: __DIR__ . '/../logs'
    );
    
    echo "=== MyStore Inventory Management Example ===\n\n";
    
    // 1. Get products
    echo "1. Getting products...\n";
    $products = $mystore->getProducts(limit: 10);
    
    if (!empty($products['rows'])) {
        echo "✓ Found " . count($products['rows']) . " products:\n";
        foreach ($products['rows'] as $product) {
            echo "  - ID: {$product['id']}, Name: " . ($product['name'] ?? 'N/A') . "\n";
        }
    } else {
        echo "✗ No products found or failed to retrieve products\n";
    }
    
    // 2. Create a new product
    echo "\n2. Creating a new product...\n";
    $newProduct = $mystore->createProduct([
        'name' => 'Example Product',
        'description' => 'Product created via BitrixProLib',
        'article' => 'EX-001',
        'price' => [
            'value' => 1000,
            'currency' => [
                'meta' => [
                    'href' => 'https://api.moysklad.ru/api/remap/1.2/entity/currency/10772c12-36e7-11e7-8a7f-40d000000097',
                    'metadataHref' => 'https://api.moysklad.ru/api/remap/1.2/entity/currency/metadata',
                    'type' => 'currency',
                    'mediaType' => 'application/json'
                ]
            ]
        ],
        'vat' => 0
    ]);
    
    if (!empty($newProduct)) {
        $productId = $newProduct['id'];
        echo "✓ Product created successfully with ID: {$productId}\n";
    } else {
        echo "✗ Failed to create product\n";
    }
    
    // 3. Get contacts
    echo "\n3. Getting contacts...\n";
    $contacts = $mystore->getContacts(limit: 10);
    
    if (!empty($contacts['rows'])) {
        echo "✓ Found " . count($contacts['rows']) . " contacts:\n";
        foreach ($contacts['rows'] as $contact) {
            echo "  - ID: {$contact['id']}, Name: " . ($contact['name'] ?? 'N/A') . "\n";
        }
    } else {
        echo "✗ No contacts found or failed to retrieve contacts\n";
    }
    
    // 4. Create a new contact
    echo "\n4. Creating a new contact...\n";
    $newContact = $mystore->createContact([
        'name' => 'John Doe',
        'email' => 'john.doe@example.com',
        'phone' => '+77001234567',
        'position' => 'Manager'
    ]);
    
    if (!empty($newContact)) {
        $contactId = $newContact['id'];
        echo "✓ Contact created successfully with ID: {$contactId}\n";
    } else {
        echo "✗ Failed to create contact\n";
    }
    
    // 5. Get warehouses
    echo "\n5. Getting warehouses...\n";
    $warehouses = $mystore->getWarehouses(limit: 10);
    
    if (!empty($warehouses['rows'])) {
        echo "✓ Found " . count($warehouses['rows']) . " warehouses:\n";
        foreach ($warehouses['rows'] as $warehouse) {
            echo "  - ID: {$warehouse['id']}, Name: " . ($warehouse['name'] ?? 'N/A') . "\n";
        }
    } else {
        echo "✗ No warehouses found or failed to retrieve warehouses\n";
    }
    
    // 6. Get customer orders
    echo "\n6. Getting customer orders...\n";
    $orders = $mystore->getCustomerOrders(limit: 10);
    
    if (!empty($orders['rows'])) {
        echo "✓ Found " . count($orders['rows']) . " customer orders:\n";
        foreach ($orders['rows'] as $order) {
            echo "  - ID: {$order['id']}, Name: " . ($order['name'] ?? 'N/A') . "\n";
        }
    } else {
        echo "✗ No customer orders found or failed to retrieve orders\n";
    }
    
    // 7. Create a customer order
    if (isset($contactId) && isset($productId)) {
        echo "\n7. Creating a customer order...\n";
        $newOrder = $mystore->createCustomerOrder([
            'name' => 'Order-001',
            'description' => 'Order created via BitrixProLib',
            'agent' => [
                'meta' => [
                    'href' => "https://api.moysklad.ru/api/remap/1.2/entity/counterparty/{$contactId}",
                    'metadataHref' => 'https://api.moysklad.ru/api/remap/1.2/entity/counterparty/metadata',
                    'type' => 'counterparty',
                    'mediaType' => 'application/json'
                ]
            ],
            'positions' => [
                [
                    'quantity' => 1,
                    'price' => 1000,
                    'assortment' => [
                        'meta' => [
                            'href' => "https://api.moysklad.ru/api/remap/1.2/entity/product/{$productId}",
                            'metadataHref' => 'https://api.moysklad.ru/api/remap/1.2/entity/product/metadata',
                            'type' => 'product',
                            'mediaType' => 'application/json'
                        ]
                    ]
                ]
            ]
        ]);
        
        if (!empty($newOrder)) {
            $orderId = $newOrder['id'];
            echo "✓ Customer order created successfully with ID: {$orderId}\n";
        } else {
            echo "✗ Failed to create customer order\n";
        }
    }
    
    // 8. Get employees
    echo "\n8. Getting employees...\n";
    $employees = $mystore->getEmployees(limit: 10);
    
    if (!empty($employees['rows'])) {
        echo "✓ Found " . count($employees['rows']) . " employees:\n";
        foreach ($employees['rows'] as $employee) {
            echo "  - ID: {$employee['id']}, Name: " . ($employee['name'] ?? 'N/A') . "\n";
        }
    } else {
        echo "✗ No employees found or failed to retrieve employees\n";
    }
    
    echo "\n=== Example completed successfully! ===\n";
    
} catch (MyStoreException $e) {
    echo "MyStore Error: " . $e->getMessage() . "\n";
    if ($e->getErrorData()) {
        echo "Error Data: " . json_encode($e->getErrorData(), JSON_PRETTY_PRINT) . "\n";
    }
} catch (TransportException $e) {
    echo "Transport Error: " . $e->getMessage() . "\n";
    echo "HTTP Code: " . ($e->getHttpCode() ?? 'N/A') . "\n";
} catch (Exception $e) {
    echo "General Error: " . $e->getMessage() . "\n";
}
