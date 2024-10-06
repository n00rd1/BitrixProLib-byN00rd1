
# Документация класса Logger

## Краткое описание

Класс `Logger` предоставляет функциональность для логирования сообщений в PHP-приложениях. Он автоматически управляет директориями логов, организует логи по типам (`success`, `errors`, `creation`, `update`, `request`) с поддиректориями по датам, а также выполняет ротацию логов, удаляя файлы, старше установленного срока (по умолчанию — 60 дней).

## Оглавление
1. [Пример использования](#1-пример-использования)
2. [Документация функций](#2-документация-функций)
   - [log()](#logstring-message-string-type-void)
   - [setLogDirectory()](#setlogdirectorystring-logdirectory-void)
   - [setLogRetentionDays()](#setlogretentiondaysint-days-void)
   - [cleanOldLogs()](#cleanoldlogsvoid)
3. [Примеры входных и выходных данных](#3-примеры-входных-и-выходных-данных)
4. [Дата ревизии и информация о разработчике](#4-дата-ревизии-и-информация-о-разработчике)
5. [Версия PHP и зависимости](#5-версия-php-и-зависимости)

---

## 1. Пример использования

```php
// Устанавливаем директорию для логов (опционально, по умолчанию 'D:/OSPanel/domains/COMPANY/library/logs')
use logger\Logger;Logger::setLogDirectory('D:\OSPanel\domains\COMPANY\library\logs');

// Логируем сообщение об успешной операции
Logger::log('Операция выполнена успешно.', 'success');

// Логируем сообщение об ошибке
Logger::log('Произошла ошибка при обработке.', 'error');

// Логируем сообщение о запросе
Logger::log('Получены данные от пользователя.', 'request');
```

## 2. Документация функций

### log()

```php
public static function log(string $message, string $type = 'request'): void
```

##### Описание:

Записывает сообщение в лог-файл. Файл создается в зависимости от типа логов (успех, ошибка, создание, обновление или запрос). Директория и файл создаются автоматически, если они не существуют.

##### Параметры:

- `string $message` - сообщение для записи.
- `string $type` - тип лога (`request`, `error`, `success`, `creation`, `update`).

##### Пример:

```php
use logger\Logger;Logger::log('Произошла ошибка при подключении к БД.', 'error');
```

### setLogDirectory()

```php
public static function setLogDirectory(string $logDirectory): void
```

##### Описание:

Устанавливает основную директорию для хранения логов. Если не задано, по умолчанию используется 'D:/OSPanel/domains/TAS/library/logs'.

##### Параметры:

- `string $logDirectory` - путь к основной директории логов.

##### Пример:

```php
use logger\Logger;Logger::setLogDirectory('D:/project/custom_logs/');
```

### setLogRetentionDays()

```php
public static function setLogRetentionDays(int $days): void
```

##### Описание:

Устанавливает срок хранения логов в днях. По умолчанию — 60 дней.

##### Параметры:

- `int $days` - количество дней, после которых логи будут удаляться.

##### Пример:

```php
use logger\Logger;Logger::setLogRetentionDays(30); // Хранить логи в течение 30 дней
```

### cleanOldLogs()

```php
private static function cleanOldLogs(): void
```

##### Описание:

Удаляет файлы логов, которым более установленного срока хранения (по умолчанию 60 дней) из всех директорий логов.

---

## 3. Примеры входных и выходных данных

### Пример 1: Логирование успешного сообщения

**Вход:**

```php
use logger\Logger;Logger::log('Регистрация пользователя завершена.', 'success');
```

**Выход:**

Создается файл лога по пути:

```bash
D:/project/logs/success/01_10_2023/success.log
```

С содержимым:

```text
[14:23:45] Регистрация пользователя завершена.
```

---

### Пример 2: Логирование сообщения об ошибке

**Вход:**

```php
use logger\Logger;Logger::log('Ошибка подключения к базе данных.', 'error');
```

**Выход:**

Создается файл лога по пути:

```bash
D:/project/logs/errors/01_10_2023/error.log
```

С содержимым:

```text
[14:25:00] Ошибка подключения к базе данных.
```

---

### Пример 3: Логирование запроса

**Вход:**

```php
use logger\Logger;Logger::log('Получены данные POST от /api/login', 'request');
```

**Выход:**

Создается файл лога по пути:

```bash
D:/project/logs/requests/01_10_2023/request.log
```

С содержимым:

```text
[14:26:30] Получены данные POST от /api/login
```

---

## 4. Дата ревизии и информация о разработчике

- **Дата ревизии**: 2024-09-26
- **Разработчик**: Мухамедшин Арсений, [Telegram](https://t.me/n00rd1), [Email](mailto:mukhamedshin13@gmail.com)

---

## 5. Версия PHP и зависимости

- **PHP**: >= 8.0
- **Зависимости**: нет
