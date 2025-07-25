<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class NodeService
{
    public static function callServiceRefresh(): array
    {
        $url = rtrim(config('services.node.http_url'), '/') . '/call/refresh';

        return self::sendRequestWithRetry($url, array());
    }


    public static function callPickupsRefresh(): array
    {
        $url = rtrim(config('services.node.http_url'), '/') . '/call/clients_refresh';

        return self::sendRequestWithRetry($url, array());
    }

    public static function callLogsRefresh(): array
    {
        $url = rtrim(config('services.node.http_url'), '/') . '/call/logs_refresh';

        return self::sendRequestWithRetry($url, array());
    }

    public static function callUserRefresh(): array
    {
        $url = rtrim(config('services.node.http_url'), '/') . '/call/user_refresh';

        return self::sendRequestWithRetry($url, array());
    }

    public static function callShowVerificationCode(int $id, string $operator_id): array
    {
        $url = rtrim(config('services.node.http_url'), '/') . '/call/show_verification_code/' . $operator_id . '/' . $id;

        return self::sendRequestWithRetry($url, array());
    }

    public static function callHideVerificationCode(int $id, string $operator_id): array
    {
        $url = rtrim(config('services.node.http_url'), '/') . '/call/hide_verification_code/' . $operator_id . '/' . $id;

        return self::sendRequestWithRetry($url, array());
    }

    public static function sendSmsCode(String $code, string $phone): array
    {
        $url = rtrim(config('services.node.http_url'), '/') . '/sms/send/code';

        return self::sendRequestWithRetry(
            $url,
            array(
                'code' => $code,
                'phone' => $phone,
            ),
            'post'
        );
    }

    // Приватный метод для повторных попыток
    private static function sendRequestWithRetry(string $url, array $payload = [], string $method = 'get', int $maxAttempts = 1, int $delayMs = 1000): array
    {
        $attempt = 0;

        while ($attempt < $maxAttempts) {
            try {
                if (strtolower($method) === 'post') {
                    $response = Http::post($url, $payload);
                } else {
                    $response = Http::get($url, $payload);
                }

                if ($response->ok() && $response->json('status') === 'ok') {
                    return $response->json();
                }
            } catch (\Exception $e) {
                $u = config('services.node.http_url');
                logger()->warning("Попытка #$attempt к $url не удалась ($u): " . $e->getMessage());
            }

            usleep($delayMs * 1000); // Задержка в миллисекундах
            $attempt++;
        }

        return [
            'status' => 'error',
            'message' => "Не удалось получить статус OK от $url после $maxAttempts попыток",
        ];
    }
}
