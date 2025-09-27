<?php

declare(strict_types=1);

namespace BitrixProLib\Bitrix;

use BitrixProLib\Logger\Logger;
use BitrixProLib\Bitrix\Exceptions\BitrixException;
use BitrixProLib\Bitrix\Exceptions\TransportException;

/**
 * Клиент для работы с Bitrix24 REST API
 * 
 * Обеспечивает комплексную интеграцию с CRM Bitrix24 через REST API.
 * Поддерживает работу со сделками, контактами, компаниями и генерацией документов.
 * 
 * @package BitrixProLib\Bitrix
 * @author N00rd1
 * @version 2.0.0
 */
class BitrixApi
{
    private string $apiUrl;
    private int $maxRetries;
    private int $retryDelay;
    private string $logDirectory;
    private array $defaultHeaders;

    /**
     * Конструктор
     * 
     * @param string $authToken Токен авторизации для Bitrix24
     * @param string $apiUrl Базовый URL API (по умолчанию: https://bitrix.domain.com/rest/main_user/)
     * @param string $logDirectory Директория для файлов логов
     * @param int $maxRetries Максимальное количество попыток повтора
     * @param int $retryDelay Задержка между попытками в микросекундах
     */
    public function __construct(
        private string $authToken,
        string $apiUrl = 'https://bitrix.domain.com/rest/main_user/',
        string $logDirectory = '',
        int $maxRetries = 3,
        int $retryDelay = 500_000
    ) {
        $this->apiUrl = rtrim($apiUrl, '/') . '/' . $authToken . '/';
        $this->maxRetries = $maxRetries;
        $this->retryDelay = $retryDelay;
        
        // Устанавливаем директорию логов по умолчанию, если не указана
        $this->logDirectory = $logDirectory ?: $this->getDefaultLogDirectory();
        
        // Инициализируем логгер
        Logger::setLogDirectory($this->logDirectory);
        
        // Устанавливаем заголовки по умолчанию
        $this->defaultHeaders = [
            'Content-Type: application/x-www-form-urlencoded',
            'User-Agent: BitrixProLib/2.0.0'
        ];
    }

    /**
     * Выполнение API запроса к Bitrix24
     * 
     * @param string $method Название метода API
     * @param array $params Параметры запроса
     * @return array|null Ответ API или null при ошибке
     * @throws BitrixException|TransportException
     */
    private function callBitrixApi(string $method, array $params = []): ?array
    {
        $url = $this->apiUrl . $method;
        $queryData = http_build_query($params);
        
        for ($attempt = 0; $attempt < $this->maxRetries; $attempt++) {
            try {
                $curl = curl_init();
                
                curl_setopt_array($curl, [
                    CURLOPT_URL => $url,
                    CURLOPT_POST => true,
                    CURLOPT_POSTFIELDS => $queryData,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_HEADER => false,
                    CURLOPT_HTTPHEADER => $this->defaultHeaders,
                    CURLOPT_SSL_VERIFYPEER => false,
                    CURLOPT_TIMEOUT => 30,
                    CURLOPT_CONNECTTIMEOUT => 10,
                ]);
                
                $response = curl_exec($curl);
                
                if ($curlError = curl_error($curl)) {
                    throw new TransportException('Ошибка CURL: ' . $curlError);
                }
                
                $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
                curl_close($curl);
                
                if ($httpCode !== 200) {
                    throw new TransportException("HTTP ошибка: {$httpCode}");
                }
                
                $responseData = json_decode($response, true);
                
                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new BitrixException('Некорректный JSON ответ: ' . json_last_error_msg());
                }
                
                // Проверяем ошибки API Bitrix
                if (isset($responseData['error'])) {
                    $errorMessage = $responseData['error_description'] ?? $responseData['error'];
                    throw new BitrixException("Ошибка API Bitrix: {$errorMessage}");
                }
                
                Logger::log("API вызов успешен: {$method}", 'request');
                return $responseData;
                
            } catch (BitrixException|TransportException $e) {
                Logger::log("API вызов неудачен (попытка " . ($attempt + 1) . "): " . $e->getMessage(), 'error');
                
                if ($attempt === $this->maxRetries - 1) {
                    throw $e;
                }
                
                usleep($this->retryDelay);
            }
        }
        
        return null;
    }

    // ==================== МЕТОДЫ ДЛЯ СДЕЛОК ====================

    /**
     * Создание новой сделки
     * 
     * @param array $fields Поля сделки
     * @return int|null ID сделки или null при ошибке
     * @throws BitrixException|TransportException
     */
    public function createDeal(array $fields): ?int
    {
        $response = $this->callBitrixApi('crm.deal.add', ['fields' => $fields]);
        
        if ($response && isset($response['result'])) {
            $dealId = (int) $response['result'];
            Logger::log("Сделка успешно создана с ID: {$dealId}", 'creation');
            return $dealId;
        }
        
        Logger::log('Не удалось создать сделку', 'error');
        return null;
    }

    /**
     * Обновление существующей сделки
     * 
     * @param int $dealId ID сделки
     * @param array $fields Поля для обновления
     * @return bool Статус успеха
     * @throws BitrixException|TransportException
     */
    public function updateDeal(int $dealId, array $fields): bool
    {
        $response = $this->callBitrixApi('crm.deal.update', [
            'id' => $dealId,
            'fields' => $fields
        ]);
        
        if ($response && $response['result'] === true) {
            Logger::log("Сделка успешно обновлена: {$dealId}", 'update');
            return true;
        }
        
        Logger::log("Не удалось обновить сделку: {$dealId}", 'error');
        return false;
    }

    /**
     * Удаление сделки
     * 
     * @param int $dealId ID сделки
     * @return bool Статус успеха
     * @throws BitrixException|TransportException
     */
    public function deleteDeal(int $dealId): bool
    {
        $response = $this->callBitrixApi('crm.deal.delete', ['id' => $dealId]);
        
        if ($response && $response['result'] === true) {
            Logger::log("Сделка успешно удалена: {$dealId}", 'success');
            return true;
        }
        
        Logger::log("Не удалось удалить сделку: {$dealId}", 'error');
        return false;
    }

    /**
     * Получение сделки по ID
     * 
     * @param int $dealId ID сделки
     * @return array|null Данные сделки или null при ошибке
     * @throws BitrixException|TransportException
     */
    public function getDealById(int $dealId): ?array
    {
        $response = $this->callBitrixApi('crm.deal.get', ['id' => $dealId]);
        
        if ($response && isset($response['result'])) {
            return $response['result'];
        }
        
        Logger::log("Не удалось получить сделку: {$dealId}", 'error');
        return null;
    }

    /**
     * Получение списка сделок с фильтрацией и пагинацией
     * 
     * @param array $filter Условия фильтрации
     * @param array $select Поля для выборки
     * @param int $start Начальная позиция
     * @return array|null Список сделок или null при ошибке
     * @throws BitrixException|TransportException
     */
    public function listDeals(array $filter = [], array $select = [], int $start = 0): ?array
    {
        $params = [
            'filter' => $filter,
            'select' => $select,
            'start' => $start
        ];
        
        $response = $this->callBitrixApi('crm.deal.list', $params);
        
        if ($response && isset($response['result'])) {
            return $response['result'];
        }
        
        Logger::log('Не удалось получить список сделок', 'error');
        return null;
    }

    // ==================== МЕТОДЫ ДЛЯ КОНТАКТОВ ====================

    /**
     * Создание нового контакта
     * 
     * @param array $fields Поля контакта
     * @return int|null ID контакта или null при ошибке
     * @throws BitrixException|TransportException
     */
    public function createContact(array $fields): ?int
    {
        $response = $this->callBitrixApi('crm.contact.add', ['fields' => $fields]);
        
        if ($response && isset($response['result'])) {
            $contactId = (int) $response['result'];
            Logger::log("Контакт успешно создан с ID: {$contactId}", 'creation');
            return $contactId;
        }
        
        Logger::log('Не удалось создать контакт', 'error');
        return null;
    }

    /**
     * Обновление существующего контакта
     * 
     * @param int $contactId ID контакта
     * @param array $fields Поля для обновления
     * @return bool Статус успеха
     * @throws BitrixException|TransportException
     */
    public function updateContact(int $contactId, array $fields): bool
    {
        $response = $this->callBitrixApi('crm.contact.update', [
            'id' => $contactId,
            'fields' => $fields
        ]);
        
        if ($response && $response['result'] === true) {
            Logger::log("Контакт успешно обновлен: {$contactId}", 'update');
            return true;
        }
        
        Logger::log("Не удалось обновить контакт: {$contactId}", 'error');
        return false;
    }

    /**
     * Удаление контакта
     * 
     * @param int $contactId ID контакта
     * @return bool Статус успеха
     * @throws BitrixException|TransportException
     */
    public function deleteContact(int $contactId): bool
    {
        $response = $this->callBitrixApi('crm.contact.delete', ['id' => $contactId]);
        
        if ($response && $response['result'] === true) {
            Logger::log("Контакт успешно удален: {$contactId}", 'success');
            return true;
        }
        
        Logger::log("Не удалось удалить контакт: {$contactId}", 'error');
        return false;
    }

    /**
     * Получение контакта по ID
     * 
     * @param int $contactId ID контакта
     * @return array|null Данные контакта или null при ошибке
     * @throws BitrixException|TransportException
     */
    public function getContactById(int $contactId): ?array
    {
        $response = $this->callBitrixApi('crm.contact.get', ['id' => $contactId]);
        
        if ($response && isset($response['result'])) {
            return $response['result'];
        }
        
        Logger::log("Не удалось получить контакт: {$contactId}", 'error');
        return null;
    }

    /**
     * Получение списка контактов с фильтрацией и пагинацией
     * 
     * @param array $filter Условия фильтрации
     * @param array $select Поля для выборки
     * @param int $start Начальная позиция
     * @return array|null Список контактов или null при ошибке
     * @throws BitrixException|TransportException
     */
    public function listContacts(array $filter = [], array $select = [], int $start = 0): ?array
    {
        $params = [
            'filter' => $filter,
            'select' => $select,
            'start' => $start
        ];
        
        $response = $this->callBitrixApi('crm.contact.list', $params);
        
        if ($response && isset($response['result'])) {
            return $response['result'];
        }
        
        Logger::log('Не удалось получить список контактов', 'error');
        return null;
    }

    // ==================== МЕТОДЫ ДЛЯ КОМПАНИЙ ====================

    /**
     * Создание новой компании
     * 
     * @param array $fields Поля компании
     * @return int|null ID компании или null при ошибке
     * @throws BitrixException|TransportException
     */
    public function createCompany(array $fields): ?int
    {
        $response = $this->callBitrixApi('crm.company.add', ['fields' => $fields]);
        
        if ($response && isset($response['result'])) {
            $companyId = (int) $response['result'];
            Logger::log("Компания успешно создана с ID: {$companyId}", 'creation');
            return $companyId;
        }
        
        Logger::log('Не удалось создать компанию', 'error');
        return null;
    }

    /**
     * Обновление существующей компании
     * 
     * @param int $companyId ID компании
     * @param array $fields Поля для обновления
     * @return bool Статус успеха
     * @throws BitrixException|TransportException
     */
    public function updateCompany(int $companyId, array $fields): bool
    {
        $response = $this->callBitrixApi('crm.company.update', [
            'id' => $companyId,
            'fields' => $fields
        ]);
        
        if ($response && $response['result'] === true) {
            Logger::log("Компания успешно обновлена: {$companyId}", 'update');
            return true;
        }
        
        Logger::log("Не удалось обновить компанию: {$companyId}", 'error');
        return false;
    }

    /**
     * Получение списка компаний с фильтрацией и пагинацией
     * 
     * @param array $filter Условия фильтрации
     * @param array $select Поля для выборки
     * @param int $start Начальная позиция
     * @return array|null Список компаний или null при ошибке
     * @throws BitrixException|TransportException
     */
    public function listCompanies(array $filter = [], array $select = [], int $start = 0): ?array
    {
        $params = [
            'filter' => $filter,
            'select' => $select,
            'start' => $start
        ];
        
        $response = $this->callBitrixApi('crm.company.list', $params);
        
        if ($response && isset($response['result'])) {
            return $response['result'];
        }
        
        Logger::log('Не удалось получить список компаний', 'error');
        return null;
    }

    // ==================== ГЕНЕРАЦИЯ ДОКУМЕНТОВ ====================

    /**
     * Создание документа из шаблона
     * 
     * @param int $templateId ID шаблона
     * @param int $entityTypeId ID типа сущности
     * @param int $entityId ID сущности
     * @param array $values Значения для шаблона
     * @return array|null Данные документа или null при ошибке
     * @throws BitrixException|TransportException
     */
    public function createDocument(int $templateId, int $entityTypeId, int $entityId, array $values = []): ?array
    {
        $response = $this->callBitrixApi('crm.documentgenerator.document.add', [
            'templateId' => $templateId,
            'entityTypeId' => $entityTypeId,
            'entityId' => $entityId,
            'values' => $values
        ]);
        
        if ($response && isset($response['result']['document']['id'])) {
            $documentId = $response['result']['document']['id'];
            Logger::log("Документ успешно создан с ID: {$documentId}", 'creation');
            return $response['result']['document'];
        }
        
        Logger::log('Не удалось создать документ', 'error');
        return null;
    }

    /**
     * Включение публичной ссылки для документа
     * 
     * @param int $documentId ID документа
     * @return string|null Публичная ссылка или null при ошибке
     * @throws BitrixException|TransportException
     */
    public function enableDocumentPublicUrl(int $documentId): ?string
    {
        $response = $this->callBitrixApi('crm.documentgenerator.document.enablepublicurl', [
            'id' => $documentId,
            'status' => 1
        ]);
        
        if ($response && isset($response['result']['publicUrl'])) {
            $publicUrl = $response['result']['publicUrl'];
            Logger::log("Публичная ссылка включена для документа: {$documentId}", 'success');
            return $publicUrl;
        }
        
        Logger::log("Не удалось включить публичную ссылку для документа: {$documentId}", 'error');
        return null;
    }

    // ==================== ВСПОМОГАТЕЛЬНЫЕ МЕТОДЫ ====================

    /**
     * Получение директории логов по умолчанию в зависимости от ОС
     * 
     * @return string Путь к директории логов по умолчанию
     */
    private function getDefaultLogDirectory(): string
    {
        return match (PHP_OS_FAMILY) {
            'Windows' => 'D:\OSPanel\domains\company\library\v2\logs\\',
            default => '/home/bitrix/www/local/library/v2/logs/',
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
        return $this->apiUrl;
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
}