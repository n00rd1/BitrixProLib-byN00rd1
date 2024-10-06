<?php
declare(strict_types = 1);

use logger\Logger;

require_once match (PHP_OS_FAMILY) {
	'Windows' => 'D:\OSPanel\domains\company\library\v2\logger\logger.php',
	default => '/home/bitrix/www/local/library/v2/logger/logger.php',
};

class BitrixApi
{
	private string $apiUrl;         // Полная ссылка для использования REST API Bitrix
	private int $maxRetries = 3;    // Максимальное количество повторных отправок
	private string $logDirectory;   // Директория для логов

	public function __construct(
		string $authToken = 'default_token',
		string $logDirectory = '',
		string $apiUrl = 'https://bitrix.domain.domainzone/rest/main_user/'
	) {
		$this->apiUrl = $apiUrl . $authToken . '/';

		// Определяем директорию для логов
		$this->logDirectory = $logDirectory ? : match (PHP_OS_FAMILY) {
			'Windows' => 'D:\OSPanel\domains\comapany\library\v2\logs\\',
			default => '/home/bitrix/www/local/library/v2/logs/',
		};

		// Устанавливаем директорию логов для Logger
		Logger::setLogDirectory($this->logDirectory);
	}

	/**
	 * Выполнение API-запроса к Bitrix
	 *
	 * @param string $method Метод API
	 * @param array  $params Параметры запроса
	 *
	 * @return array|null Ответ API или null при ошибке
	 * @throws Exception
	 */
	private function callBitrixApi(string $method, array $params = []): ?array
	{
		$url = $this->apiUrl . $method;
		$queryData = http_build_query($params);
		$retryDelay = 500_000; // Задержка перед повторной попыткой в микросекундах (0,5с)

		for ($attempt = 0; $attempt < $this->maxRetries; $attempt++) {
			try {
				$curl = curl_init();

				curl_setopt_array($curl, [
					CURLOPT_SSL_VERIFYPEER => false,
					CURLOPT_POST => true,
					CURLOPT_HEADER => false,
					CURLOPT_RETURNTRANSFER => true,
					CURLOPT_URL => $url,
					CURLOPT_POSTFIELDS => $queryData,
				]);

				$response = curl_exec($curl);

				if ($curlError = curl_error($curl)) {
					throw new Exception('Ошибка CURL: ' . $curlError);
				}

				$httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
				if ($httpCode !== 200) {
					throw new Exception('Ошибка API: HTTP код ' . $httpCode);
				}

				$responseData = json_decode($response, true);

				curl_close($curl);

				return $responseData;
			} catch (Exception $e) {
				Logger::log('Ошибка при вызове API: ' . $e->getMessage(), 'error');

				if (isset($curl) && is_resource($curl)) {
					curl_close($curl);
				}

				if ($attempt === $this->maxRetries - 1) {
					throw $e;
				}

				usleep($retryDelay);
			}
		}

		return NULL;
	}

	// --- Методы для сделок ---

	public function createDeal(array $fields): array|int|null
	{
		$response = $this->callBitrixApi('crm.deal.add', ['fields' => $fields]);
		if ($response && isset($response['result'])) {
			$dealId = $response['result'];
			Logger::log('Успешное создание сделки с ID: ' . $dealId, 'creation');
			return $dealId;
		}

		Logger::log('Не удалось создать сделку', 'error');
		return NULL;
	}

	public function updateDeal(int $dealId, array $fields): ?bool
	{
		$response = $this->callBitrixApi('crm.deal.update', ['id' => $dealId, 'fields' => $fields]);

		if ($response && $response['result'] === true) {
			Logger::log("Успешное обновление сделки с ID: $dealId", 'update');
			return $response['result'];
		}

		Logger::log("Не удалось обновить сделку с ID: $dealId", 'error');
		return false;
	}

	public function deleteDeal(int $dealId): ?bool
	{
		$response = $this->callBitrixApi('crm.deal.delete', ['id' => $dealId]);

		if ($response && $response['result'] === true) {
			Logger::log("Успешное удаление сделки с ID: $dealId", 'success');
			return $response['result'];
		}

		Logger::log("Не удалось удалить сделку с ID: $dealId", 'error');
		return false;
	}

	public function getDealById(int $dealId): ?array
	{
		$response = $this->callBitrixApi('crm.deal.get', ['id' => $dealId]);

		if ($response && isset($response['result'])) {
			return $response['result'];
		}

		Logger::log("Не удалось получить сделку с ID: $dealId", 'error');
		return NULL;
	}

	public function listDeals(array $filter = [], array $select = [], int $start = 0): ?array
	{
		$response = $this->callBitrixApi('crm.deal.list', [
			'filter' => $filter,
			'select' => $select,
			'start' => $start,
		]);

		if ($response && isset($response['result'])) {
			return $response['result'];
		}

		Logger::log('Не удалось получить список сделок', 'error');
		return NULL;
	}

	// --- Методы для клиентов ---

	public function createClient(array $fields): array|int|null
	{
		$response = $this->callBitrixApi('crm.contact.add', ['fields' => $fields]);

		if ($response && isset($response['result'])) {
			$clientId = $response['result'];
			Logger::log('Успешное создание клиента с ID: ' . $clientId, 'creation');
			return $clientId;
		}

		Logger::log('Не удалось создать клиента', 'error');
		return NULL;
	}

	public function updateClient(int $clientId, array $fields): ?bool
	{
		$response = $this->callBitrixApi('crm.contact.update', ['id' => $clientId, 'fields' => $fields]);
		if ($response && $response['result'] === true) {
			Logger::log("Успешное обновление клиента с ID: $clientId", 'update');
			return $response['result'];
		}

		Logger::log("Не удалось обновить клиента с ID: $clientId", 'error');
		return false;
	}

	public function deleteClient(int $clientId): ?bool
	{
		$response = $this->callBitrixApi('crm.contact.delete', ['id' => $clientId]);
		if ($response && $response['result'] === true) {
			Logger::log("Успешное удаление клиента с ID: $clientId", 'success');
			return $response['result'];
		}

		Logger::log("Не удалось удалить клиента с ID: $clientId", 'error');
		return false;
	}

	public function getClientById(int $clientId): ?array
	{
		$response = $this->callBitrixApi('crm.contact.get', ['id' => $clientId]);

		if ($response && isset($response['result'])) {
			return $response['result'];
		}

		Logger::log("Не удалось получить клиента с ID: $clientId", 'error');
		return NULL;
	}

	public function listClients(array $filter = [], array $select = [], int $start = 0): ?array
	{
		$response = $this->callBitrixApi('crm.contact.list', [
			'filter' => $filter,
			'select' => $select,
			'start' => $start,
		]);

		if ($response && isset($response['result'])) {
			return $response['result'];
		}

		Logger::log('Не удалось получить список клиентов', 'error');
		return NULL;
	}

	// --- Методы для элементов ---

	public function createItem(array $fields): array|int|null
	{
		$response = $this->callBitrixApi('crm.item.add', ['fields' => $fields]);
		if ($response && isset($response['result']['item']['id'])) {
			$itemId = $response['result']['item']['id'];
			Logger::log('Успешное создание элемента с ID: ' . $itemId, 'creation');
			return $itemId;
		}

		Logger::log('Не удалось создать элемент', 'error');
		return NULL;
	}

	public function updateItem(int $itemId, array $fields): ?bool
	{
		$response = $this->callBitrixApi('crm.item.update', ['id' => $itemId, 'fields' => $fields]);

		if ($response && isset($response['result']['item']['id'])) {
			Logger::log("Успешное обновление элемента с ID: $itemId", 'update');
			return true;
		}

		Logger::log("Не удалось обновить элемент с ID: $itemId", 'error');
		return false;
	}

	public function deleteItem(int $itemId): ?bool
	{
		$response = $this->callBitrixApi('crm.item.delete', ['id' => $itemId]);
		if ($response && isset($response['result'])) {
			Logger::log("Успешное удаление элемента с ID: $itemId", 'success');
			return true;
		}

		Logger::log("Не удалось удалить элемент с ID: $itemId", 'error');
		return false;
	}

	public function getItemById(int $itemId): ?array
	{
		$response = $this->callBitrixApi('crm.item.get', ['id' => $itemId]);

		if ($response && isset($response['result']['item'])) {
			return $response['result']['item'];
		}

		Logger::log("Не удалось получить элемент с ID: $itemId", 'error');
		return NULL;
	}

	public function listItems(array $filter = [], array $select = [], int $start = 0): ?array
	{
		$response = $this->callBitrixApi('crm.item.list', [
			'filter' => $filter,
			'select' => $select,
			'start' => $start,
		]);

		if ($response && isset($response['result']['items'])) {
			return $response['result']['items'];
		}

		Logger::log('Не удалось получить список элементов', 'error');
		return NULL;
	}

	/* Новые функции по работе с документами */

	public function createDocument(int $templateId, int $entityTypeId, int $entityId, array $values = []): ?array
	{
		$response = $this->callBitrixApi('crm.documentgenerator.document.add', [
			'templateId' => $templateId,
			'entityTypeId' => $entityTypeId,
			'entityId' => $entityId,
			'values' => $values,
		]);

		if ($response && isset($response['result']['document']['id'])) {
			$documentId = $response['result']['document']['id'];
			Logger::log('Успешное создание документа с ID: ' . $documentId, 'creation');
			return ['document_id' => $documentId];
		}

		Logger::log('Не удалось создать документ', 'error');
		return NULL;
	}

	public function enableDocumentPublicUrl(int $documentId): ?string
	{
		$response = $this->callBitrixApi('crm.documentgenerator.document.enablepublicurl', [
			'id' => $documentId,
			'status' => 1,
		]);

		if ($response && isset($response['result']['publicUrl'])) {
			$publicUrl = $response['result']['publicUrl'];
			Logger::log('Публичная ссылка для документа с ID ' . $documentId . ': ' . $publicUrl, 'success');
			return $publicUrl;
		}

		Logger::log('Не удалось включить публичную ссылку для документа с ID: ' . $documentId, 'error');
		return NULL;
	}
}
