<?php

declare(strict_types=1);

require_once __DIR__ . '/../autoload.php';

use BitrixProLib\Utils\HttpHelper;
use BitrixProLib\Utils\DataValidator;
use BitrixProLib\Utils\DataFormatter;
use BitrixProLib\Logger\Logger;

/**
 * Webhook Handler Example
 * 
 * This example demonstrates how to handle incoming webhooks
 * and process data using the utility functions.
 */

// Set CORS headers for webhook endpoint
HttpHelper::setCorsHeaders();
HttpHelper::handlePreflightRequest();

try {
    echo "=== Webhook Handler Example ===\n\n";
    
    // 1. Get request data
    echo "1. Processing incoming webhook...\n";
    $data = HttpHelper::getRequestData();
    
    if (empty($data)) {
        HttpHelper::sendErrorResponse('No data received', null, 400);
    }
    
    echo "✓ Received webhook data:\n";
    echo "  - Request method: " . HttpHelper::getRequestMethod() . "\n";
    echo "  - Client IP: " . HttpHelper::getClientIp() . "\n";
    echo "  - User agent: " . HttpHelper::getUserAgent() . "\n";
    echo "  - Is AJAX: " . (HttpHelper::isAjaxRequest() ? 'Yes' : 'No') . "\n";
    
    // 2. Validate incoming data
    echo "\n2. Validating incoming data...\n";
    
    $validationErrors = [];
    
    // Validate phone number if present
    if (isset($data['phone'])) {
        if (!DataValidator::isValidPhone($data['phone'])) {
            $validationErrors[] = 'Invalid phone number format';
        } else {
            echo "✓ Phone number is valid: " . $data['phone'] . "\n";
        }
    }
    
    // Validate email if present
    if (isset($data['email'])) {
        if (!DataValidator::isValidEmail($data['email'])) {
            $validationErrors[] = 'Invalid email format';
        } else {
            echo "✓ Email is valid: " . $data['email'] . "\n";
        }
    }
    
    // Validate IIN if present
    if (isset($data['iin'])) {
        if (!DataValidator::isValidIIN($data['iin'])) {
            $validationErrors[] = 'Invalid IIN format';
        } else {
            echo "✓ IIN is valid: " . $data['iin'] . "\n";
        }
    }
    
    if (!empty($validationErrors)) {
        HttpHelper::sendErrorResponse('Validation failed', $validationErrors, 400);
    }
    
    // 3. Process and format data
    echo "\n3. Processing and formatting data...\n";
    
    $processedData = [];
    
    // Format phone number
    if (isset($data['phone'])) {
        $processedData['phone'] = DataFormatter::formatPhone($data['phone'], '+7');
        $processedData['phone_variants'] = DataFormatter::formatPhoneVariants($data['phone']);
        echo "✓ Phone formatted: " . $processedData['phone'] . "\n";
    }
    
    // Process full name
    if (isset($data['full_name'])) {
        $nameParts = DataFormatter::splitFullName($data['full_name']);
        $processedData['name_parts'] = $nameParts;
        echo "✓ Name split: " . json_encode($nameParts) . "\n";
    }
    
    // Format currency if present
    if (isset($data['amount'])) {
        $processedData['formatted_amount'] = DataFormatter::formatCurrency(
            (float) $data['amount'], 
            $data['currency'] ?? 'KZT'
        );
        echo "✓ Amount formatted: " . $processedData['formatted_amount'] . "\n";
    }
    
    // 4. Log the webhook event
    echo "\n4. Logging webhook event...\n";
    Logger::log(
        'Webhook received and processed successfully',
        'request',
        [
            'client_ip' => HttpHelper::getClientIp(),
            'user_agent' => HttpHelper::getUserAgent(),
            'data_keys' => array_keys($data),
            'processed_data' => $processedData
        ],
        'info'
    );
    echo "✓ Webhook event logged\n";
    
    // 5. Send success response
    echo "\n5. Sending success response...\n";
    HttpHelper::sendSuccessResponse(
        'Webhook processed successfully',
        [
            'original_data' => $data,
            'processed_data' => $processedData,
            'timestamp' => date('Y-m-d H:i:s'),
            'session_id' => Logger::getSessionId()
        ]
    );
    
} catch (Exception $e) {
    Logger::logException($e, 'error');
    HttpHelper::sendErrorResponse(
        'Internal server error: ' . $e->getMessage(),
        null,
        500
    );
}
