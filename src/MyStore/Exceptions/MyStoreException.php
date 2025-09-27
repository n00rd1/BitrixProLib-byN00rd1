<?php

declare(strict_types=1);

namespace BitrixProLib\MyStore\Exceptions;

use Exception;

/**
 * Исключение специфичное для API MyStore
 * 
 * Выбрасывается когда API MyStore возвращает ошибку
 * 
 * @package BitrixProLib\MyStore\Exceptions
 */
class MyStoreException extends Exception
{
    private ?array $errorData;

    /**
     * Конструктор
     * 
     * @param string $message Сообщение об ошибке
     * @param int $code Код ошибки
     * @param Exception|null $previous Предыдущее исключение
     * @param array|null $errorData Дополнительные данные об ошибке от MyStore
     */
    public function __construct(
        string $message = '',
        int $code = 0,
        ?Exception $previous = null,
        ?array $errorData = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->errorData = $errorData;
    }

    /**
     * Получение дополнительных данных об ошибке от MyStore
     * 
     * @return array|null Данные об ошибке
     */
    public function getErrorData(): ?array
    {
        return $this->errorData;
    }

    /**
     * Создание исключения из ответа об ошибке MyStore
     * 
     * @param array $errorResponse Ответ об ошибке MyStore
     * @return self
     */
    public static function fromMyStoreResponse(array $errorResponse): self
    {
        $message = $errorResponse['error_description'] ?? $errorResponse['error'] ?? 'Неизвестная ошибка MyStore';
        $code = $errorResponse['error_code'] ?? 0;
        
        return new self($message, $code, null, $errorResponse);
    }
}