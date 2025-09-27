<?php

declare(strict_types=1);

require_once __DIR__ . '/../autoload.php';

use BitrixProLib\TrustMe\TrustmeService;
use BitrixProLib\TrustMe\Exceptions\TrustmeException;
use BitrixProLib\TrustMe\Exceptions\TransportException;

/**
 * TrustMe Digital Signature Example
 * 
 * This example demonstrates how to use the TrustmeService class
 * to send documents for digital signing.
 */

try {
    // Initialize TrustMe service
    $trustme = new TrustmeService(
        apiUrl: 'https://api.trustme.kz/',
        authToken: 'your_trustme_token_here',
        logDirectory: __DIR__ . '/../logs'
    );
    
    echo "=== TrustMe Digital Signature Example ===\n\n";
    
    // Example 1: Send document for signing via file URL
    echo "1. Sending document for signing via file URL...\n";
    
    $signingResult = $trustme->sendToSignWithFileUrl(
        fileUrl: 'https://example.com/document.pdf',
        companyName: 'Example Company Ltd.',
        phone: '+77001234567',
        contractName: 'Service Agreement',
        kzBmg: false,
        faceId: false
    );
    
    if ($signingResult['status'] === 'success') {
        echo "✓ Document sent for signing successfully!\n";
        echo "  - Document ID: " . ($signingResult['document_id'] ?? 'N/A') . "\n";
        echo "  - Signing URL: " . ($signingResult['url'] ?? 'N/A') . "\n";
        echo "  - File Name: " . ($signingResult['fileName'] ?? 'N/A') . "\n";
        
        $documentId = $signingResult['document_id'];
    } else {
        echo "✗ Failed to send document for signing\n";
    }
    
    // Example 2: Send document for signing via Base64
    echo "\n2. Sending document for signing via Base64...\n";
    
    $base64Result = $trustme->sendToSignWithFileBase64(
        fileUrl: 'https://example.com/contract.pdf',
        companyName: 'Example Company Ltd.',
        fio: 'Иванов Иван Иванович',
        iin: '123456789012',
        phone: '+77001234567',
        number: 'ДОГ-001',
        info: 'Договор на оказание услуг',
        contractName: 'Service Contract',
        fileExt: 'pdf'
    );
    
    if ($base64Result['status'] === 'success') {
        echo "✓ Document sent for signing via Base64 successfully!\n";
        echo "  - Document ID: " . ($base64Result['document_id'] ?? 'N/A') . "\n";
        echo "  - Signing URL: " . ($base64Result['url'] ?? 'N/A') . "\n";
        echo "  - File Name: " . ($base64Result['fileName'] ?? 'N/A') . "\n";
        
        $base64DocumentId = $base64Result['document_id'];
    } else {
        echo "✗ Failed to send document for signing via Base64\n";
    }
    
    // Example 3: Check document status
    if (isset($documentId)) {
        echo "\n3. Checking document status...\n";
        
        $status = $trustme->getDocumentStatus($documentId);
        
        if (!empty($status)) {
            echo "✓ Document status retrieved:\n";
            echo "  - Status: " . ($status['status'] ?? 'N/A') . "\n";
            echo "  - Created: " . ($status['created_at'] ?? 'N/A') . "\n";
            echo "  - Updated: " . ($status['updated_at'] ?? 'N/A') . "\n";
        } else {
            echo "✗ Failed to get document status\n";
        }
    }
    
    // Example 4: Download signed document
    if (isset($documentId)) {
        echo "\n4. Downloading signed document...\n";
        
        $downloadPath = __DIR__ . '/../downloads/signed_document_' . $documentId . '.pdf';
        
        // Create downloads directory if it doesn't exist
        $downloadDir = dirname($downloadPath);
        if (!is_dir($downloadDir)) {
            mkdir($downloadDir, 0755, true);
        }
        
        $downloaded = $trustme->downloadSignedDocument($documentId, $downloadPath);
        
        if ($downloaded) {
            echo "✓ Signed document downloaded successfully!\n";
            echo "  - Path: {$downloadPath}\n";
            echo "  - Size: " . filesize($downloadPath) . " bytes\n";
        } else {
            echo "✗ Failed to download signed document\n";
        }
    }
    
    echo "\n=== Example completed successfully! ===\n";
    
} catch (TrustmeException $e) {
    echo "TrustMe Error: " . $e->getMessage() . "\n";
    if ($e->getErrorData()) {
        echo "Error Data: " . json_encode($e->getErrorData(), JSON_PRETTY_PRINT) . "\n";
    }
} catch (TransportException $e) {
    echo "Transport Error: " . $e->getMessage() . "\n";
    echo "HTTP Code: " . ($e->getHttpCode() ?? 'N/A') . "\n";
} catch (Exception $e) {
    echo "General Error: " . $e->getMessage() . "\n";
}
