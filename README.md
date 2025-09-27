# 🏢 Business Integration Hub - Профессиональная библиотека для бизнес-интеграций

[![PHP Version](https://img.shields.io/badge/php-%3E%3D8.1-blue.svg)](https://php.net)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)
[![Build Status](https://img.shields.io/badge/build-passing-brightgreen.svg)](https://github.com/your-username/business-integration-hub)
[![Downloads](https://img.shields.io/packagist/dt/your-username/business-integration-hub.svg)](https://packagist.org/packages/your-username/business-integration-hub)

Профессиональная PHP библиотека для комплексной интеграции бизнес-систем. Предоставляет единый интерфейс для работы с CRM Bitrix24, сервисом цифровых подписей TrustMe, системой управления складом MyStore и множеством других бизнес-сервисов. Создана системным интегратором с 5+ летним опытом для решения реальных задач автоматизации бизнес-процессов.

## 🚀 Возможности

### Основные компоненты

- **Интеграция с Bitrix24** - Полная обертка REST API для операций CRM
- **Сервис TrustMe** - Цифровое подписание и управление документами
- **Интеграция с MyStore** - Управление складом и инвентарем
- **Продвинутое логирование** - Структурированное логирование с автоматической очисткой
- **Утилитарные функции** - Общие бизнес-операции и валидация данных

### Ключевые преимущества

- ✅ **Типобезопасность** - Полные объявления типов PHP 8.1+
- ✅ **Обработка ошибок** - Комплексная обработка исключений и логика повторов
- ✅ **Логирование** - Структурированное логирование с настраиваемым хранением
- ✅ **Документация** - Обширная встроенная документация
- ✅ **Соответствие PSR** - Следует стандартам автозагрузки PSR-4

## 📦 Установка

### Composer (Рекомендуется)

```bash
composer require your-username/bitrixprolib
```

### Ручная установка

1. Клонируйте репозиторий:
```bash
git clone https://github.com/your-username/bitrixprolib.git
cd bitrixprolib
```

2. Подключите автозагрузчик:
```php
require_once 'path/to/bitrixprolib/autoload.php';
```

## 🏗️ Структура проекта

```
bitrixprolib/
├── src/
│   ├── Bitrix/           # Интеграция с CRM Bitrix24
│   ├── TrustMe/          # Сервис цифровых подписей
│   ├── MyStore/          # Управление складом
│   ├── Logger/           # Продвинутая система логирования
│   └── Utils/            # Утилитарные функции
├── docs/                 # Документация
├── examples/             # Примеры использования
└── composer.json         # Зависимости
```

## 🚀 Быстрый старт

### Интеграция с Bitrix24

```php
use BitrixProLib\Bitrix\BitrixApi;

$bitrix = new BitrixApi(
    authToken: 'your_auth_token',
    apiUrl: 'https://your-portal.bitrix24.com/rest/main_user/',
    logDirectory: '/path/to/logs'
);

// Создание сделки
$dealId = $bitrix->createDeal([
    'TITLE' => 'Новая сделка',
    'STAGE_ID' => 'NEW',
    'OPPORTUNITY' => 10000
]);

// Получение клиента по телефону
$clients = $bitrix->listContacts(['PHONE' => '+77001234567']);
```

### TrustMe Digital Signatures

```php
use BitrixProLib\TrustMe\TrustmeService;

$trustme = new TrustmeService(
    apiUrl: 'https://api.trustme.kz/',
    authToken: 'your_token',
    logDirectory: '/path/to/logs'
);

// Send document for signing
$result = $trustme->sendToSignWithFileUrl(
    fileUrl: 'https://example.com/document.pdf',
    companyName: 'Your Company',
    phone: '+77001234567',
    contractName: 'Service Agreement'
);
```

### MyStore Integration

```php
use BitrixProLib\MyStore\MyStoreClient;

$mystore = new MyStoreClient(
    login: 'your_login',
    password: 'your_password'
);

// Get products
$products = $mystore->getProducts(limit: 50);

// Create contact
$contact = $mystore->createContact([
    'name' => 'John Doe',
    'email' => 'john@example.com'
]);
```

### Advanced Logging

```php
use BitrixProLib\Logger\Logger;

// Configure logging
Logger::setLogDirectory('/path/to/logs');
Logger::setLogRetentionDays(30);

// Log messages
Logger::log('Operation completed successfully', 'success');
Logger::log('Error occurred', 'error');
```

## 📚 API Documentation

### Bitrix24 API

#### Deal Management
- `createDeal(array $fields): int|null` - Create new deal
- `updateDeal(int $dealId, array $fields): bool` - Update existing deal
- `deleteDeal(int $dealId): bool` - Delete deal
- `getDealById(int $dealId): array|null` - Get deal by ID
- `listDeals(array $filter, array $select, int $start): array|null` - List deals

#### Contact Management
- `createClient(array $fields): int|null` - Create new contact
- `updateClient(int $clientId, array $fields): bool` - Update contact
- `deleteClient(int $clientId): bool` - Delete contact
- `getClientById(int $clientId): array|null` - Get contact by ID
- `listClients(array $filter, array $select, int $start): array|null` - List contacts

#### Document Generation
- `createDocument(int $templateId, int $entityTypeId, int $entityId, array $values): array|null` - Generate document
- `enableDocumentPublicUrl(int $documentId): string|null` - Enable public URL for document

### TrustMe API

#### Document Signing
- `sendToSignWithFileUrl(string $fileUrl, string $companyName, string $phone, ...): array` - Send document by URL
- `sendToSignWithFileBase64(string $fileUrl, string $companyName, string $fio, ...): array` - Send document as Base64

### MyStore API

#### Entity Management
- `getProducts(int $limit, int $offset): array` - Get products
- `createProduct(array $data): array` - Create product
- `getContacts(int $limit, int $offset): array` - Get contacts
- `createContact(array $data): array` - Create contact
- `getCustomerOrders(int $limit, int $offset): array` - Get customer orders

### Utility Functions

#### Data Validation
- `isValidPhone(string $phone): bool` - Validate phone number
- `isValidIIN(string $iin): bool` - Validate IIN (Kazakhstan)
- `sanitizePhoneNumber(string $phone): string` - Clean phone number

#### Name Processing
- `splitFullName(string $fullName): array` - Split full name into parts
- `combineFullName(string $lastName, string $firstName, string $middleName): string` - Combine name parts

#### Response Handling
- `sendResponse(bool $success, ?string $message, ?array $data, int $status): void` - Send JSON response
- `logAndRespond(string $logMessage, ?string $responseMessage, ...): void` - Log and respond

## 🧪 Testing

Run the test suite:

```bash
composer test
```

Run specific test groups:

```bash
# Test Bitrix integration
composer test:bitrix

# Test TrustMe integration  
composer test:trustme

# Test MyStore integration
composer test:mystore

# Test utilities
composer test:utils
```

## 📖 Examples

Check the `examples/` directory for comprehensive usage examples:

- `examples/bitrix_integration.php` - Complete Bitrix24 workflow
- `examples/trustme_signing.php` - Document signing process
- `examples/mystore_inventory.php` - Inventory management
- `examples/webhook_handler.php` - Webhook processing

## ⚙️ Configuration

### Environment Variables

Create a `.env` file in your project root:

```env
# Bitrix24 Configuration
BITRIX_AUTH_TOKEN=your_auth_token
BITRIX_API_URL=https://your-portal.bitrix24.com/rest/main_user/

# TrustMe Configuration
TRUSTME_API_URL=https://api.trustme.kz/
TRUSTME_AUTH_TOKEN=your_trustme_token

# MyStore Configuration
MYSTORE_LOGIN=your_login
MYSTORE_PASSWORD=your_password

# Logging Configuration
LOG_DIRECTORY=/path/to/logs
LOG_RETENTION_DAYS=30
```

### Logging Configuration

```php
use BitrixProLib\Logger\Logger;

// Set log directory
Logger::setLogDirectory('/var/log/bitrixprolib');

// Set retention period (days)
Logger::setLogRetentionDays(60);

// Log levels: 'error', 'success', 'creation', 'update', 'request'
```

## 🔧 Error Handling

The library provides comprehensive error handling:

```php
try {
    $dealId = $bitrix->createDeal($dealData);
} catch (BitrixException $e) {
    // Handle Bitrix-specific errors
    Logger::log("Bitrix error: " . $e->getMessage(), 'error');
} catch (TransportException $e) {
    // Handle network/transport errors
    Logger::log("Transport error: " . $e->getMessage(), 'error');
} catch (Exception $e) {
    // Handle general errors
    Logger::log("General error: " . $e->getMessage(), 'error');
}
```

## 🤝 Contributing

We welcome contributions! Please see our [Contributing Guide](CONTRIBUTING.md) for details.

### Development Setup

1. Fork the repository
2. Clone your fork: `git clone https://github.com/your-username/bitrixprolib.git`
3. Install dependencies: `composer install`
4. Run tests: `composer test`
5. Create a feature branch: `git checkout -b feature/amazing-feature`
6. Commit your changes: `git commit -m 'Add amazing feature'`
7. Push to the branch: `git push origin feature/amazing-feature`
8. Open a Pull Request

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 🆘 Support

- 📧 Email: support@bitrixprolib.com
- 📖 Documentation: [docs.bitrixprolib.com](https://docs.bitrixprolib.com)
- 🐛 Issues: [GitHub Issues](https://github.com/your-username/bitrixprolib/issues)
- 💬 Discussions: [GitHub Discussions](https://github.com/your-username/bitrixprolib/discussions)

## 🙏 Acknowledgments

- Bitrix24 team for the excellent CRM platform
- TrustMe team for digital signature services
- MyStore team for inventory management solutions
- PHP community for excellent tools and libraries

## 📊 Changelog

See [CHANGELOG.md](CHANGELOG.md) for a list of changes and version history.

---

**Made with ❤️ for the PHP community**