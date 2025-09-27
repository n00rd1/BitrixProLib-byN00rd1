<?php

declare(strict_types=1);

namespace BitrixProLib\Utils;

/**
 * Форматтер данных
 * 
 * Предоставляет методы для форматирования различных типов данных
 * 
 * @package BitrixProLib\Utils
 * @author N00rd1
 * @version 2.0.0
 */
class DataFormatter
{
    /**
     * Форматирование номера телефона
     * 
     * @param string $phone Номер телефона
     * @param string $format Формат (international, national, clean)
     * @return string Отформатированный номер
     */
    public static function formatPhone(string $phone, string $format = 'international'): string
    {
        // Очищаем номер от всех символов кроме цифр и +
        $cleanPhone = preg_replace('/[^\d+]/', '', $phone);
        
        // Убираем + если есть
        $digits = ltrim($cleanPhone, '+');
        
        // Определяем формат в зависимости от длины
        if (strlen($digits) === 11 && $digits[0] === '7') {
            // Российский номер
            $countryCode = '7';
            $number = substr($digits, 1);
        } elseif (strlen($digits) === 10 && $digits[0] === '7') {
            // Российский номер без кода страны
            $countryCode = '7';
            $number = $digits;
        } elseif (strlen($digits) === 11 && $digits[0] === '8') {
            // Российский номер с 8
            $countryCode = '7';
            $number = substr($digits, 1);
        } else {
            // Другой формат
            return $cleanPhone;
        }
        
        return match ($format) {
            'international' => "+{$countryCode} ({$number[0]}{$number[1]}{$number[2]}) {$number[3]}{$number[4]}{$number[5]}-{$number[6]}{$number[7]}-{$number[8]}{$number[9]}",
            'national' => "8 ({$number[0]}{$number[1]}{$number[2]}) {$number[3]}{$number[4]}{$number[5]}-{$number[6]}{$number[7]}-{$number[8]}{$number[9]}",
            'clean' => $countryCode . $number,
            default => $cleanPhone,
        };
    }

    /**
     * Форматирование валюты
     * 
     * @param float $amount Сумма
     * @param string $currency Валюта
     * @param string $locale Локаль
     * @return string Отформатированная сумма
     */
    public static function formatCurrency(float $amount, string $currency = 'KZT', string $locale = 'ru_RU'): string
    {
        $symbols = [
            'KZT' => '₸',
            'RUB' => '₽',
            'USD' => '$',
            'EUR' => '€',
        ];
        
        $symbol = $symbols[$currency] ?? $currency;
        
        return number_format($amount, 0, ',', ' ') . ' ' . $symbol;
    }

    /**
     * Форматирование даты
     * 
     * @param string|\DateTime $date Дата
     * @param string $format Формат вывода
     * @return string Отформатированная дата
     */
    public static function formatDate(string|\DateTime $date, string $format = 'd.m.Y'): string
    {
        if (is_string($date)) {
            $date = new \DateTime($date);
        }
        
        return $date->format($format);
    }

    /**
     * Форматирование даты и времени
     * 
     * @param string|\DateTime $datetime Дата и время
     * @param string $format Формат вывода
     * @return string Отформатированная дата и время
     */
    public static function formatDateTime(string|\DateTime $datetime, string $format = 'd.m.Y H:i'): string
    {
        if (is_string($datetime)) {
            $datetime = new \DateTime($datetime);
        }
        
        return $datetime->format($format);
    }

    /**
     * Форматирование размера файла
     * 
     * @param int $bytes Размер в байтах
     * @param int $precision Точность
     * @return string Отформатированный размер
     */
    public static function formatFileSize(int $bytes, int $precision = 2): string
    {
        $units = ['Б', 'КБ', 'МБ', 'ГБ', 'ТБ'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }

    /**
     * Форматирование числа с разделителями
     * 
     * @param float $number Число
     * @param int $decimals Количество знаков после запятой
     * @param string $decimalSeparator Разделитель дробной части
     * @param string $thousandsSeparator Разделитель тысяч
     * @return string Отформатированное число
     */
    public static function formatNumber(
        float $number, 
        int $decimals = 0, 
        string $decimalSeparator = ',', 
        string $thousandsSeparator = ' '
    ): string {
        return number_format($number, $decimals, $decimalSeparator, $thousandsSeparator);
    }

    /**
     * Форматирование процентов
     * 
     * @param float $value Значение
     * @param int $decimals Количество знаков после запятой
     * @return string Отформатированный процент
     */
    public static function formatPercent(float $value, int $decimals = 1): string
    {
        return number_format($value, $decimals, ',', ' ') . '%';
    }

    /**
     * Форматирование ИИН/БИН
     * 
     * @param string $iin ИИН/БИН
     * @return string Отформатированный ИИН/БИН
     */
    public static function formatIIN(string $iin): string
    {
        $cleanIIN = preg_replace('/[^\d]/', '', $iin);
        
        if (strlen($cleanIIN) === 12) {
            return substr($cleanIIN, 0, 6) . ' ' . substr($cleanIIN, 6, 6);
        }
        
        return $iin;
    }

    /**
     * Форматирование JSON с отступами
     * 
     * @param mixed $data Данные для форматирования
     * @param int $flags Флаги JSON
     * @return string Отформатированный JSON
     */
    public static function formatJson(mixed $data, int $flags = JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT): string
    {
        return json_encode($data, $flags);
    }

    /**
     * Форматирование массива как строки
     * 
     * @param array $array Массив
     * @param string $separator Разделитель
     * @param string $keyValueSeparator Разделитель ключ-значение
     * @return string Отформатированная строка
     */
    public static function formatArrayAsString(
        array $array, 
        string $separator = ', ', 
        string $keyValueSeparator = ': '
    ): string {
        $result = [];
        
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $value = self::formatArrayAsString($value, $separator, $keyValueSeparator);
            }
            $result[] = $key . $keyValueSeparator . $value;
        }
        
        return implode($separator, $result);
    }

    /**
     * Форматирование времени выполнения
     * 
     * @param float $seconds Секунды
     * @return string Отформатированное время
     */
    public static function formatExecutionTime(float $seconds): string
    {
        if ($seconds < 1) {
            return round($seconds * 1000, 2) . ' мс';
        } elseif ($seconds < 60) {
            return round($seconds, 2) . ' сек';
        } elseif ($seconds < 3600) {
            $minutes = floor($seconds / 60);
            $remainingSeconds = $seconds % 60;
            return $minutes . ' мин ' . round($remainingSeconds, 1) . ' сек';
        } else {
            $hours = floor($seconds / 3600);
            $minutes = floor(($seconds % 3600) / 60);
            return $hours . ' ч ' . $minutes . ' мин';
        }
    }

    /**
     * Форматирование относительного времени
     * 
     * @param string|\DateTime $datetime Дата и время
     * @return string Относительное время
     */
    public static function formatRelativeTime(string|\DateTime $datetime): string
    {
        if (is_string($datetime)) {
            $datetime = new \DateTime($datetime);
        }
        
        $now = new \DateTime();
        $diff = $now->diff($datetime);
        
        if ($diff->days > 0) {
            return $diff->days . ' дн. назад';
        } elseif ($diff->h > 0) {
            return $diff->h . ' ч. назад';
        } elseif ($diff->i > 0) {
            return $diff->i . ' мин. назад';
        } else {
            return 'только что';
        }
    }
}