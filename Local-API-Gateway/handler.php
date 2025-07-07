<?php
/**
 * Yandex.Metrika CDP API Handler for Keitaro
 *
 * This script processes S2S postbacks from Keitaro and sends events
 * to the Yandex.Metrika CDP API using the ClientID. It logs the yclid
 * for reference.
 */

require_once __DIR__ . '/config.php';
define('LOG_FILE', __DIR__ . '/yandex_log.txt');

/**
 * Class YandexMetrikaHandler
 * Encapsulates all logic for the Yandex.Metrika CDP API.
 */
class YandexMetrikaHandler
{
    private $counterId;
    private $oauthToken;

    public function __construct(string $counterId, string $oauthToken)
    {
        $this->counterId = $counterId;
        $this->oauthToken = $oauthToken;
    }

    public function sendEvent(array $params): array
    {
        $yandexClientId = $params['sub_id_16'] ?? null;
        $status = $params['status'] ?? null;

        if (empty($yandexClientId)) {
            return $this->formatResponse(400, false, 'Yandex ClientID (sub_id_16) is missing.');
        }
        if (empty($status)) {
            return $this->formatResponse(400, false, 'Status parameter is missing.');
        }

        $eventPayload = $this->buildEventPayload($yandexClientId, $status, $params);
        $result = $this->executeApiRequest($eventPayload);

        $this->logTransaction($params, $eventPayload, $result);
        
        return $result;
    }

    private function buildEventPayload(string $clientId, string $eventName, array $params): array
    {
        $event = ['event_name' => strtolower($eventName)];
        
        if (strtolower($eventName) === 'sale' && !empty($params['payout'])) {
            $event['event_params'] = [
                'price' => (float)$params['payout'],
                'currency' => strtoupper($params['currency'] ?? 'USD'),
            ];
        }
        
        return [
            'user_profiles' => [[
                'user_id' => $clientId, // Привязка по ClientID
                'events' => [$event],
            ]]
        ];
    }
    
    private function executeApiRequest(array $payload): array
    {
        $apiUrl = "https://api-metrika.yandex.ru/cdp/api/v1/data/upload?counter_id={$this->counterId}";
        $headers = [
            'Authorization: OAuth ' . $this->oauthToken,
            'Content-Type: application/json',
        ];
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $apiUrl,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
        ]);
        
        $responseBody = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $isSuccess = ($httpCode >= 200 && $httpCode < 300);
        $message = $isSuccess ? "Event sent successfully." : "Failed to send event.";

        return $this->formatResponse($httpCode, $isSuccess, $message, json_decode($responseBody, true));
    }

    private function formatResponse(int $httpCode, bool $success, string $message, ?array $response = null): array
    {
        http_response_code($httpCode);
        return ['success' => $success, 'message' => $message, 'yandex_response' => $response];
    }
    
    private function logTransaction(array $request, array $sentData, array $result): void
    {
        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s T'),
            'status' => $result['success'] ? 'SUCCESS' : 'ERROR',
            'incoming_request' => $request,
            'sent_to_yandex' => $sentData,
            'yandex_response' => $result['yandex_response'] ?? null,
        ];
        file_put_contents(LOG_FILE, json_encode($logEntry, JSON_UNESCAPED_UNICODE) . PHP_EOL, FILE_APPEND | LOCK_EX);
    }
}

// =============================================================================
// Точка входа и обработка запроса
// =============================================================================
try {
    $handler = new YandexMetrikaHandler(YM_COUNTER_ID, YM_OAUTH_TOKEN);
    $result = $handler->sendEvent($_GET);
    header('Content-Type: application/json');
    echo json_encode($result);
} catch (Exception $e) {
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Internal Server Error.', 'error' => $e->getMessage()]);
}