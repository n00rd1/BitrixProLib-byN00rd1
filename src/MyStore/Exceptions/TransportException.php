<?php

declare(strict_types=1);

namespace BitrixProLib\MyStore\Exceptions;

use Exception;

/**
 * Исключение транспортного уровня для MyStore
 * 
 * Выбрасывается при возникновении сетевых или транспортных ошибок
 * 
 * @package BitrixProLib\MyStore\Exceptions
 */
class TransportException extends Exception
{
    private ?int $httpCode;
    private ?string $responseBody;

    /**
     * Конструктор
     * 
     * @param string $message Сообщение об ошибке
     * @param int $code Код ошибки
     * @param Exception|null $previous Предыдущее исключение
     * @param int|null $httpCode HTTP код статуса
     * @param string|null $responseBody Тело ответа
     */
    public function __construct(
        string $message = '',
        int $code = 0,
        ?Exception $previous = null,
        ?int $httpCode = null,
        ?string $responseBody = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->httpCode = $httpCode;
        $this->responseBody = $responseBody;
    }

    /**
     * Получение HTTP кода статуса
     * 
     * @return int|null HTTP код статуса
     */
    public function getHttpCode(): ?int
    {
        return $this->httpCode;
    }

    /**
     * Получение тела ответа
     * 
     * @return string|null Тело ответа
     */
    public function getResponseBody(): ?string
    {
        return $this->responseBody;
    }

    /**
     * Создание исключения из HTTP ошибки
     * 
     * @param int $httpCode HTTP код статуса
     * @param string $responseBody Тело ответа
     * @return self
     */
    public static function fromHttpError(int $httpCode, string $responseBody = ''): self
    {
        $message = "HTTP ошибка {$httpCode}";
        if ($responseBody) {
            $message .= ": {$responseBody}";
        }
        
        return new self($message, $httpCode, null, $httpCode, $responseBody);
    }
}