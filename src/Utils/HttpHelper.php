<?php

declare(strict_types=1);

namespace BitrixProLib\Utils;

use BitrixProLib\Logger\Logger;

/**
 * HTTP helper utilities
 * 
 * Provides common HTTP operations and response handling functions.
 * 
 * @package BitrixProLib\Utils
 * @author N00rd1
 * @version 2.0.0
 */
class HttpHelper
{
    /**
     * Get data from incoming request
     * 
     * @return array Request data
     */
    public static function getRequestData(): array
    {
        $rawPostData = file_get_contents('php://input');
        $data = json_decode($rawPostData, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            Logger::log('JSON decode error: ' . json_last_error_msg(), 'error');
            return [];
        }
        
        return $data ?? [];
    }

    /**
     * Send JSON response and exit
     * 
     * @param bool $success Success status
     * @param string|null $message Response message
     * @param array|null $data Response data
     * @param int $status HTTP status code
     * @return void
     */
    public static function sendJsonResponse(
        bool $success,
        ?string $message = null,
        ?array $data = null,
        int $status = 200
    ): void {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        
        $response = ['success' => $success];
        
        if ($message !== null) {
            $response['message'] = $message;
        }
        
        if ($data !== null) {
            $response['data'] = $data;
        }
        
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit();
    }

    /**
     * Send error response
     * 
     * @param string $message Error message
     * @param array|null $data Additional data
     * @param int $status HTTP status code
     * @return void
     */
    public static function sendErrorResponse(string $message, ?array $data = null, int $status = 400): void
    {
        Logger::log("Error response: {$message}", 'error');
        self::sendJsonResponse(false, $message, $data, $status);
    }

    /**
     * Send success response
     * 
     * @param string $message Success message
     * @param array|null $data Response data
     * @param int $status HTTP status code
     * @return void
     */
    public static function sendSuccessResponse(string $message, ?array $data = null, int $status = 200): void
    {
        Logger::log("Success response: {$message}", 'success');
        self::sendJsonResponse(true, $message, $data, $status);
    }

    /**
     * Log and send response
     * 
     * @param string $logMessage Log message
     * @param string|null $responseMessage Response message
     * @param array|null $data Response data
     * @param int $statusCode HTTP status code
     * @param string $logType Log type
     * @return void
     */
    public static function logAndRespond(
        string $logMessage,
        ?string $responseMessage = null,
        ?array $data = null,
        int $statusCode = 200,
        string $logType = 'error'
    ): void {
        Logger::log($logMessage, $logType);
        self::sendJsonResponse($statusCode < 400, $responseMessage, $data, $statusCode);
    }

    /**
     * Get client IP address
     * 
     * @return string Client IP address
     */
    public static function getClientIp(): string
    {
        $ipKeys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];
        
        foreach ($ipKeys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = $_SERVER[$key];
                if (str_contains($ip, ',')) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    /**
     * Get user agent
     * 
     * @return string User agent string
     */
    public static function getUserAgent(): string
    {
        return $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    }

    /**
     * Check if request is AJAX
     * 
     * @return bool True if AJAX request
     */
    public static function isAjaxRequest(): bool
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    /**
     * Check if request is POST
     * 
     * @return bool True if POST request
     */
    public static function isPostRequest(): bool
    {
        return $_SERVER['REQUEST_METHOD'] === 'POST';
    }

    /**
     * Check if request is GET
     * 
     * @return bool True if GET request
     */
    public static function isGetRequest(): bool
    {
        return $_SERVER['REQUEST_METHOD'] === 'GET';
    }

    /**
     * Get request method
     * 
     * @return string Request method
     */
    public static function getRequestMethod(): string
    {
        return $_SERVER['REQUEST_METHOD'] ?? 'GET';
    }

    /**
     * Get request URI
     * 
     * @return string Request URI
     */
    public static function getRequestUri(): string
    {
        return $_SERVER['REQUEST_URI'] ?? '/';
    }

    /**
     * Get referer URL
     * 
     * @return string|null Referer URL
     */
    public static function getReferer(): ?string
    {
        return $_SERVER['HTTP_REFERER'] ?? null;
    }

    /**
     * Set CORS headers
     * 
     * @param string $origin Allowed origin
     * @param array $methods Allowed methods
     * @param array $headers Allowed headers
     * @return void
     */
    public static function setCorsHeaders(
        string $origin = '*',
        array $methods = ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
        array $headers = ['Content-Type', 'Authorization', 'X-Requested-With']
    ): void {
        header("Access-Control-Allow-Origin: {$origin}");
        header('Access-Control-Allow-Methods: ' . implode(', ', $methods));
        header('Access-Control-Allow-Headers: ' . implode(', ', $headers));
        header('Access-Control-Allow-Credentials: true');
        header('Access-Control-Max-Age: 86400');
    }

    /**
     * Handle preflight OPTIONS request
     * 
     * @return void
     */
    public static function handlePreflightRequest(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            self::setCorsHeaders();
            http_response_code(200);
            exit();
        }
    }

    /**
     * Download file
     * 
     * @param string $filePath File path
     * @param string|null $filename Download filename
     * @param string|null $contentType Content type
     * @return void
     */
    public static function downloadFile(string $filePath, ?string $filename = null, ?string $contentType = null): void
    {
        if (!file_exists($filePath)) {
            http_response_code(404);
            exit('File not found');
        }
        
        $filename = $filename ?? basename($filePath);
        $contentType = $contentType ?? mime_content_type($filePath) ?? 'application/octet-stream';
        
        header('Content-Type: ' . $contentType);
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($filePath));
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
        
        readfile($filePath);
        exit();
    }

    /**
     * Redirect to URL
     * 
     * @param string $url URL to redirect to
     * @param int $statusCode HTTP status code
     * @return void
     */
    public static function redirect(string $url, int $statusCode = 302): void
    {
        http_response_code($statusCode);
        header("Location: {$url}");
        exit();
    }
}
