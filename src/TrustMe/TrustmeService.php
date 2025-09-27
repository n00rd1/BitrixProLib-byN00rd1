<?php

declare(strict_types=1);

namespace BitrixProLib\TrustMe;

use BitrixProLib\Logger\Logger;
use BitrixProLib\TrustMe\Exceptions\TrustmeException;
use BitrixProLib\TrustMe\Exceptions\TransportException;

/**
 * Интеграция с сервисом цифровых подписей TrustMe
 * 
 * Обеспечивает комплексную интеграцию с сервисом цифровых подписей TrustMe.
 * Поддерживает подписание документов через URL файла и кодирование Base64.
 * 
 * @package BitrixProLib\TrustMe
 * @author N00rd1
 * @version 2.0.0
 */
class TrustmeService
{
    private string $apiUrl;
    private string $authToken;
    private int $maxRetries;
    private int $retryDelay;
    private string $logDirectory;
    private array $defaultHeaders;

    /**
     * Конструктор
     * 
     * @param string $apiUrl Базовый URL API TrustMe
     * @param string $authToken Токен авторизации
     * @param string $logDirectory Директория для файлов логов
     * @param int $maxRetries Максимальное количество попыток повтора
     * @param int $retryDelay Задержка между попытками в микросекундах
     */
    public function __construct(
        string $apiUrl,
        string $authToken,
        string $logDirectory = '',
        int $maxRetries = 3,
        int $retryDelay = 500_000
    ) {
        $this->apiUrl = rtrim($apiUrl, '/') . '/';
        $this->authToken = $authToken;
        $this->maxRetries = $maxRetries;
        $this->retryDelay = $retryDelay;
        
        // Устанавливаем директорию логов по умолчанию, если не указана
        $this->logDirectory = $logDirectory ?: $this->getDefaultLogDirectory();
        
        // Инициализируем логгер
        Logger::setLogDirectory($this->logDirectory);
        
        // Устанавливаем заголовки по умолчанию
        $this->defaultHeaders = [
            'User-Agent: BitrixProLib/2.0.0'
        ];
    }

    /**
     * Выполнение API запроса к TrustMe
     * 
     * @param string $endpoint Конечная точка API
     * @param array|string $postData Данные запроса
     * @param string $contentType Тип контента (json или multipart)
     * @return array Ответ API
     * @throws TrustmeException|TransportException
     */
    private function callTrustmeApi(string $endpoint, array|string $postData, string $contentType = 'json'): array
    {
        $url = $this->apiUrl . ltrim($endpoint, '/');
        
        for ($attempt = 0; $attempt < $this->maxRetries; $attempt++) {
            try {
                $curl = curl_init();
                
                curl_setopt_array($curl, [
                    CURLOPT_URL => $url,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_POST => true,
                    CURLOPT_TIMEOUT => 60,
                    CURLOPT_CONNECTTIMEOUT => 10,
                    CURLOPT_SSL_VERIFYPEER => false,
                ]);
                
                // Устанавливаем заголовки и данные в зависимости от типа контента
                if ($contentType === 'multipart') {
                    curl_setopt($curl, CURLOPT_POSTFIELDS, $postData);
                    $headers = array_merge($this->defaultHeaders, [
                        "Authorization: {$this->authToken}"
                    ]);
                } else {
                    $jsonData = is_array($postData) ? json_encode($postData, JSON_UNESCAPED_UNICODE) : $postData;
                    curl_setopt($curl, CURLOPT_POSTFIELDS, $jsonData);
                    $headers = array_merge($this->defaultHeaders, [
                        'Content-Type: application/json',
                        "Authorization: {$this->authToken}"
                    ]);
                }
                
                curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
                
                $response = curl_exec($curl);
                
                if ($curlError = curl_error($curl)) {
                    throw new TransportException('Ошибка CURL: ' . $curlError);
                }
                
                $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
                curl_close($curl);
                
                if ($httpCode !== 200) {
                    throw new TransportException("HTTP ошибка: {$httpCode}", $httpCode, null, $httpCode, $response);
                }
                
                $responseData = json_decode($response, true);
                
                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new TrustmeException('Некорректный JSON ответ: ' . json_last_error_msg());
                }
                
                Logger::log("API вызов TrustMe успешен: {$endpoint}", 'request');
                return $responseData;
                
            } catch (TrustmeException|TransportException $e) {
                Logger::log("API вызов TrustMe неудачен (попытка " . ($attempt + 1) . "): " . $e->getMessage(), 'error');
                
                if ($attempt === $this->maxRetries - 1) {
                    throw $e;
                }
                
                usleep($this->retryDelay);
            }
        }
        
        throw new TrustmeException('Превышено максимальное количество попыток');
    }

    /**
     * Отправка документа на подписание через URL файла
     * 
     * @param string $fileUrl URL к файлу документа
     * @param string $companyName Название компании
     * @param string $phone Номер телефона
     * @param string $contractName Название договора
     * @param bool $kzBmg Флаг KZ BMG
     * @param bool $faceId Флаг Face ID
     * @param array $requisites Дополнительные реквизиты
     * @return array Результат подписания
     * @throws TrustmeException|TransportException
     */
    public function sendToSignWithFileUrl(
        string $fileUrl,
        string $companyName,
        string $phone,
        string $contractName = "Технические требования",
        bool $kzBmg = false,
        bool $faceId = false,
        array $requisites = []
    ): array {
        // Подготавливаем данные запроса
        $requestData = [
            "downloadURL" => $fileUrl,
            "KzBmg" => $kzBmg,
            "FaceId" => $faceId,
            "requisites" => $this->prepareRequisites($companyName, $phone, $requisites),
            "contractName" => $contractName,
        ];
        
        $response = $this->callTrustmeApi('UploadWithFileURL', $requestData);
        
        if (($response['status'] ?? '') === 'Ok') {
            $data = $response['data'] ?? [];
            $result = [
                'url' => $data['url'] ?? null,
                'document_id' => $data['document_id'] ?? null,
                'fileName' => $data['fileName'] ?? null,
                'status' => 'success'
            ];
            
            Logger::log("Документ успешно отправлен на подписание (FileURL): {$contractName}", 'success');
            return $result;
        }
        
        $errorText = $response['errorText'] ?? 'Неизвестная ошибка при отправке документа по URL';
        Logger::log("Ошибка TrustMe (FileURL): {$errorText}", 'error');
        throw new TrustmeException($errorText);
    }

    /**
     * Отправка документа на подписание через кодирование Base64
     * 
     * @param string $fileUrl URL к файлу документа
     * @param string $companyName Название компании
     * @param string $fio Полное имя
     * @param string $iin ИИН/БИН
     * @param string $phone Номер телефона
     * @param string $number Номер документа
     * @param string $info Дополнительная информация
     * @param string $contractName Название договора
     * @param string $fileExt Расширение файла
     * @return array Результат подписания
     * @throws TrustmeException|TransportException
     */
    public function sendToSignWithFileBase64(
        string $fileUrl,
        string $companyName,
        string $fio,
        string $iin,
        string $phone,
        string $number = "АВР-1",
        string $info = "Не задана",
        string $contractName = "Технические требования",
        string $fileExt = "pdf"
    ): array {
        // Конвертируем файл в Base64
        $base64file = $this->convertFileToBase64($fileUrl);
        
        // Подготавливаем детали
        $details = [
            "NumberDial" => $number,
            "KzBmg" => false,
            "FaceId" => false,
            "AdditionalInfo" => $info,
            "Requisites" => [
                [
                    "CompanyName" => $companyName,
                    "FIO" => $fio,
                    "IIN_BIN" => $iin,
                    "PhoneNumber" => $phone,
                ]
            ]
        ];
        
        // Подготавливаем данные multipart формы
        $requestData = [
            'FileBase64' => $base64file,
            'details' => json_encode($details, JSON_UNESCAPED_UNICODE),
            'contract_name' => $contractName
        ];
        
        $endpoint = "SendToSignBase64FileExt/" . $fileExt;
        $response = $this->callTrustmeApi($endpoint, $requestData, 'multipart');
        
        if (($response['status'] ?? '') === 'Ok') {
            $data = $response['data'] ?? [];
            $result = [
                'url' => $data['url'] ?? null,
                'document_id' => $data['document_id'] ?? null,
                'fileName' => $data['fileName'] ?? null,
                'status' => 'success'
            ];
            
            Logger::log("Документ успешно отправлен на подписание (Base64): {$contractName}", 'success');
            return $result;
        }
        
        $errorText = $response['errorText'] ?? 'Неизвестная ошибка при отправке Base64 документа';
        Logger::log("Ошибка TrustMe (Base64): {$errorText}", 'error');
        throw new TrustmeException($errorText);
    }

    /**
     * Получение статуса документа
     * 
     * @param string $documentId ID документа
     * @return array Статус документа
     * @throws TrustmeException|TransportException
     */
    public function getDocumentStatus(string $documentId): array
    {
        $response = $this->callTrustmeApi("GetDocumentStatus/{$documentId}", []);
        
        if (($response['status'] ?? '') === 'Ok') {
            return $response['data'] ?? [];
        }
        
        $errorText = $response['errorText'] ?? 'Неизвестная ошибка при получении статуса документа';
        throw new TrustmeException($errorText);
    }

    /**
     * Скачивание подписанного документа
     * 
     * @param string $documentId ID документа
     * @param string $savePath Путь для сохранения документа
     * @return bool Статус успеха
     * @throws TrustmeException|TransportException
     */
    public function downloadSignedDocument(string $documentId, string $savePath): bool
    {
        $response = $this->callTrustmeApi("DownloadSignedDocument/{$documentId}", []);
        
        if (($response['status'] ?? '') === 'Ok') {
            $downloadUrl = $response['data']['downloadUrl'] ?? null;
            
            if ($downloadUrl) {
                $fileContent = file_get_contents($downloadUrl);
                if ($fileContent !== false) {
                    $result = file_put_contents($savePath, $fileContent);
                    if ($result !== false) {
                        Logger::log("Подписанный документ успешно скачан: {$documentId}", 'success');
                        return true;
                    }
                }
            }
        }
        
        $errorText = $response['errorText'] ?? 'Неизвестная ошибка при скачивании документа';
        Logger::log("Ошибка TrustMe (Скачивание): {$errorText}", 'error');
        throw new TrustmeException($errorText);
    }

    /**
     * Конвертация файла в кодирование Base64
     * 
     * @param string $fileUrl URL файла
     * @return string Содержимое файла в кодировке Base64
     * @throws TrustmeException
     */
    private function convertFileToBase64(string $fileUrl): string
    {
        $content = @file_get_contents($fileUrl);
        if ($content === false) {
            throw new TrustmeException("Не удалось скачать файл по URL: {$fileUrl}");
        }
        
        // Проверяем, является ли это HTML (просмотрщик PDF Bitrix)
        if (stripos($content, '<html') !== false) {
            $pdfUrl = $this->extractPdfUrlFromBitrixHtml($content);
            if (!$pdfUrl) {
                throw new TrustmeException("Не удалось найти URL PDF в HTML из: {$fileUrl}");
            }
            
            $pdfUrl = $this->makeAbsoluteUrl($fileUrl, $pdfUrl);
            $pdfContent = @file_get_contents($pdfUrl);
            if ($pdfContent === false) {
                throw new TrustmeException("Не удалось скачать PDF по URL: {$pdfUrl}");
            }
            
            return base64_encode($pdfContent);
        }
        
        return base64_encode($content);
    }

    /**
     * Извлечение URL PDF из HTML просмотрщика Bitrix
     * 
     * @param string $html HTML содержимое
     * @return string|null URL PDF
     */
    private function extractPdfUrlFromBitrixHtml(string $html): ?string
    {
        if (preg_match('/window\.pdfJsFilePath\s*=\s*[\'"]([^\'"]+)[\'"]/', $html, $matches)) {
            return $matches[1];
        }
        return null;
    }

    /**
     * Создание абсолютного URL из относительного пути
     * 
     * @param string $baseUrl Базовый URL
     * @param string $relative Относительный путь
     * @return string Абсолютный URL
     */
    private function makeAbsoluteUrl(string $baseUrl, string $relative): string
    {
        if (preg_match('#^https?://#i', $relative)) {
            return $relative;
        }
        
        $parsed = parse_url($baseUrl);
        $scheme = $parsed['scheme'] ?? 'http';
        $host = $parsed['host'] ?? '';
        
        return $scheme . '://' . $host . $relative;
    }

    /**
     * Подготовка массива реквизитов
     * 
     * @param string $companyName Название компании
     * @param string $phone Номер телефона
     * @param array $additionalRequisites Дополнительные реквизиты
     * @return array Массив реквизитов
     */
    private function prepareRequisites(string $companyName, string $phone, array $additionalRequisites = []): array
    {
        $defaultRequisites = [
            [
                "CompanyName" => $companyName,
                "FIO" => "Аблязов Даулет Амирович",
                "IIN_BIN" => "654646546546",
                "PhoneNumber" => $phone,
            ]
        ];
        
        return array_merge($defaultRequisites, $additionalRequisites);
    }

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