<?php

declare(strict_types=1);

namespace BitrixProLib\Logger;

use DateTime;
use DateTimeZone;

/**
 * Продвинутая система логирования со структурированным логированием и автоматической очисткой
 * 
 * Возможности:
 * - Структурированное логирование с различными уровнями
 * - Автоматическая ротация и очистка логов
 * - Отслеживание сессий с уникальными ID
 * - Настраиваемые периоды хранения
 * - Несколько форматов логов (JSON, обычный текст)
 * - Мониторинг производительности
 * 
 * @package BitrixProLib\Logger
 * @author N00rd1
 * @version 2.0.0
 */
class Logger
{
    private static string $logDirectory = '';
    private static array $logDirectories = [];
    private static bool $initialized = false;
    private static int $logRetentionDays = 60;
    private static string $sessionId = '';
    private static string $logFormat = 'text'; // 'text' или 'json'
    private static array $logLevels = [
        'debug' => 0,
        'info' => 1,
        'warning' => 2,
        'error' => 3,
        'critical' => 4
    ];
    private static int $currentLogLevel = 1; // По умолчанию: info
    private static array $performanceMetrics = [];

    /**
     * Инициализация логгера с конфигурацией
     * 
     * @return void
     */
    private static function initialize(): void
    {
        if (self::$initialized) {
            return;
        }

        // Генерируем уникальный ID сессии
        self::$sessionId = uniqid('session_', true);

        // Устанавливаем директорию логов по умолчанию, если не установлена
        if (empty(self::$logDirectory)) {
            self::$logDirectory = self::getDefaultLogDirectory();
        }

        // Инициализируем директории логов
        self::$logDirectories = [
            'debug' => self::$logDirectory . DIRECTORY_SEPARATOR . 'debug' . DIRECTORY_SEPARATOR . date('Y-m-d') . DIRECTORY_SEPARATOR,
            'info' => self::$logDirectory . DIRECTORY_SEPARATOR . 'info' . DIRECTORY_SEPARATOR . date('Y-m-d') . DIRECTORY_SEPARATOR,
            'warning' => self::$logDirectory . DIRECTORY_SEPARATOR . 'warning' . DIRECTORY_SEPARATOR . date('Y-m-d') . DIRECTORY_SEPARATOR,
            'error' => self::$logDirectory . DIRECTORY_SEPARATOR . 'error' . DIRECTORY_SEPARATOR . date('Y-m-d') . DIRECTORY_SEPARATOR,
            'critical' => self::$logDirectory . DIRECTORY_SEPARATOR . 'critical' . DIRECTORY_SEPARATOR . date('Y-m-d') . DIRECTORY_SEPARATOR,
            'request' => self::$logDirectory . DIRECTORY_SEPARATOR . 'request' . DIRECTORY_SEPARATOR . date('Y-m-d') . DIRECTORY_SEPARATOR,
            'success' => self::$logDirectory . DIRECTORY_SEPARATOR . 'success' . DIRECTORY_SEPARATOR . date('Y-m-d') . DIRECTORY_SEPARATOR,
            'creation' => self::$logDirectory . DIRECTORY_SEPARATOR . 'creation' . DIRECTORY_SEPARATOR . date('Y-m-d') . DIRECTORY_SEPARATOR,
            'update' => self::$logDirectory . DIRECTORY_SEPARATOR . 'update' . DIRECTORY_SEPARATOR . date('Y-m-d') . DIRECTORY_SEPARATOR,
        ];

        self::initializeDirectories();
        self::cleanOldLogs();
        self::$initialized = true;
    }

    /**
     * Инициализация директорий для логов
     * 
     * @return void
     */
    private static function initializeDirectories(): void
    {
        foreach (self::$logDirectories as $directory) {
            if (!is_dir($directory)) {
                mkdir($directory, 0755, true);
            }
        }
    }

    /**
     * Получение пути к файлу лога для определенного типа
     * 
     * @param string $type Тип лога
     * @return string Путь к файлу лога
     */
    private static function getLogFilePath(string $type): string
    {
        $filename = sprintf('%s.log', $type);
        $directory = self::$logDirectories[$type] ?? self::$logDirectory;
        
        return $directory . $filename;
    }

    /**
     * Логирование сообщения с указанным уровнем и типом
     * 
     * @param string $message Сообщение для лога
     * @param string $type Тип лога (debug, info, warning, error, critical, request, success, creation, update)
     * @param array $context Дополнительные контекстные данные
     * @param string $level Уровень лога (debug, info, warning, error, critical)
     * @return void
     */
    public static function log(
        string $message, 
        string $type = 'info', 
        array $context = [], 
        string $level = 'info'
    ): void {
        self::initialize();

        // Проверяем, позволяет ли уровень лога это сообщение
        if (self::$logLevels[$level] < self::$currentLogLevel) {
            return;
        }

        $logFile = self::getLogFilePath($type);

        if (!file_exists($logFile)) {
            touch($logFile);
            chmod($logFile, 0644);
        }

        $logEntry = self::formatLogEntry($message, $type, $context, $level);
        file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
    }

    /**
     * Форматирование записи лога в зависимости от текущего формата
     * 
     * @param string $message Сообщение лога
     * @param string $type Тип лога
     * @param array $context Контекстные данные
     * @param string $level Уровень лога
     * @return string Отформатированная запись лога
     */
    private static function formatLogEntry(string $message, string $type, array $context, string $level): string
    {
        $timestamp = (new DateTime('now', new DateTimeZone('UTC')))->format('Y-m-d H:i:s.u');
        
        if (self::$logFormat === 'json') {
            return self::formatJsonLogEntry($message, $type, $context, $level, $timestamp);
        }
        
        return self::formatTextLogEntry($message, $type, $context, $level, $timestamp);
    }

    /**
     * Форматирование записи лога как JSON
     * 
     * @param string $message Сообщение лога
     * @param string $type Тип лога
     * @param array $context Контекстные данные
     * @param string $level Уровень лога
     * @param string $timestamp Временная метка
     * @return string JSON отформатированная запись лога
     */
    private static function formatJsonLogEntry(string $message, string $type, array $context, string $level, string $timestamp): string
    {
        $logData = [
            'timestamp' => $timestamp,
            'level' => strtoupper($level),
            'type' => $type,
            'session_id' => self::$sessionId,
            'message' => $message,
            'context' => $context,
            'memory_usage' => memory_get_usage(true),
            'memory_peak' => memory_get_peak_usage(true)
        ];

        return json_encode($logData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
    }

    /**
     * Форматирование записи лога как обычный текст
     * 
     * @param string $message Сообщение лога
     * @param string $type Тип лога
     * @param array $context Контекстные данные
     * @param string $level Уровень лога
     * @param string $timestamp Временная метка
     * @return string Текстовая отформатированная запись лога
     */
    private static function formatTextLogEntry(string $message, string $type, array $context, string $level, string $timestamp): string
    {
        $contextStr = empty($context) ? '' : ' | Контекст: ' . json_encode($context, JSON_UNESCAPED_UNICODE);
        $memoryUsage = self::formatBytes(memory_get_usage(true));
        $memoryPeak = self::formatBytes(memory_get_peak_usage(true));
        
        return sprintf(
            '[%s] [%s] [%s] [%s] %s | Память: %s (Пик: %s)%s%s',
            $timestamp,
            strtoupper($level),
            $type,
            self::$sessionId,
            $message,
            $memoryUsage,
            $memoryPeak,
            $contextStr,
            PHP_EOL
        );
    }

    /**
     * Форматирование байтов в читаемый формат
     * 
     * @param int $bytes Байты для форматирования
     * @return string Отформатированная строка
     */
    private static function formatBytes(int $bytes): string
    {
        $units = ['Б', 'КБ', 'МБ', 'ГБ', 'ТБ'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= (1 << (10 * $pow));
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }

    /**
     * Очистка старых файлов логов на основе политики хранения
     * 
     * @return void
     */
    private static function cleanOldLogs(): void
    {
        if (self::$logRetentionDays <= 0) {
            return;
        }

        $cutoffTime = time() - (self::$logRetentionDays * 24 * 60 * 60);
        
        foreach (self::$logDirectories as $directory) {
            if (!is_dir($directory)) {
                continue;
            }
            
            $files = glob($directory . '*.log');
            foreach ($files as $file) {
                if (is_file($file) && filemtime($file) < $cutoffTime) {
                    unlink($file);
                }
            }
            
            // Удаляем пустые директории
            $subdirs = glob($directory . '*', GLOB_ONLYDIR);
            foreach ($subdirs as $subdir) {
                if (is_dir($subdir) && count(scandir($subdir)) === 2) { // Только . и ..
                    rmdir($subdir);
                }
            }
        }
    }

    /**
     * Запуск мониторинга производительности для определенной операции
     * 
     * @param string $operation Название операции
     * @return void
     */
    public static function startTimer(string $operation): void
    {
        self::$performanceMetrics[$operation] = [
            'start_time' => microtime(true),
            'start_memory' => memory_get_usage(true)
        ];
    }

    /**
     * Завершение мониторинга производительности и логирование результатов
     * 
     * @param string $operation Название операции
     * @param string $message Дополнительное сообщение
     * @return array Метрики производительности
     */
    public static function endTimer(string $operation, string $message = ''): array
    {
        if (!isset(self::$performanceMetrics[$operation])) {
            return [];
        }

        $metrics = self::$performanceMetrics[$operation];
        $endTime = microtime(true);
        $endMemory = memory_get_usage(true);
        
        $duration = $endTime - $metrics['start_time'];
        $memoryDelta = $endMemory - $metrics['start_memory'];
        
        $performanceData = [
            'operation' => $operation,
            'duration_ms' => round($duration * 1000, 2),
            'memory_delta' => self::formatBytes($memoryDelta),
            'memory_peak' => self::formatBytes(memory_get_peak_usage(true))
        ];
        
        if ($message) {
            $performanceData['message'] = $message;
        }
        
        self::log(
            "Производительность: {$operation} завершена за {$performanceData['duration_ms']}мс",
            'info',
            $performanceData,
            'info'
        );
        
        unset(self::$performanceMetrics[$operation]);
        
        return $performanceData;
    }

    /**
     * Логирование исключения с полным стеком вызовов
     * 
     * @param \Throwable $exception Исключение для логирования
     * @param string $type Тип лога
     * @param array $context Дополнительный контекст
     * @return void
     */
    public static function logException(\Throwable $exception, string $type = 'error', array $context = []): void
    {
        $context['exception'] = [
            'class' => get_class($exception),
            'message' => $exception->getMessage(),
            'code' => $exception->getCode(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString()
        ];
        
        self::log(
            "Исключение: {$exception->getMessage()}",
            $type,
            $context,
            'error'
        );
    }

    /**
     * Установка директории логов
     * 
     * @param string $logDirectory Путь к директории логов
     * @return void
     */
    public static function setLogDirectory(string $logDirectory): void
    {
        self::$logDirectory = rtrim($logDirectory, DIRECTORY_SEPARATOR);
        self::$initialized = false;
    }

    /**
     * Установка периода хранения логов в днях
     * 
     * @param int $days Период хранения в днях
     * @return void
     */
    public static function setLogRetentionDays(int $days): void
    {
        self::$logRetentionDays = $days;
    }

    /**
     * Установка формата лога (text или json)
     * 
     * @param string $format Формат лога ('text' или 'json')
     * @return void
     */
    public static function setLogFormat(string $format): void
    {
        if (in_array($format, ['text', 'json'])) {
            self::$logFormat = $format;
        }
    }

    /**
     * Установка минимального уровня лога
     * 
     * @param string $level Минимальный уровень лога
     * @return void
     */
    public static function setLogLevel(string $level): void
    {
        if (isset(self::$logLevels[$level])) {
            self::$currentLogLevel = self::$logLevels[$level];
        }
    }

    /**
     * Получение текущего ID сессии
     * 
     * @return string ID сессии
     */
    public static function getSessionId(): string
    {
        self::initialize();
        return self::$sessionId;
    }

    /**
     * Получение директории логов по умолчанию в зависимости от ОС
     * 
     * @return string Директория логов по умолчанию
     */
    private static function getDefaultLogDirectory(): string
    {
        return match (PHP_OS_FAMILY) {
            'Windows' => 'D:\OSPanel\domains\company\library\v2\logs',
            default => '/home/bitrix/www/local/library/v2/logs',
        };
    }

    /**
     * Получение статистики логов
     * 
     * @return array Статистика логов
     */
    public static function getLogStats(): array
    {
        self::initialize();
        
        $stats = [
            'session_id' => self::$sessionId,
            'log_directory' => self::$logDirectory,
            'retention_days' => self::$logRetentionDays,
            'log_format' => self::$logFormat,
            'current_level' => self::$currentLogLevel,
            'directories' => []
        ];
        
        foreach (self::$logDirectories as $type => $directory) {
            if (is_dir($directory)) {
                $files = glob($directory . '*.log');
                $totalSize = 0;
                $fileCount = count($files);
                
                foreach ($files as $file) {
                    $totalSize += filesize($file);
                }
                
                $stats['directories'][$type] = [
                    'path' => $directory,
                    'file_count' => $fileCount,
                    'total_size' => self::formatBytes($totalSize)
                ];
            }
        }
        
        return $stats;
    }
}