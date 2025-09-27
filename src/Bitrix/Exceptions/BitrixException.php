<?php

declare(strict_types=1);

namespace BitrixProLib\Bitrix\Exceptions;

use Exception;

/**
 * Исключение специфичное для API Bitrix
 * 
 * Выбрасывается когда API Bitrix24 возвращает ошибку
 * 
 * @package BitrixProLib\Bitrix\Exceptions
 */
class BitrixException extends Exception
{
    private ?array $errorData;

    /**
     * Конструктор
     * 
     * @param string $message Сообщение об ошибке
     * @param int $code Код ошибки
     * @param Exception|null $previous Предыдущее исключение
     * @param array|null $errorData Дополнительные данные об ошибке от Bitrix
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
     * Получение дополнительных данных об ошибке от Bitrix
     * 
     * @return array|null Данные об ошибке
     */
    public function getErrorData(): ?array
    {
        return $this->errorData;
    }

    /**
     * Создание исключения из ответа об ошибке Bitrix
     * 
     * @param array $errorResponse Ответ об ошибке Bitrix
     * @return self
     */
    public static function fromBitrixResponse(array $errorResponse): self
    {
        $message = $errorResponse['error_description'] ?? $errorResponse['error'] ?? 'Неизвестная ошибка Bitrix';
        $code = $errorResponse['error_code'] ?? 0;
        
        return new self($message, $code, null, $errorResponse);
    }
}