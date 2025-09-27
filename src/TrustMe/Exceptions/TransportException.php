<?php

declare(strict_types=1);

namespace BitrixProLib\TrustMe\Exceptions;

use Exception;

/**
 * Transport layer exception for TrustMe
 * 
 * Thrown when network or transport errors occur
 * 
 * @package BitrixProLib\TrustMe\Exceptions
 */
class TransportException extends Exception
{
    private ?int $httpCode;
    private ?string $responseBody;

    /**
     * Constructor
     * 
     * @param string $message Error message
     * @param int $code Error code
     * @param Exception|null $previous Previous exception
     * @param int|null $httpCode HTTP status code
     * @param string|null $responseBody Response body
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
     * Get HTTP status code
     * 
     * @return int|null HTTP status code
     */
    public function getHttpCode(): ?int
    {
        return $this->httpCode;
    }

    /**
     * Get response body
     * 
     * @return string|null Response body
     */
    public function getResponseBody(): ?string
    {
        return $this->responseBody;
    }

    /**
     * Create exception from HTTP error
     * 
     * @param int $httpCode HTTP status code
     * @param string $responseBody Response body
     * @return self
     */
    public static function fromHttpError(int $httpCode, string $responseBody = ''): self
    {
        $message = "HTTP Error {$httpCode}";
        if ($responseBody) {
            $message .= ": {$responseBody}";
        }
        
        return new self($message, $httpCode, null, $httpCode, $responseBody);
    }
}
