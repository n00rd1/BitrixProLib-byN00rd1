<?php
declare(strict_types = 1);

// Подключаем библиотеку логирования и BitrixAPI
use logger\Logger;

if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
	require_once 'D:\OSPanel\domains\COMPANY\library\v2\logger\logger.php';
	require_once 'D:\OSPanel\domains\COMPANY\library\v2\bitrix\b24.php';
	$logDirectory = 'D:\OSPanel\domains\COMPANY\logs';
} else {
	include_once '/home/bitrix/www/local/library/v2/logger/logger.php';
	include_once '/home/bitrix/www/local/library/v2/bitrix/b24.php';
	$logDirectory = '/home/bitrix/www/local/logs';
}

// Устанавливаем директорию для логов
Logger::setLogDirectory($logDirectory);

/**
 * Получает данные из входящего запроса
 *
 * @return mixed
 */
function getData(): mixed
{
	$rawPostData = file_get_contents('php://input');
	$data = json_decode($rawPostData, true);

	if (json_last_error() !== JSON_ERROR_NONE) {
		logAndRespond('Ошибка декодирования JSON', 'Некорректные данные', ['data' => $rawPostData], 400, 'error');
	}

	return $data ?? [];
}

/**
 * Получение данных клиента по телефону и ИИН
 *
 * @param string    $phone Телефон клиента
 * @param string    $iin ИИН клиента
 * @param BitrixApi $handler Экземпляр BitrixApi
 *
 * @return array|null
 */
function getClient(string $phone, string $iin, BitrixApi $handler): ?array
{
	if (empty($phone) && empty($iin)) {
		logAndRespond('Пустые данные при запросе клиента', 'Пустые данные', NULL, 400, 'error');
	}

	$clients = [];

	if (!empty($phone)) {
		$phone = preg_replace('/\D/', '', $phone);

		if (!isValidPhone($phone)) {
			logAndRespond("Некорректный телефон: $phone", 'Некорректный телефон', NULL, 400, 'error');
		}

		$phoneVariants = formatPhoneVariants($phone);

		foreach ($phoneVariants as $variant) {
			usleep(500_000); // Задержка 0.5 секунды
			$result = $handler->listClients(['PHONE' => $variant]);
			if (!empty($result)) {
				$clients = array_merge($clients, $result);
			}
		}
	}

	if (!empty($iin)) {
		if (!isValidIIN($iin)) {
			logAndRespond("Некорректный ИИН: $iin", 'Некорректный ИИН', NULL, 400, 'error');
		}

		$result = $handler->listClients(['UF_CRM_1554290627253' => $iin]);
		if (!empty($result)) {
			$clients = array_merge($clients, $result);
		}
	}

	if (empty($clients)) {
		Logger::log("Клиенты не найдены [Телефон: $phone, ИИН: $iin]", 'error');
		return NULL;
	}

	$uniqueClients = array_unique($clients, SORT_REGULAR);

	Logger::log("Найдены клиенты по телефону или ИИН [Телефон: $phone, ИИН: $iin]", 'success');
	return $uniqueClients;
}

/**
 * Формирует варианты номера телефона для поиска
 *
 * @param string $phone
 *
 * @return array
 */
function formatPhoneVariants(string $phone): array
{
	$number = substr($phone, -10); // Последние 10 цифр номера

	return [
		'7' . $number,
		'8' . $number,
		'+7' . $number,
	];
}

/**
 * Разделяет полное имя на части
 *
 * @param string $fullName
 *
 * @return array
 */
function splitFullName(string $fullName): array
{
	$parts = preg_split('/\s+/', trim($fullName));

	return [
		'lastName'   => $parts[0] ?? '',
		'firstName'  => $parts[1] ?? '',
		'middleName' => $parts[2] ?? '',
	];
}

/**
 * Объединяет части имени в полное имя
 *
 * @param string $lastName
 * @param string $firstName
 * @param string $middleName
 *
 * @return string
 */
function combineFullName(string $lastName, string $firstName, string $middleName = ''): string
{
	return trim("$lastName $firstName $middleName");
}

/**
 * Проверяет корректность ИИН
 *
 * @param string $iin
 *
 * @return bool
 */
function isValidIIN(string $iin): bool
{
	return preg_match('/^\d{12}$/', $iin) === 1;
}

/**
 * Проверяет корректность номера телефона
 *
 * @param string $phone
 *
 * @return bool
 */
function isValidPhone(string $phone): bool
{
	$phone = preg_replace('/\D/', '', $phone);
	return preg_match('/^(7|8)?\d{10}$/', $phone) === 1;
}

/**
 * Централизованное логирование и отправка ответа
 *
 * @param string      $logMessage Сообщение для лога
 * @param string|null $responseMessage Сообщение для ответа
 * @param array|null  $data Дополнительные данные
 * @param int         $statusCode HTTP-код ответа
 * @param string      $logType Тип лога (error, success, request)
 */
function logAndRespond(
	string $logMessage,
	?string $responseMessage = NULL,
	?array $data = NULL,
	int $statusCode = 200,
	string $logType = 'error'
): void {
	Logger::log($logMessage, $logType);
	sendResponse(false, $responseMessage, $data, $statusCode);
}

/**
 * Отправляет ответ клиенту в формате JSON и завершает скрипт
 *
 * @param bool        $success Статус выполнения
 * @param string|null $message Сообщение
 * @param array|null  $data Данные
 * @param int         $status HTTP статус код
 */
function sendResponse(
	bool $success,
	?string $message = NULL,
	?array $data = NULL,
	int $status = 200
): void {
	http_response_code($status);
	header('Content-Type: application/json; charset=utf-8');

	$response = ['success' => $success];

	if ($message !== NULL) {
		$response['message'] = $message;
	}

	if ($data !== NULL) {
		$response['data'] = $data;
	}

	echo json_encode($response);
	exit();
}
