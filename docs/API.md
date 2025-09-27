# BitrixProLib API Documentation

## Overview

BitrixProLib is a comprehensive PHP library for integrating with various business services including Bitrix24 CRM, TrustMe digital signatures, MyStore inventory management, and utility functions.

## Table of Contents

- [Bitrix24 Integration](#bitrix24-integration)
- [TrustMe Integration](#trustme-integration)
- [MyStore Integration](#mystore-integration)
- [Logger](#logger)
- [Utilities](#utilities)

## Bitrix24 Integration

### BitrixApi Class

The `BitrixApi` class provides comprehensive integration with Bitrix24 CRM.

#### Constructor

```php
new BitrixApi(
    string $authToken,
    string $apiUrl = 'https://bitrix.domain.com/rest/main_user/',
    string $logDirectory = '',
    int $maxRetries = 3,
    int $retryDelay = 500_000
)
```

#### Deal Methods

- `createDeal(array $fields): ?int` - Create a new deal
- `updateDeal(int $dealId, array $fields): bool` - Update existing deal
- `deleteDeal(int $dealId): bool` - Delete deal
- `getDealById(int $dealId): ?array` - Get deal by ID
- `listDeals(array $filter, array $select, int $start): ?array` - List deals

#### Contact Methods

- `createContact(array $fields): ?int` - Create a new contact
- `updateContact(int $contactId, array $fields): bool` - Update contact
- `deleteContact(int $contactId): bool` - Delete contact
- `getContactById(int $contactId): ?array` - Get contact by ID
- `listContacts(array $filter, array $select, int $start): ?array` - List contacts

#### Company Methods

- `createCompany(array $fields): ?int` - Create a new company
- `updateCompany(int $companyId, array $fields): bool` - Update company
- `listCompanies(array $filter, array $select, int $start): ?array` - List companies

#### Document Generation

- `createDocument(int $templateId, int $entityTypeId, int $entityId, array $values): ?array` - Generate document
- `enableDocumentPublicUrl(int $documentId): ?string` - Enable public URL for document

## TrustMe Integration

### TrustmeService Class

The `TrustmeService` class provides integration with TrustMe digital signature service.

#### Constructor

```php
new TrustmeService(
    string $apiUrl,
    string $authToken,
    string $logDirectory = '',
    int $maxRetries = 3,
    int $retryDelay = 500_000
)
```

#### Document Signing Methods

- `sendToSignWithFileUrl(string $fileUrl, string $companyName, string $phone, ...): array` - Send document by URL
- `sendToSignWithFileBase64(string $fileUrl, string $companyName, string $fio, ...): array` - Send document as Base64

#### Document Management

- `getDocumentStatus(string $documentId): array` - Get document status
- `downloadSignedDocument(string $documentId, string $savePath): bool` - Download signed document

## MyStore Integration

### MyStoreClient Class

The `MyStoreClient` class provides integration with MyStore inventory management system.

#### Constructor

```php
new MyStoreClient(
    string $login,
    string $password,
    string $logDirectory = '',
    int $maxRetries = 3,
    int $retryDelay = 500_000
)
```

#### Contact Methods

- `getContacts(int $limit, int $offset): array` - Get contacts
- `getContact(string $id): array` - Get contact by ID
- `createContact(array $data): array` - Create contact
- `updateContact(string $id, array $data): array` - Update contact
- `deleteContact(string $id): array` - Delete contact

#### Product Methods

- `getProducts(int $limit, int $offset): array` - Get products
- `getProduct(string $id): array` - Get product by ID
- `createProduct(array $data): array` - Create product
- `updateProduct(string $id, array $data): array` - Update product
- `deleteProduct(string $id): array` - Delete product

#### Order Methods

- `getCustomerOrders(int $limit, int $offset): array` - Get customer orders
- `getCustomerOrder(string $id): array` - Get order by ID
- `createCustomerOrder(array $data): array` - Create order
- `updateCustomerOrder(string $id, array $data): array` - Update order
- `deleteCustomerOrder(string $id): array` - Delete order

#### Warehouse Methods

- `getWarehouses(int $limit, int $offset): array` - Get warehouses
- `getWarehouse(string $id): array` - Get warehouse by ID
- `getStoreStocks(string $storeId): array` - Get store stocks

## Logger

### Logger Class

The `Logger` class provides advanced logging functionality with structured logging and automatic cleanup.

#### Configuration Methods

- `setLogDirectory(string $logDirectory): void` - Set log directory
- `setLogRetentionDays(int $days): void` - Set retention period
- `setLogFormat(string $format): void` - Set log format (text/json)
- `setLogLevel(string $level): void` - Set minimum log level

#### Logging Methods

- `log(string $message, string $type, array $context, string $level): void` - Log message
- `logException(\Throwable $exception, string $type, array $context): void` - Log exception

#### Performance Monitoring

- `startTimer(string $operation): void` - Start performance timer
- `endTimer(string $operation, string $message): array` - End timer and get metrics

#### Utility Methods

- `getSessionId(): string` - Get current session ID
- `getLogStats(): array` - Get logging statistics

## Utilities

### DataValidator Class

Validation utilities for common data types.

#### Validation Methods

- `isValidPhone(string $phone): bool` - Validate phone number
- `isValidIIN(string $iin): bool` - Validate IIN
- `isValidBIN(string $bin): bool` - Validate BIN
- `isValidEmail(string $email): bool` - Validate email
- `isValidUrl(string $url): bool` - Validate URL
- `isValidDate(string $date, string $format): bool` - Validate date
- `isValidNumeric(mixed $value, ?float $min, ?float $max): bool` - Validate numeric value
- `isValidStringLength(string $string, ?int $minLength, ?int $maxLength): bool` - Validate string length
- `isValidArrayStructure(array $data, array $requiredKeys, array $optionalKeys): bool` - Validate array structure
- `isValidJson(string $json): bool` - Validate JSON
- `isValidFileExtension(string $filename, array $allowedExtensions): bool` - Validate file extension
- `isValidFileSize(int $fileSize, ?int $maxSize): bool` - Validate file size

### DataFormatter Class

Formatting utilities for common data types.

#### Formatting Methods

- `formatPhone(string $phone, string $format): string` - Format phone number
- `formatPhoneVariants(string $phone): array` - Generate phone variants
- `splitFullName(string $fullName): array` - Split full name
- `combineFullName(string $lastName, string $firstName, string $middleName): string` - Combine name parts
- `formatCurrency(float $amount, string $currency, int $decimals): string` - Format currency
- `formatDate(string|\DateTime $date, string $format): string` - Format date
- `formatFileSize(int $bytes, int $precision): string` - Format file size
- `formatDuration(int $seconds): string` - Format duration
- `sanitizeString(string $string, bool $stripTags): string` - Sanitize string
- `generateRandomString(int $length, string $characters): string` - Generate random string
- `arrayToCsv(array $data, string $delimiter, string $enclosure): string` - Convert array to CSV
- `csvToArray(string $csv, string $delimiter, string $enclosure): array` - Convert CSV to array
- `maskSensitiveData(string $data, int $visibleChars, string $maskChar): string` - Mask sensitive data

### HttpHelper Class

HTTP utilities for web applications.

#### Request Methods

- `getRequestData(): array` - Get request data
- `getClientIp(): string` - Get client IP
- `getUserAgent(): string` - Get user agent
- `isAjaxRequest(): bool` - Check if AJAX request
- `isPostRequest(): bool` - Check if POST request
- `isGetRequest(): bool` - Check if GET request
- `getRequestMethod(): string` - Get request method
- `getRequestUri(): string` - Get request URI
- `getReferer(): ?string` - Get referer URL

#### Response Methods

- `sendJsonResponse(bool $success, ?string $message, ?array $data, int $status): void` - Send JSON response
- `sendErrorResponse(string $message, ?array $data, int $status): void` - Send error response
- `sendSuccessResponse(string $message, ?array $data, int $status): void` - Send success response
- `logAndRespond(string $logMessage, ?string $responseMessage, ?array $data, int $statusCode, string $logType): void` - Log and respond

#### Utility Methods

- `setCorsHeaders(string $origin, array $methods, array $headers): void` - Set CORS headers
- `handlePreflightRequest(): void` - Handle preflight request
- `downloadFile(string $filePath, ?string $filename, ?string $contentType): void` - Download file
- `redirect(string $url, int $statusCode): void` - Redirect to URL

### JsonHelper Class

JSON utilities with error handling.

#### Encoding/Decoding Methods

- `encode(mixed $data, int $flags): string` - Encode to JSON
- `decode(string $json, bool $associative, int $depth, int $flags): mixed` - Decode from JSON
- `safeDecode(string $json, bool $associative): mixed` - Safe decode (returns null on error)
- `isValid(string $json): bool` - Validate JSON
- `prettyEncode(mixed $data, int $flags): string` - Pretty print JSON

#### Utility Methods

- `getLastError(): string` - Get last JSON error message
- `getLastErrorCode(): int` - Get last JSON error code
- `arrayToJson(array $array, int $flags): string` - Convert array to JSON
- `jsonToArray(string $json, int $depth, int $flags): array` - Convert JSON to array
- `safeJsonToArray(string $json): array` - Safe convert to array
- `merge(string $json1, string $json2, int $flags): string` - Merge JSON strings
- `extract(string $json, string $path, mixed $default): mixed` - Extract value using dot notation
- `set(string $json, string $path, mixed $value, int $flags): string` - Set value using dot notation

## Error Handling

All classes use custom exception types for better error handling:

- `BitrixException` - Bitrix24 API errors
- `TrustmeException` - TrustMe service errors
- `MyStoreException` - MyStore service errors
- `TransportException` - Network/transport errors

## Examples

See the `examples/` directory for comprehensive usage examples:

- `bitrix_integration.php` - Complete Bitrix24 workflow
- `trustme_signing.php` - Document signing process
- `mystore_inventory.php` - Inventory management
- `webhook_handler.php` - Webhook processing

## Testing

Run the test suite:

```bash
composer test
```

Run specific test groups:

```bash
composer test:bitrix
composer test:trustme
composer test:mystore
composer test:utils
```
