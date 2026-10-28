<?php
/**
 * Minimal RoomZ PMS API client for a lodge website (server side).
 * PHP 7.2+, needs the curl extension. Keep the API key in server config,
 * never in browser JavaScript.
 *
 *   $roomz = new RoomzClient('https://www.macsoftlabs.co.zw/mukusu/api/v1', getenv('ROOMZ_API_KEY'));
 *   $quote = $roomz->get('/rates/quote', ['property_id' => 1, 'room_type_id' => 1,
 *              'check_in' => '2026-12-10', 'check_out' => '2026-12-12', 'adults' => 2]);
 */
class RoomzApiException extends Exception
{
    public $errorCode;
    public $details;
    public $requestId;
    public $httpStatus;

    public function __construct(int $httpStatus, string $code, string $message, array $details = [], string $requestId = '')
    {
        parent::__construct($message);
        $this->httpStatus = $httpStatus;
        $this->errorCode  = $code;
        $this->details    = $details;
        $this->requestId  = $requestId;
    }
}

class RoomzClient
{
    private $base;
    private $key;
    private $timeout;

    public function __construct(string $baseUrl, string $apiKey, int $timeoutSeconds = 20)
    {
        $this->base = rtrim($baseUrl, '/');
        $this->key = $apiKey;
        $this->timeout = $timeoutSeconds;
    }

    public function get(string $path, array $query = [])
    {
        return $this->request('GET', $path . ($query ? '?' . http_build_query($query) : ''));
    }

    /** POST/PATCH with automatic safe retries: the same Idempotency-Key is reused on every attempt. */
    public function send(string $method, string $path, array $body, string $idempotencyKey = '')
    {
        if ($idempotencyKey === '') {
            $idempotencyKey = bin2hex(random_bytes(16));
        }
        return $this->request($method, $path, $body, $idempotencyKey);
    }

    private function request(string $method, string $path, array $body = null, string $idemKey = '')
    {
        $attempt = 0;
        while (true) {
            $attempt++;
            $ch = curl_init($this->base . $path);
            $headers = ['Authorization: Bearer ' . $this->key, 'Accept: application/json'];
            if ($body !== null) {
                $headers[] = 'Content-Type: application/json';
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
            }
            if ($idemKey !== '') {
                $headers[] = 'Idempotency-Key: ' . $idemKey;
            }
            curl_setopt_array($ch, [
                CURLOPT_CUSTOMREQUEST  => $method,
                CURLOPT_HTTPHEADER     => $headers,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => $this->timeout,
            ]);
            $raw = curl_exec($ch);
            $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $netError = curl_errno($ch);
            curl_close($ch);

            $retryable = $netError !== 0 || $status >= 500 || $status === 429
                || ($status === 409 && strpos((string)$raw, 'request_in_progress') !== false);
            if ($retryable && $attempt < 4) {
                sleep($attempt);      // 1s, 2s, 3s
                continue;
            }
            if ($netError !== 0) {
                throw new RoomzApiException(0, 'network_error', 'Could not reach the RoomZ API.');
            }
            $json = json_decode((string)$raw, true);
            if (!is_array($json)) {
                throw new RoomzApiException($status, 'bad_response', 'Unexpected response from the RoomZ API.');
            }
            if (empty($json['ok'])) {
                $e = $json['error'] ?? [];
                throw new RoomzApiException($status, (string)($e['code'] ?? 'error'), (string)($e['message'] ?? 'Error'),
                    (array)($e['details'] ?? []), (string)($json['meta']['request_id'] ?? ''));
            }
            return $json['data'];
        }
    }
}
