
# Документация по работе с Bitrix API и логами

## Оглавление
1. [Введение](#введение)
2. [Подключение библиотек](#подключение-библиотек)
3. [Основные функции](#основные-функции)
   - [getData](#getdata)
   - [getClient](#getclient)
   - [formatPhoneVariants](#formatphonevariants)
   - [splitFullName](#splitfullname)
   - [combineFullName](#combinefullname)
   - [isValidIIN](#isvalidiin)
   - [isValidPhone](#isvalidphone)
   - [sendResponse](#sendresponse)
4. [Логирование](#логирование)
5. [Информация о ревизии](#информация-о-ревизии)

## Введение
Этот скрипт реализует интеграцию с API Bitrix24 для работы с клиентами, их поиском по ИИН и телефону, а также обработкой данных. Дополнительно добавлена система логирования для отслеживания ошибок и успешных операций.

## Подключение библиотек
В зависимости от операционной системы скрипт автоматически подключает библиотеки для логирования и работы с API Bitrix:

```php
require_once match (PHP_OS_FAMILY) {
    'Windows' => 'D:\OSPanel\domains\COMPANY\library\v2\logger\logger.php',
    default => '/home/bitrix/www/local/library/v2/logger/logger.php',
};

require_once match (PHP_OS_FAMILY) {
    'Windows' => 'D:\OSPanel\domains\COMPANY\library\v2\bitrix\b24.php',
    default => '/home/bitrix/www/local/library/v2/bitrix/b24.php',
};
```

## Основные функции

### getData
**Описание**: Получает данные, отправленные в скрипт, и преобразует их из JSON в массив. В случае ошибки логируется сообщение.

```php
use logger\Logger;function getData(): mixed
{
    $rawPostData = file_get_contents('php://input');
    $data = json_decode($rawPostData, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        Logger::log('Ошибка декодирования JSON: ' . json_last_error_msg(), 'error');
        sendResponse(false, "Ошибка по данным", ["data" => $rawPostData], 400, true);
    }

    return $data;
}
```

### getClient
**Описание**: Ищет клиента по телефону или ИИН через Bitrix API. Логирует ошибки и успешные поиски.

```php
use logger\Logger;function getClient(string $phone, string $iin, BitrixApi $handler): ?array
{
    // Проверка пустых значений
    if (empty($iin) && empty($phone)) {
        Logger::log("Пустые данные при запросе клиента", 'error');
        http_response_code(400);
        echo json_encode(["error" => "Пустые данные"]);
        return null;
    }

    // Очистка номера телефона от лишних символов
    $phone = preg_replace('/\D/', '', $phone);
    $phoneClients = [];

    // Поиск по телефону
    if (!empty($phone)) {
        if (!isValidPhone($phone)) {
            Logger::log("Некорректный телефон: $phone", 'error');
            http_response_code(400);
            echo json_encode(["error" => "Некорректный телефон"]);
            return null;
        }

        $phoneVariants = formatPhoneVariants($phone);

        foreach ($phoneVariants as $variant) {
            usleep(500_000);
            $result = $handler->searchClient(['PHONE' => $variant]);
            if (is_array($result)) {
                $phoneClients = array_merge($phoneClients, $result);
            }
        }
    }

    $iinClients = [];

    // Поиск по ИИН
    if (!empty($iin)) {
        if (!isValidIIN($iin)) {
            Logger::log("Некорректный ИИН: $iin", 'error');
            http_response_code(400);
            echo json_encode(["error" => "Некорректный ИИН"]);
            return null;
        }

        $iinClients = $handler->searchClient(['UF_CRM_1554290627253' => $iin]) ?: [];
    }

    // Если клиентов не найдено
    if (empty($iinClients) && empty($phoneClients)) {
        Logger::log("Клиенты не найдены [Телефон: $phone, ИИН: $iin]", 'error');
        http_response_code(404);
        echo json_encode(["message" => "Клиенты не найдены"]);
        return null;
    }

    // Уникальные клиенты
    $clients = array_merge($iinClients, $phoneClients);
    $uniqueClients = array_unique($clients, SORT_REGULAR);

    Logger::log("Найдены клиенты по телефону или ИИН [Телефон: $phone, ИИН: $iin]", 'success');
    return $uniqueClients;
}
```

### formatPhoneVariants
**Описание**: Формирует варианты телефонного номера для поиска по базе.

```php
function formatPhoneVariants(string $phone): array
{
    if (strlen($phone) === 11 && ($phone[0] === '7' || $phone[0] === '8')) {
        $phone = substr($phone, 1);
    } elseif (strlen($phone) === 12 && $phone[0] === '7') {
        $phone = substr($phone, 1);
    }

    return [
        '7' . $phone,
        '8' . $phone,
        '+7' . $phone,
    ];
}
```

### splitFullName
**Описание**: Разделяет ФИО на части (фамилию, имя и отчество).

```php
function splitFullName(string $fullName): array
{
    $parts = explode(' ', $fullName);
    $parts = array_pad($parts, 3, '');

    return [
        'lastName' => $parts[0],
        'firstName' => $parts[1],
        'middleName' => $parts[2],
    ];
}
```

### combineFullName
**Описание**: Объединяет фамилию, имя и отчество в строку.

```php
function combineFullName(string $lastName, string $firstName, string $middleName = ''): string
{
    return trim("$lastName $firstName $middleName");
}
```

### isValidIIN
**Описание**: Проверяет корректность ИИН.

```php
function isValidIIN(string $iin): bool
{
    return preg_match('/^\d{12}$/', $iin) === 1;
}
```

### isValidPhone
**Описание**: Проверяет корректность телефонного номера.

```php
function isValidPhone(string $phone): bool
{
    return preg_match('/^\d{10,12}$/', $phone) === 1;
}
```

### sendResponse
**Описание**: Отправляет ответ на запрос в формате JSON и завершает скрипт при необходимости.

```php
use logger\Logger;function sendResponse(
    bool $success,
    ?string $message = null,
    ?array $data = null,
    ?int $errorCode = null,
    bool $terminate = false
): void {
    header('Content-Type: application/json; charset=utf-8');

    if ($errorCode !== null) {
        http_response_code($errorCode);
    }

    $response = ['success' => $success];

    if ($message !== null) {
        $response['message'] = $message;
    }

    if ($data !== null) {
        $response['data'] = $data;
    }

    echo json_encode($response);

    if ($terminate) {
        Logger::log('Завершение выполнения скрипта после отправки ответа', 'info');
        exit();
    }
}
```

## Логирование
В скрипте используется логирование с помощью класса `Logger`. Для записи логов используется метод `Logger::log(message, type)`, где `type` может быть: `error`, `info`, или `success`.

Примеры логов:
- Логирование ошибок при некорректных данных:
  ```php
  Logger::log("Некорректный телефон: $phone", 'error');
  ```
- Логирование успешных операций:
  ```php
  Logger::log("Найдены клиенты по телефону или ИИН [Телефон: $phone, ИИН: $iin]", 'success');
  ```

## Информация о ревизии
- **Автор**: Мухамедшин Арсений
- **Контакты**: [t.me/n00rd1](https://t.me/n00rd1), mukhamedshin13@gmail.com
- **Дата последней ревизии**: 2024-09-19
- **Версия PHP**: PHP 8.2
