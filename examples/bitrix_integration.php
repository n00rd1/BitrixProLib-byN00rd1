<?php

declare(strict_types=1);

require_once __DIR__ . '/../autoload.php';

use BitrixProLib\Bitrix\BitrixApi;
use BitrixProLib\Bitrix\Exceptions\BitrixException;
use BitrixProLib\Bitrix\Exceptions\TransportException;

/**
 * Bitrix24 Integration Example
 * 
 * This example demonstrates how to use the BitrixApi class
 * to interact with Bitrix24 CRM.
 */

try {
    // Initialize Bitrix API client
    $bitrix = new BitrixApi(
        authToken: 'your_auth_token_here',
        apiUrl: 'https://your-portal.bitrix24.com/rest/main_user/',
        logDirectory: __DIR__ . '/../logs'
    );
    
    echo "=== Bitrix24 Integration Example ===\n\n";
    
    // 1. Create a new deal
    echo "1. Creating a new deal...\n";
    $dealId = $bitrix->createDeal([
        'TITLE' => 'Example Deal',
        'STAGE_ID' => 'NEW',
        'OPPORTUNITY' => 50000,
        'CURRENCY_ID' => 'KZT',
        'COMMENTS' => 'Created via BitrixProLib'
    ]);
    
    if ($dealId) {
        echo "✓ Deal created successfully with ID: {$dealId}\n";
    } else {
        echo "✗ Failed to create deal\n";
    }
    
    // 2. Create a new contact
    echo "\n2. Creating a new contact...\n";
    $contactId = $bitrix->createContact([
        'NAME' => 'John',
        'LAST_NAME' => 'Doe',
        'PHONE' => [
            ['VALUE' => '+77001234567', 'VALUE_TYPE' => 'WORK']
        ],
        'EMAIL' => [
            ['VALUE' => 'john.doe@example.com', 'VALUE_TYPE' => 'WORK']
        ]
    ]);
    
    if ($contactId) {
        echo "✓ Contact created successfully with ID: {$contactId}\n";
    } else {
        echo "✗ Failed to create contact\n";
    }
    
    // 3. Update the deal
    if ($dealId) {
        echo "\n3. Updating the deal...\n";
        $updated = $bitrix->updateDeal($dealId, [
            'STAGE_ID' => 'PREPARATION',
            'OPPORTUNITY' => 75000,
            'COMMENTS' => 'Updated via BitrixProLib - increased opportunity'
        ]);
        
        if ($updated) {
            echo "✓ Deal updated successfully\n";
        } else {
            echo "✗ Failed to update deal\n";
        }
    }
    
    // 4. Get deal information
    if ($dealId) {
        echo "\n4. Getting deal information...\n";
        $deal = $bitrix->getDealById($dealId);
        
        if ($deal) {
            echo "✓ Deal information retrieved:\n";
            echo "  - Title: " . ($deal['TITLE'] ?? 'N/A') . "\n";
            echo "  - Stage: " . ($deal['STAGE_ID'] ?? 'N/A') . "\n";
            echo "  - Opportunity: " . ($deal['OPPORTUNITY'] ?? 'N/A') . "\n";
            echo "  - Currency: " . ($deal['CURRENCY_ID'] ?? 'N/A') . "\n";
        } else {
            echo "✗ Failed to get deal information\n";
        }
    }
    
    // 5. List recent deals
    echo "\n5. Listing recent deals...\n";
    $deals = $bitrix->listDeals(
        filter: ['STAGE_ID' => 'NEW'],
        select: ['ID', 'TITLE', 'STAGE_ID', 'OPPORTUNITY'],
        start: 0
    );
    
    if ($deals && !empty($deals)) {
        echo "✓ Found " . count($deals) . " deals:\n";
        foreach ($deals as $deal) {
            echo "  - ID: {$deal['ID']}, Title: " . ($deal['TITLE'] ?? 'N/A') . "\n";
        }
    } else {
        echo "✗ No deals found or failed to retrieve deals\n";
    }
    
    // 6. Create a company
    echo "\n6. Creating a new company...\n";
    $companyId = $bitrix->createCompany([
        'TITLE' => 'Example Company Ltd.',
        'PHONE' => [
            ['VALUE' => '+77001234567', 'VALUE_TYPE' => 'WORK']
        ],
        'EMAIL' => [
            ['VALUE' => 'info@example.com', 'VALUE_TYPE' => 'WORK']
        ],
        'ADDRESS' => 'Almaty, Kazakhstan'
    ]);
    
    if ($companyId) {
        echo "✓ Company created successfully with ID: {$companyId}\n";
    } else {
        echo "✗ Failed to create company\n";
    }
    
    echo "\n=== Example completed successfully! ===\n";
    
} catch (BitrixException $e) {
    echo "Bitrix API Error: " . $e->getMessage() . "\n";
    if ($e->getErrorData()) {
        echo "Error Data: " . json_encode($e->getErrorData(), JSON_PRETTY_PRINT) . "\n";
    }
} catch (TransportException $e) {
    echo "Transport Error: " . $e->getMessage() . "\n";
    echo "HTTP Code: " . ($e->getHttpCode() ?? 'N/A') . "\n";
} catch (Exception $e) {
    echo "General Error: " . $e->getMessage() . "\n";
}
