<?php

declare(strict_types=1);

namespace BitrixProLib\Utils;

/**
 * JSON помощник
 * 
 * Предоставляет методы для работы с JSON данными
 * 
 * @package BitrixProLib\Utils
 * @author N00rd1
 * @version 2.0.0
 */
class JsonHelper
{
    /**
     * Кодирование данных в JSON
     * 
     * @param mixed $data Данные для кодирования
     * @param int $flags Флаги JSON
     * @param int $depth Глубина рекурсии
     * @return string JSON строка
     * @throws \RuntimeException
     */
    public static function encode(mixed $data, int $flags = JSON_UNESCAPED_UNICODE, int $depth = 512): string
    {
        $json = json_encode($data, $flags, $depth);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException('Ошибка кодирования JSON: ' . json_last_error_msg());
        }
        
        return $json;
    }

    /**
     * Декодирование JSON строки
     * 
     * @param string $json JSON строка
     * @param bool $assoc Возвращать ассоциативный массив
     * @param int $depth Глубина рекурсии
     * @param int $flags Флаги JSON
     * @return mixed Декодированные данные
     * @throws \RuntimeException
     */
    public static function decode(string $json, bool $assoc = true, int $depth = 512, int $flags = 0): mixed
    {
        $data = json_decode($json, $assoc, $depth, $flags);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException('Ошибка декодирования JSON: ' . json_last_error_msg());
        }
        
        return $data;
    }

    /**
     * Безопасное декодирование JSON с возвратом значения по умолчанию
     * 
     * @param string $json JSON строка
     * @param mixed $default Значение по умолчанию
     * @param bool $assoc Возвращать ассоциативный массив
     * @return mixed Декодированные данные или значение по умолчанию
     */
    public static function decodeSafe(string $json, mixed $default = null, bool $assoc = true): mixed
    {
        try {
            return self::decode($json, $assoc);
        } catch (\RuntimeException) {
            return $default;
        }
    }

    /**
     * Форматирование JSON с отступами
     * 
     * @param mixed $data Данные для форматирования
     * @param int $flags Флаги JSON
     * @return string Отформатированный JSON
     * @throws \RuntimeException
     */
    public static function prettyEncode(mixed $data, int $flags = JSON_UNESCAPED_UNICODE): string
    {
        return self::encode($data, $flags | JSON_PRETTY_PRINT);
    }

    /**
     * Валидация JSON строки
     * 
     * @param string $json JSON строка
     * @return bool Результат валидации
     */
    public static function isValid(string $json): bool
    {
        json_decode($json);
        return json_last_error() === JSON_ERROR_NONE;
    }

    /**
     * Получение ошибки JSON
     * 
     * @return string Сообщение об ошибке
     */
    public static function getLastError(): string
    {
        return json_last_error_msg();
    }

    /**
     * Получение кода ошибки JSON
     * 
     * @return int Код ошибки
     */
    public static function getLastErrorCode(): int
    {
        return json_last_error();
    }

    /**
     * Объединение JSON объектов
     * 
     * @param string $json1 Первый JSON
     * @param string $json2 Второй JSON
     * @param int $flags Флаги JSON
     * @return string Объединенный JSON
     * @throws \RuntimeException
     */
    public static function merge(string $json1, string $json2, int $flags = JSON_UNESCAPED_UNICODE): string
    {
        $data1 = self::decode($json1, true);
        $data2 = self::decode($json2, true);
        
        if (!is_array($data1) || !is_array($data2)) {
            throw new \RuntimeException('Оба JSON должны быть объектами или массивами');
        }
        
        $merged = array_merge_recursive($data1, $data2);
        return self::encode($merged, $flags);
    }

    /**
     * Извлечение значения по пути из JSON
     * 
     * @param string $json JSON строка
     * @param string $path Путь к значению (например: "user.name")
     * @param mixed $default Значение по умолчанию
     * @return mixed Значение или значение по умолчанию
     */
    public static function getValue(string $json, string $path, mixed $default = null): mixed
    {
        try {
            $data = self::decode($json, true);
            $keys = explode('.', $path);
            
            foreach ($keys as $key) {
                if (!is_array($data) || !array_key_exists($key, $data)) {
                    return $default;
                }
                $data = $data[$key];
            }
            
            return $data;
        } catch (\RuntimeException) {
            return $default;
        }
    }

    /**
     * Установка значения по пути в JSON
     * 
     * @param string $json JSON строка
     * @param string $path Путь к значению
     * @param mixed $value Новое значение
     * @param int $flags Флаги JSON
     * @return string Обновленный JSON
     * @throws \RuntimeException
     */
    public static function setValue(string $json, string $path, mixed $value, int $flags = JSON_UNESCAPED_UNICODE): string
    {
        $data = self::decode($json, true);
        $keys = explode('.', $path);
        $current = &$data;
        
        foreach ($keys as $key) {
            if (!is_array($current)) {
                $current = [];
            }
            if (!array_key_exists($key, $current)) {
                $current[$key] = [];
            }
            $current = &$current[$key];
        }
        
        $current = $value;
        return self::encode($data, $flags);
    }

    /**
     * Удаление значения по пути из JSON
     * 
     * @param string $json JSON строка
     * @param string $path Путь к значению
     * @param int $flags Флаги JSON
     * @return string Обновленный JSON
     * @throws \RuntimeException
     */
    public static function removeValue(string $json, string $path, int $flags = JSON_UNESCAPED_UNICODE): string
    {
        $data = self::decode($json, true);
        $keys = explode('.', $path);
        $current = &$data;
        
        for ($i = 0; $i < count($keys) - 1; $i++) {
            if (!is_array($current) || !array_key_exists($keys[$i], $current)) {
                return $json; // Путь не найден
            }
            $current = &$current[$keys[$i]];
        }
        
        $lastKey = end($keys);
        if (is_array($current) && array_key_exists($lastKey, $current)) {
            unset($current[$lastKey]);
        }
        
        return self::encode($data, $flags);
    }

    /**
     * Сравнение JSON объектов
     * 
     * @param string $json1 Первый JSON
     * @param string $json2 Второй JSON
     * @return bool Результат сравнения
     */
    public static function equals(string $json1, string $json2): bool
    {
        try {
            $data1 = self::decode($json1, true);
            $data2 = self::decode($json2, true);
            return $data1 === $data2;
        } catch (\RuntimeException) {
            return false;
        }
    }

    /**
     * Минификация JSON (удаление пробелов)
     * 
     * @param string $json JSON строка
     * @return string Минифицированный JSON
     * @throws \RuntimeException
     */
    public static function minify(string $json): string
    {
        $data = self::decode($json);
        return self::encode($data, JSON_UNESCAPED_UNICODE);
    }
}