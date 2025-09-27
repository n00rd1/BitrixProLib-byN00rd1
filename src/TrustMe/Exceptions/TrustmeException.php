<?php

declare(strict_types=1);

namespace BitrixProLib\TrustMe\Exceptions;

use Exception;

/**
 * TrustMe service specific exception
 * 
 * Thrown when TrustMe API returns an error response
 * 
 * @package BitrixProLib\TrustMe\Exceptions
 */
class TrustmeException extends Exception
{
    private ?array $errorData;

    /**
     * Constructor
     * 
     * @param string $message Error message
     * @param int $code Error code
     * @param Exception|null $previous Previous exception
     * @param array|null $errorData Additional error data from TrustMe
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
     * Get additional error data from TrustMe
     * 
     * @return array|null Error data
     */
    public function getErrorData(): ?array
    {
        return $this->errorData;
    }

    /**
     * Create exception from TrustMe error response
     * 
     * @param array $errorResponse TrustMe error response
     * @return self
     */
    public static function fromTrustmeResponse(array $errorResponse): self
    {
        $message = $errorResponse['errorText'] ?? $errorResponse['error'] ?? 'Unknown TrustMe error';
        $code = $errorResponse['errorCode'] ?? 0;
        
        return new self($message, $code, null, $errorResponse);
    }
}
