<?php

declare(strict_types=1);

namespace BitrixProLib\Utils;

/**
 * Валидатор данных
 * 
 * Предоставляет методы для валидации различных типов данных
 * 
 * @package BitrixProLib\Utils
 * @author N00rd1
 * @version 2.0.0
 */
class DataValidator
{
    /**
     * Валидация номера телефона
     * 
     * @param string $phone Номер телефона
     * @return bool Результат валидации
     */
    public static function validatePhone(string $phone): bool
    {
        // Очищаем номер от всех символов кроме цифр и +
        $cleanPhone = preg_replace('/[^\d+]/', '', $phone);
        
        // Проверяем различные форматы номеров
        $patterns = [
            '/^\+7\d{10}$/',           // +7XXXXXXXXXX
            '/^8\d{10}$/',             // 8XXXXXXXXXX
            '/^7\d{10}$/',             // 7XXXXXXXXXX
            '/^\+\d{1,3}\d{4,14}$/',   // Международный формат
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $cleanPhone)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Валидация ИИН/БИН
     * 
     * @param string $iin ИИН/БИН
     * @return bool Результат валидации
     */
    public static function validateIIN(string $iin): bool
    {
        // Очищаем от всех символов кроме цифр
        $cleanIIN = preg_replace('/[^\d]/', '', $iin);
        
        // ИИН должен содержать 12 цифр
        if (strlen($cleanIIN) !== 12) {
            return false;
        }
        
        // Проверяем контрольную сумму
        return self::validateIINChecksum($cleanIIN);
    }

    /**
     * Валидация контрольной суммы ИИН
     * 
     * @param string $iin ИИН
     * @return bool Результат валидации
     */
    private static function validateIINChecksum(string $iin): bool
    {
        $weights1 = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11];
        $weights2 = [3, 4, 5, 6, 7, 8, 9, 10, 11, 1, 2];
        
        $sum1 = 0;
        $sum2 = 0;
        
        for ($i = 0; $i < 11; $i++) {
            $sum1 += (int) $iin[$i] * $weights1[$i];
            $sum2 += (int) $iin[$i] * $weights2[$i];
        }
        
        $checkDigit1 = $sum1 % 11;
        $checkDigit2 = $sum2 % 11;
        
        if ($checkDigit1 === 10) {
            $checkDigit1 = $checkDigit2;
        }
        
        return $checkDigit1 === (int) $iin[11];
    }

    /**
     * Валидация email адреса
     * 
     * @param string $email Email адрес
     * @return bool Результат валидации
     */
    public static function validateEmail(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Валидация даты
     * 
     * @param string $date Дата в формате Y-m-d
     * @return bool Результат валидации
     */
    public static function validateDate(string $date): bool
    {
        $d = \DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date;
    }

    /**
     * Валидация даты и времени
     * 
     * @param string $datetime Дата и время в формате Y-m-d H:i:s
     * @return bool Результат валидации
     */
    public static function validateDateTime(string $datetime): bool
    {
        $d = \DateTime::createFromFormat('Y-m-d H:i:s', $datetime);
        return $d && $d->format('Y-m-d H:i:s') === $datetime;
    }

    /**
     * Валидация URL
     * 
     * @param string $url URL
     * @return bool Результат валидации
     */
    public static function validateUrl(string $url): bool
    {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }

    /**
     * Валидация JSON строки
     * 
     * @param string $json JSON строка
     * @return bool Результат валидации
     */
    public static function validateJson(string $json): bool
    {
        json_decode($json);
        return json_last_error() === JSON_ERROR_NONE;
    }

    /**
     * Валидация числового значения
     * 
     * @param mixed $value Значение для проверки
     * @param float|null $min Минимальное значение
     * @param float|null $max Максимальное значение
     * @return bool Результат валидации
     */
    public static function validateNumber(mixed $value, ?float $min = null, ?float $max = null): bool
    {
        if (!is_numeric($value)) {
            return false;
        }
        
        $num = (float) $value;
        
        if ($min !== null && $num < $min) {
            return false;
        }
        
        if ($max !== null && $num > $max) {
            return false;
        }
        
        return true;
    }

    /**
     * Валидация длины строки
     * 
     * @param string $string Строка для проверки
     * @param int|null $minLength Минимальная длина
     * @param int|null $maxLength Максимальная длина
     * @return bool Результат валидации
     */
    public static function validateStringLength(string $string, ?int $minLength = null, ?int $maxLength = null): bool
    {
        $length = mb_strlen($string, 'UTF-8');
        
        if ($minLength !== null && $length < $minLength) {
            return false;
        }
        
        if ($maxLength !== null && $length > $maxLength) {
            return false;
        }
        
        return true;
    }

    /**
     * Валидация массива
     * 
     * @param mixed $value Значение для проверки
     * @param int|null $minCount Минимальное количество элементов
     * @param int|null $maxCount Максимальное количество элементов
     * @return bool Результат валидации
     */
    public static function validateArray(mixed $value, ?int $minCount = null, ?int $maxCount = null): bool
    {
        if (!is_array($value)) {
            return false;
        }
        
        $count = count($value);
        
        if ($minCount !== null && $count < $minCount) {
            return false;
        }
        
        if ($maxCount !== null && $count > $maxCount) {
            return false;
        }
        
        return true;
    }

    /**
     * Валидация обязательных полей в массиве
     * 
     * @param array $data Массив данных
     * @param array $requiredFields Обязательные поля
     * @return array Массив отсутствующих полей
     */
    public static function validateRequiredFields(array $data, array $requiredFields): array
    {
        $missing = [];
        
        foreach ($requiredFields as $field) {
            if (!array_key_exists($field, $data) || empty($data[$field])) {
                $missing[] = $field;
            }
        }
        
        return $missing;
    }
}