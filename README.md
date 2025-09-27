# 🏢 Business Integration Hub - Профессиональная библиотека для бизнес-интеграций

[![PHP Version](https://img.shields.io/badge/php-%3E%3D8.1-blue.svg)](https://php.net)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)
[![Build Status](https://img.shields.io/badge/build-passing-brightgreen.svg)](https://github.com/n00rd1/BitrixProLib-byN00rd1)

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
composer require n00rd1/bitrixprolib
```

### Ручная установка

1. Клонируйте репозиторий:
```bash
git clone https://github.com/n00rd1/BitrixProLib-byN00rd1.git
cd BitrixProLib-byN00rd1
```

2. Подключите автозагрузчик:
```php
require_once 'path/to/BitrixProLib-byN00rd1/autoload.php';
```

## 🏗️ Структура проекта

```
BitrixProLib-byN00rd1/
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

### TrustMe Цифровые подписи

```php
use BitrixProLib\TrustMe\TrustmeService;

$trustme = new TrustmeService(
    apiUrl: 'https://api.trustme.kz/',
    authToken: 'your_token',
    logDirectory: '/path/to/logs'
);

// Отправка документа на подписание
$result = $trustme->sendToSignWithFileUrl(
    fileUrl: 'https://example.com/document.pdf',
    companyName: 'Ваша компания',
    phone: '+77001234567',
    contractName: 'Договор на услуги'
);
```

### Интеграция с MyStore

```php
use BitrixProLib\MyStore\MyStoreClient;

$mystore = new MyStoreClient(
    login: 'your_login',
    password: 'your_password'
);

// Получение товаров
$products = $mystore->getProducts(limit: 50);

// Создание контакта
$contact = $mystore->createContact([
    'name' => 'Иван Иванов',
    'email' => 'ivan@example.com'
]);
```

### Продвинутое логирование

```php
use BitrixProLib\Logger\Logger;

// Настройка логирования
Logger::setLogDirectory('/path/to/logs');
Logger::setLogRetentionDays(30);

// Логирование сообщений
Logger::log('Операция выполнена успешно', 'success');
Logger::log('Произошла ошибка', 'error');
```

## 📚 API Документация

### Bitrix24 API

#### Управление сделками

* `createDeal(array $fields): int|null` - Создание новой сделки
* `updateDeal(int $dealId, array $fields): bool` - Обновление существующей сделки
* `deleteDeal(int $dealId): bool` - Удаление сделки
* `getDealById(int $dealId): array|null` - Получение сделки по ID
* `listDeals(array $filter, array $select, int $start): array|null` - Список сделок

#### Управление контактами

* `createContact(array $fields): int|null` - Создание нового контакта
* `updateContact(int $contactId, array $fields): bool` - Обновление контакта
* `deleteContact(int $contactId): bool` - Удаление контакта
* `getContactById(int $contactId): array|null` - Получение контакта по ID
* `listContacts(array $filter, array $select, int $start): array|null` - Список контактов

#### Генерация документов

* `createDocument(int $templateId, int $entityTypeId, int $entityId, array $values): array|null` - Создание документа
* `enableDocumentPublicUrl(int $documentId): string|null` - Включение публичной ссылки

### TrustMe API

#### Подписание документов

* `sendToSignWithFileUrl(string $fileUrl, string $companyName, string $phone, ...): array` - Отправка документа по URL
* `sendToSignWithFileBase64(string $fileUrl, string $companyName, string $fio, ...): array` - Отправка документа в Base64
* `getDocumentStatus(string $documentId): array` - Получение статуса документа
* `downloadSignedDocument(string $documentId, string $savePath): bool` - Скачивание подписанного документа

### MyStore API

#### Управление сущностями

* `getProducts(int $limit, int $offset): array` - Получение товаров
* `createProduct(array $data): array` - Создание товара
* `getContacts(int $limit, int $offset): array` - Получение контактов
* `createContact(array $data): array` - Создание контакта
* `getCustomerOrders(int $limit, int $offset): array` - Получение заказов покупателей

### Утилитарные функции

#### Валидация данных

* `validatePhone(string $phone): bool` - Валидация номера телефона
* `validateIIN(string $iin): bool` - Валидация ИИН (Казахстан)
* `validateEmail(string $email): bool` - Валидация email адреса
* `validateDate(string $date): bool` - Валидация даты

#### Форматирование данных

* `formatPhone(string $phone, string $format): string` - Форматирование номера телефона
* `formatCurrency(float $amount, string $currency): string` - Форматирование валюты
* `formatDate(string|\DateTime $date, string $format): string` - Форматирование даты

## 📖 Примеры

Проверьте папку `examples/` для подробных примеров использования:

* `examples/bitrix_integration.php` - Полный рабочий процесс Bitrix24
* `examples/trustme_signing.php` - Процесс подписания документов
* `examples/mystore_inventory.php` - Управление инвентарем
* `examples/webhook_handler.php` - Обработка webhook'ов

## ⚙️ Конфигурация

### Переменные окружения

Создайте файл `.env` в корне проекта:

```env
# Конфигурация Bitrix24
BITRIX_AUTH_TOKEN=your_auth_token
BITRIX_API_URL=https://your-portal.bitrix24.com/rest/main_user/

# Конфигурация TrustMe
TRUSTME_API_URL=https://api.trustme.kz/
TRUSTME_AUTH_TOKEN=your_trustme_token

# Конфигурация MyStore
MYSTORE_LOGIN=your_login
MYSTORE_PASSWORD=your_password

# Конфигурация логирования
LOG_DIRECTORY=/path/to/logs
LOG_RETENTION_DAYS=30
```

### Конфигурация логирования

```php
use BitrixProLib\Logger\Logger;

// Установка директории логов
Logger::setLogDirectory('/var/log/bitrixprolib');

// Установка периода хранения (дни)
Logger::setLogRetentionDays(60);

// Уровни логирования: 'error', 'success', 'creation', 'update', 'request'
```

## 🔧 Обработка ошибок

Библиотека предоставляет комплексную обработку ошибок:

```php
try {
    $dealId = $bitrix->createDeal($dealData);
} catch (BitrixException $e) {
    // Обработка ошибок Bitrix
    Logger::log("Ошибка Bitrix: " . $e->getMessage(), 'error');
} catch (TransportException $e) {
    // Обработка сетевых/транспортных ошибок
    Logger::log("Транспортная ошибка: " . $e->getMessage(), 'error');
} catch (Exception $e) {
    // Обработка общих ошибок
    Logger::log("Общая ошибка: " . $e->getMessage(), 'error');
}
```

## 🤝 Участие в разработке

Мы приветствуем вклад в развитие! Пожалуйста, ознакомьтесь с нашим Руководством по участию для подробностей.

### Настройка среды разработки

1. Форкните репозиторий
2. Клонируйте ваш форк: `git clone https://github.com/your-username/BitrixProLib-byN00rd1.git`
3. Установите зависимости: `composer install`
4. Запустите проверки качества: `composer quality`
5. Создайте ветку для функции: `git checkout -b feature/amazing-feature`
6. Зафиксируйте изменения: `git commit -m 'Добавить удивительную функцию'`
7. Отправьте в ветку: `git push origin feature/amazing-feature`
8. Откройте Pull Request

## 📄 Лицензия

Этот проект лицензирован под лицензией MIT - см. файл LICENSE для подробностей.

## 🆘 Поддержка

* 📧 **Email:** [mukhamedshin13@gmail.com](mailto:mukhamedshin13@gmail.com)
* 💬 **Telegram:** [@n00rd1](https://t.me/n00rd1)
* 🐛 **Issues:** [GitHub Issues](https://github.com/n00rd1/BitrixProLib-byN00rd1/issues)
* 💬 **Discussions:** [GitHub Discussions](https://github.com/n00rd1/BitrixProLib-byN00rd1/discussions)

## 🙏 Благодарности

* Команде Bitrix24 за отличную CRM платформу
* Команде TrustMe за сервисы цифровых подписей
* Команде MyStore за решения управления складом
* PHP сообществу за отличные инструменты и библиотеки

## 📊 История изменений

См. CHANGELOG.md для списка изменений и истории версий.

---

**Создано с ❤️ для PHP сообщества**

## Об авторе

**n00rd1** - Системный интегратор с 3+ летним опытом разработки интеграций для бизнеса.

* 🔗 **GitHub:** [@n00rd1](https://github.com/n00rd1)
* 💬 **Telegram:** [@n00rd1](https://t.me/n00rd1)
* 📧 **Email:** [mukhamedshin13@gmail.com](mailto:mukhamedshin13@gmail.com)

---

*Если эта библиотека помогла вам в работе, поставьте ⭐ звездочку!*
