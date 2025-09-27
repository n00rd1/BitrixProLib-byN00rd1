<?php

declare(strict_types=1);

namespace BitrixProLib\MyStore;

use BitrixProLib\Logger\Logger;
use BitrixProLib\MyStore\Exceptions\MyStoreException;
use BitrixProLib\MyStore\Exceptions\TransportException;

/**
 * Клиент API MyStore (МойСклад)
 * 
 * Обеспечивает комплексную интеграцию с системой управления складом MyStore.
 * Поддерживает контакты, товары, заказы, склады и многое другое.
 * 
 * @package BitrixProLib\MyStore
 * @author N00rd1
 * @version 2.0.0
 */
class MyStoreClient
{
    private const BASE_URL = 'https://api.moysklad.ru/api/remap/1.2';
    
    private string $login;
    private string $password;
    private ?string $token = null;
    private int $tokenExpiresAt = 0;
    private ?array $metadataCache = null;
    private int $maxRetries;
    private int $retryDelay;
    private string $logDirectory;
    private array $defaultHeaders;

    /**
     * Конструктор
     * 
     * @param string $login Логин MyStore
     * @param string $password Пароль MyStore
     * @param string $logDirectory Директория для файлов логов
     * @param int $maxRetries Максимальное количество попыток повтора
     * @param int $retryDelay Задержка между попытками в микросекундах
     */
    public function __construct(
        string $login,
        string $password,
        string $logDirectory = '',
        int $maxRetries = 3,
        int $retryDelay = 500_000
    ) {
        $this->login = trim($login);
        $this->password = trim($password);
        
        if ($this->login === '' || $this->password === '') {
            throw new MyStoreException('Пустые учетные данные MyStore');
        }
        
        $this->maxRetries = $maxRetries;
        $this->retryDelay = $retryDelay;
        
        // Устанавливаем директорию логов по умолчанию, если не указана
        $this->logDirectory = $logDirectory ?: $this->getDefaultLogDirectory();
        
        // Инициализируем логгер
        Logger::setLogDirectory($this->logDirectory);
        
        // Устанавливаем заголовки по умолчанию
        $this->defaultHeaders = [
            'Content-Type: application/json',
            'Accept-Encoding: gzip',
            'User-Agent: BitrixProLib/2.0.0'
        ];
        
        // Аутентификация
        $this->authenticate();
    }

    /**
     * Выполнение API запроса к MyStore
     * 
     * @param string $method HTTP метод
     * @param string $path Путь API
     * @param array $data Данные запроса
     * @return array Ответ API
     * @throws MyStoreException|TransportException
     */
    private function request(string $method, string $path, array $data = []): array
    {
        $this->ensureToken();
        $url = self::BASE_URL . $path;
        
        for ($attempt = 0; $attempt < $this->maxRetries; $attempt++) {
            try {
                $curl = curl_init();
                
                $headers = array_merge($this->defaultHeaders, [
                    'Authorization: Bearer ' . $this->token
                ]);
                
                curl_setopt_array($curl, [
                    CURLOPT_URL => $url,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_HTTPHEADER => $headers,
                    CURLOPT_ENCODING => '', // Включаем автоматическую распаковку
                    CURLOPT_TIMEOUT => 30,
                    CURLOPT_CONNECTTIMEOUT => 10,
                    CURLOPT_SSL_VERIFYPEER => false,
                ]);
                
                if ($method === 'GET' && $data) {
                    $url .= '?' . http_build_query($data);
                    curl_setopt($curl, CURLOPT_URL, $url);
                } elseif ($method === 'POST') {
                    curl_setopt($curl, CURLOPT_POST, true);
                    curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data, JSON_UNESCAPED_UNICODE));
                } elseif ($method === 'PUT') {
                    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'PUT');
                    curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data, JSON_UNESCAPED_UNICODE));
                } elseif ($method === 'DELETE') {
                    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'DELETE');
                }
                
                $response = curl_exec($curl);
                $curlError = curl_errno($curl);
                $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
                curl_close($curl);
                
                if ($curlError) {
                    throw new TransportException('Ошибка CURL: ' . curl_strerror($curlError));
                }
                
                if ($httpCode < 200 || $httpCode >= 300) {
                    throw new TransportException("HTTP ошибка: {$httpCode}", $httpCode, null, $httpCode, $response);
                }
                
                $responseData = json_decode($response, true);
                
                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new MyStoreException('Некорректный JSON ответ: ' . json_last_error_msg());
                }
                
                Logger::log("API вызов MyStore успешен: {$method} {$path}", 'request');
                return $responseData;
                
            } catch (MyStoreException|TransportException $e) {
                Logger::log("API вызов MyStore неудачен (попытка " . ($attempt + 1) . "): " . $e->getMessage(), 'error');
                
                if ($attempt === $this->maxRetries - 1) {
                    throw $e;
                }
                
                usleep($this->retryDelay);
            }
        }
        
        throw new MyStoreException('Превышено максимальное количество попыток');
    }

    /**
     * Обеспечение наличия валидного токена
     * 
     * @return void
     * @throws MyStoreException|TransportException
     */
    private function ensureToken(): void
    {
        if ($this->token === null || time() >= $this->tokenExpiresAt - 60) {
            $this->authenticate();
        }
    }

    /**
     * Аутентификация и получение токена доступа
     * 
     * @return void
     * @throws MyStoreException|TransportException
     */
    private function authenticate(): void
    {
        $url = self::BASE_URL . '/security/token';
        $basic = base64_encode($this->login . ':' . $this->password);
        
        $headers = array_merge($this->defaultHeaders, [
            'Authorization: Basic ' . $basic
        ]);
        
        try {
            $curl = curl_init($url);
            curl_setopt_array($curl, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode(['_dummy' => true], JSON_UNESCAPED_UNICODE),
                CURLOPT_TIMEOUT => 30,
                CURLOPT_CONNECTTIMEOUT => 10,
                CURLOPT_SSL_VERIFYPEER => false,
            ]);
            
            $response = curl_exec($curl);
            $error = curl_error($curl);
            $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            curl_close($curl);
            
            if ($error) {
                throw new TransportException("Ошибка CURL аутентификации: {$error}");
            }
            
            if ($httpCode !== 200) {
                throw new TransportException("HTTP ошибка аутентификации: {$httpCode}", $httpCode, null, $httpCode, $response);
            }
            
            $data = json_decode($response, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new MyStoreException('Некорректный JSON в ответе аутентификации: ' . json_last_error_msg());
            }
            
            if (empty($data['access_token'])) {
                throw new MyStoreException('Некорректный ответ аутентификации');
            }
            
            $this->token = (string) $data['access_token'];
            $this->tokenExpiresAt = time() + 6000; // 100 минут
            
            Logger::log('Аутентификация MyStore успешна', 'success');
            
        } catch (MyStoreException|TransportException $e) {
            Logger::log('Аутентификация MyStore неудачна: ' . $e->getMessage(), 'error');
            throw $e;
        }
    }

    // ==================== МЕТОДЫ ДЛЯ КОНТАКТОВ ====================

    /**
     * Получение контактов с пагинацией
     * 
     * @param int $limit Лимит
     * @param int $offset Смещение
     * @return array Список контактов
     * @throws MyStoreException|TransportException
     */
    public function getContacts(int $limit = 100, int $offset = 0): array
    {
        return $this->request('GET', '/entity/contact', ['limit' => $limit, 'offset' => $offset]);
    }

    /**
     * Получение контакта по ID
     * 
     * @param string $id ID контакта
     * @return array Данные контакта
     * @throws MyStoreException|TransportException
     */
    public function getContact(string $id): array
    {
        return $this->request('GET', "/entity/contact/{$id}");
    }

    /**
     * Создание нового контакта
     * 
     * @param array $data Данные контакта
     * @return array Созданный контакт
     * @throws MyStoreException|TransportException
     */
    public function createContact(array $data): array
    {
        $result = $this->request('POST', '/entity/contact', $data);
        Logger::log("Контакт успешно создан", 'creation');
        return $result;
    }

    /**
     * Обновление контакта
     * 
     * @param string $id ID контакта
     * @param array $data Данные контакта
     * @return array Обновленный контакт
     * @throws MyStoreException|TransportException
     */
    public function updateContact(string $id, array $data): array
    {
        $result = $this->request('PUT', "/entity/contact/{$id}", $data);
        Logger::log("Контакт успешно обновлен: {$id}", 'update');
        return $result;
    }

    /**
     * Удаление контакта
     * 
     * @param string $id ID контакта
     * @return array Результат удаления
     * @throws MyStoreException|TransportException
     */
    public function deleteContact(string $id): array
    {
        $result = $this->request('DELETE', "/entity/contact/{$id}");
        Logger::log("Контакт успешно удален: {$id}", 'success');
        return $result;
    }

    // ==================== МЕТОДЫ ДЛЯ ТОВАРОВ ====================

    /**
     * Получение товаров с пагинацией
     * 
     * @param int $limit Лимит
     * @param int $offset Смещение
     * @return array Список товаров
     * @throws MyStoreException|TransportException
     */
    public function getProducts(int $limit = 100, int $offset = 0): array
    {
        return $this->request('GET', '/entity/product', ['limit' => $limit, 'offset' => $offset]);
    }

    /**
     * Получение товара по ID
     * 
     * @param string $id ID товара
     * @return array Данные товара
     * @throws MyStoreException|TransportException
     */
    public function getProduct(string $id): array
    {
        return $this->request('GET', "/entity/product/{$id}");
    }

    /**
     * Создание нового товара
     * 
     * @param array $data Данные товара
     * @return array Созданный товар
     * @throws MyStoreException|TransportException
     */
    public function createProduct(array $data): array
    {
        $result = $this->request('POST', '/entity/product', $data);
        Logger::log("Товар успешно создан", 'creation');
        return $result;
    }

    /**
     * Обновление товара
     * 
     * @param string $id ID товара
     * @param array $data Данные товара
     * @return array Обновленный товар
     * @throws MyStoreException|TransportException
     */
    public function updateProduct(string $id, array $data): array
    {
        $result = $this->request('PUT', "/entity/product/{$id}", $data);
        Logger::log("Товар успешно обновлен: {$id}", 'update');
        return $result;
    }

    /**
     * Удаление товара
     * 
     * @param string $id ID товара
     * @return array Результат удаления
     * @throws MyStoreException|TransportException
     */
    public function deleteProduct(string $id): array
    {
        $result = $this->request('DELETE', "/entity/product/{$id}");
        Logger::log("Товар успешно удален: {$id}", 'success');
        return $result;
    }

    // ==================== МЕТОДЫ ДЛЯ ЗАКАЗОВ ПОКУПАТЕЛЕЙ ====================

    /**
     * Получение заказов покупателей с пагинацией
     * 
     * @param int $limit Лимит
     * @param int $offset Смещение
     * @return array Список заказов покупателей
     * @throws MyStoreException|TransportException
     */
    public function getCustomerOrders(int $limit = 100, int $offset = 0): array
    {
        return $this->request('GET', '/entity/customerorder', ['limit' => $limit, 'offset' => $offset]);
    }

    /**
     * Получение заказа покупателя по ID
     * 
     * @param string $id ID заказа
     * @return array Данные заказа
     * @throws MyStoreException|TransportException
     */
    public function getCustomerOrder(string $id): array
    {
        return $this->request('GET', "/entity/customerorder/{$id}");
    }

    /**
     * Создание нового заказа покупателя
     * 
     * @param array $data Данные заказа
     * @return array Созданный заказ
     * @throws MyStoreException|TransportException
     */
    public function createCustomerOrder(array $data): array
    {
        $result = $this->request('POST', '/entity/customerorder', $data);
        Logger::log("Заказ покупателя успешно создан", 'creation');
        return $result;
    }

    /**
     * Обновление заказа покупателя
     * 
     * @param string $id ID заказа
     * @param array $data Данные заказа
     * @return array Обновленный заказ
     * @throws MyStoreException|TransportException
     */
    public function updateCustomerOrder(string $id, array $data): array
    {
        $result = $this->request('PUT', "/entity/customerorder/{$id}", $data);
        Logger::log("Заказ покупателя успешно обновлен: {$id}", 'update');
        return $result;
    }

    /**
     * Удаление заказа покупателя
     * 
     * @param string $id ID заказа
     * @return array Результат удаления
     * @throws MyStoreException|TransportException
     */
    public function deleteCustomerOrder(string $id): array
    {
        $result = $this->request('DELETE', "/entity/customerorder/{$id}");
        Logger::log("Заказ покупателя успешно удален: {$id}", 'success');
        return $result;
    }

    // ==================== МЕТОДЫ ДЛЯ СКЛАДОВ ====================

    /**
     * Получение складов с пагинацией
     * 
     * @param int $limit Лимит
     * @param int $offset Смещение
     * @return array Список складов
     * @throws MyStoreException|TransportException
     */
    public function getWarehouses(int $limit = 100, int $offset = 0): array
    {
        return $this->request('GET', '/entity/store', ['limit' => $limit, 'offset' => $offset]);
    }

    /**
     * Получение склада по ID
     * 
     * @param string $id ID склада
     * @return array Данные склада
     * @throws MyStoreException|TransportException
     */
    public function getWarehouse(string $id): array
    {
        return $this->request('GET', "/entity/store/{$id}");
    }

    /**
     * Получение остатков по складу
     * 
     * @param string $storeId ID склада
     * @return array Остатки по складу
     * @throws MyStoreException|TransportException
     */
    public function getStoreStocks(string $storeId): array
    {
        return $this->request('GET', '/report/stock/bystore', ['store' => $storeId]);
    }

    // ==================== МЕТОДЫ ДЛЯ СОТРУДНИКОВ ====================

    /**
     * Получение сотрудников с пагинацией
     * 
     * @param int $limit Лимит
     * @param int $offset Смещение
     * @return array Список сотрудников
     * @throws MyStoreException|TransportException
     */
    public function getEmployees(int $limit = 100, int $offset = 0): array
    {
        return $this->request('GET', '/entity/employee', ['limit' => $limit, 'offset' => $offset]);
    }

    /**
     * Получение сотрудника по ID
     * 
     * @param string $id ID сотрудника
     * @return array Данные сотрудника
     * @throws MyStoreException|TransportException
     */
    public function getEmployee(string $id): array
    {
        return $this->request('GET', "/entity/employee/{$id}");
    }

    // ==================== МЕТОДЫ ДЛЯ КОНТРАГЕНТОВ ====================

    /**
     * Получение контрагентов с пагинацией
     * 
     * @param int $limit Лимит
     * @param int $offset Смещение
     * @return array Список контрагентов
     * @throws MyStoreException|TransportException
     */
    public function getCounterparties(int $limit = 100, int $offset = 0): array
    {
        return $this->request('GET', '/entity/counterparty', ['limit' => $limit, 'offset' => $offset]);
    }

    // ==================== МЕТОДЫ ДЛЯ МЕТАДАННЫХ ====================

    /**
     * Получение метаданных сущности
     * 
     * @param string $entity Название сущности
     * @return array Метаданные
     * @throws MyStoreException|TransportException
     */
    public function getMetadata(string $entity): array
    {
        $entity = filter_var($entity, FILTER_SANITIZE_STRING);
        
        if ($this->metadataCache[$entity] ?? null) {
            return $this->metadataCache[$entity];
        }
        
        $data = $this->request('GET', "/entity/{$entity}/metadata");
        return $this->metadataCache[$entity] = $data;
    }

    // ==================== ВСПОМОГАТЕЛЬНЫЕ МЕТОДЫ ====================

    /**
     * Получение директории логов по умолчанию в зависимости от ОС
     * 
     * @return string Директория логов по умолчанию
     */
    private function getDefaultLogDirectory(): string
    {
        return match (PHP_OS_FAMILY) {
            'Windows' => 'D:\OSPanel\domains\company\library\v2\logs',
            default => '/home/bitrix/www/local/library/v2/logs',
        };
    }

    /**
     * Установка пользовательских заголовков для API запросов
     * 
     * @param array $headers Пользовательские заголовки
     * @return void
     */
    public function setCustomHeaders(array $headers): void
    {
        $this->defaultHeaders = array_merge($this->defaultHeaders, $headers);
    }

    /**
     * Получение текущего URL API
     * 
     * @return string Текущий URL API
     */
    public function getApiUrl(): string
    {
        return self::BASE_URL;
    }

    /**
     * Установка конфигурации повторов
     * 
     * @param int $maxRetries Максимальное количество попыток
     * @param int $retryDelay Задержка между попытками в микросекундах
     * @return void
     */
    public function setRetryConfig(int $maxRetries, int $retryDelay): void
    {
        $this->maxRetries = $maxRetries;
        $this->retryDelay = $retryDelay;
    }

    /**
     * Получение времени истечения текущего токена
     * 
     * @return int Временная метка истечения токена
     */
    public function getTokenExpiration(): int
    {
        return $this->tokenExpiresAt;
    }
}