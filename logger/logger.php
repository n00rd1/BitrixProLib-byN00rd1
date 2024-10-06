<?php

declare(strict_types=1);

namespace logger;

class Logger
{
    private static string $logDirectory = 'D:\OSPanel\domains\COMPANY\library\logs';
    private static array $logDirectories = [];
    private static bool $initialized = false;
    private static int $logRetentionDays = 60; // Настраиваемый срок хранения логов
    private static string $sessionId = '';

    /**
     * Инициализация директорий и переменных
     */
    private static function initialize(): void
    {
        if (self::$initialized) {
            return;
        }

        self::$sessionId = uniqid('', true);

        self::$logDirectories = [
            'error' => self::$logDirectory . DIRECTORY_SEPARATOR . 'errors' . DIRECTORY_SEPARATOR . date('d_m_Y') . DIRECTORY_SEPARATOR,
            'success' => self::$logDirectory . DIRECTORY_SEPARATOR . 'success' . DIRECTORY_SEPARATOR . date('d_m_Y') . DIRECTORY_SEPARATOR,
            'creation' => self::$logDirectory . DIRECTORY_SEPARATOR . 'creation' . DIRECTORY_SEPARATOR . date('d_m_Y') . DIRECTORY_SEPARATOR,
            'update' => self::$logDirectory . DIRECTORY_SEPARATOR . 'updates' . DIRECTORY_SEPARATOR . date('d_m_Y') . DIRECTORY_SEPARATOR,
            'request' => self::$logDirectory . DIRECTORY_SEPARATOR . 'requests' . DIRECTORY_SEPARATOR . date('d_m_Y') . DIRECTORY_SEPARATOR,
        ];

        self::initializeDirectories();
        self::cleanOldLogs();
        self::$initialized = true;
    }

    /**
     * Инициализация директорий для логов.
     */
    private static function initializeDirectories(): void
    {
        foreach (self::$logDirectories as $directory) {
            if (!is_dir($directory)) {
                mkdir($directory, 0777, true);
            }
        }
    }

    /**
     * Получает путь к файлу лога на основе типа.
     *
     * @param string $type Тип лога ("request", "error", "success", "creation" или "update").
     * @return string Путь к файлу лога.
     */
    private static function getLogFilePath(string $type): string
    {
        $filename = sprintf('%s.log', $type);
        $directory = self::$logDirectories[$type] ?? self::$logDirectory;

        return $directory . $filename;
    }

    /**
     * Записывает сообщение в лог-файл.
     *
     * @param string $message Сообщение для записи.
     * @param string $type Тип лога ("request", "error", "success", "creation" или "update").
     */
    public static function log(string $message, string $type = 'request'): void
    {
        self::initialize();
        $logFile = self::getLogFilePath($type);

        if (!file_exists($logFile)) {
            touch($logFile);
        }

        $dateTime = date('[H:i:s]');
        $logEntry = sprintf('%s [%s] %s%s', $dateTime, self::$sessionId, $message, PHP_EOL);
        file_put_contents($logFile, $logEntry, FILE_APPEND);
    }

    /**
     * Удаляет логи, старше установленного срока (по умолчанию — 60 дней).
     */
    private static function cleanOldLogs(): void
    {
        $now = time();

        foreach (self::$logDirectories as $directory) {
            foreach (glob($directory . '*.log') as $file) {
                if (is_file($file) && ($now - filemtime($file)) >= self::$logRetentionDays * 24 * 60 * 60) {
                    unlink($file);
                }
            }
        }
    }

    /**
     * Устанавливает путь к основной директории логов.
     *
     * @param string $logDirectory
     */
    public static function setLogDirectory(string $logDirectory): void
    {
        self::$logDirectory = rtrim($logDirectory, DIRECTORY_SEPARATOR);
        self::$initialized = false;
    }

    /**
     * Устанавливает срок хранения логов в днях.
     *
     * @param int $days
     */
    public static function setLogRetentionDays(int $days): void
    {
        self::$logRetentionDays = $days;
        self::$initialized = false;
    }
}
