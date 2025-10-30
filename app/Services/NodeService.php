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

    // Добавить новый метод для уведомления о новой заявке компании
    public static function notifyNewCompanyRequest(array $requestData): array
    {
        $url = rtrim(config('services.node.http_url'), '/') . '/company-request/new';

        return self::sendRequestWithRetry(
            $url,
            array(
                'company_name' => $requestData['company_name'],
                'phone' => $requestData['phone'],
                'id' => $requestData['id'],
            ),
            'post'
        );
    }

    // Новый метод: оповестить о том, что список/счетчик заявок изменился
    public static function notifyCompanyRequestsRefresh(): array
    {
        $url = rtrim(config('services.node.http_url'), '/') . '/company-request/refresh';
        return self::sendRequestWithRetry($url, array(), 'post');
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

    public static function callVisibilityUpdate(): array
    {
        $url = rtrim(config('services.node.http_url'), '/') . '/visibility/update';

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

    public static function sendPushNotification(string $fcmToken, string $title, string $message, array $data = []): array
    {
        $url = rtrim(config('services.node.http_url'), '/') . '/push/send';

        return self::sendRequestWithRetry(
            $url,
            array(
                'fcm_token' => $fcmToken,
                'title' => $title,
                'message' => $message,
                'data' => $data,
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


                // Детальное логирование ответа my code***
                $statusCode = $response->status();
                $responseBody = $response->body();

                logger()->info("NodeService HTTP request", [
                    'url' => $url,
                    'method' => $method,
                    'payload' => $payload,
                    'status_code' => $statusCode,
                    'response_body' => $responseBody,
                    'is_ok' => $response->ok()
                ]);

                if ($response->ok()) {
                    $responseJson = $response->json();

                    if (isset($responseJson['status']) && $responseJson['status'] === 'ok') {
                        return $responseJson;
                    } else {
                        logger()->warning("NodeService response status is not OK", [
                            'url' => $url,
                            'response_json' => $responseJson
                        ]);
                    }
                } else {
                    logger()->warning("NodeService HTTP response is not OK", [
                        'url' => $url,
                        'status_code' => $statusCode,
                        'response_body' => $responseBody
                    ]);
                }

                // end my code ****


                // if ($response->ok() && $response->json('status') === 'ok') {
                //     return $response->json();
                // }
            } catch (\Exception $e) {
                // $u = config('services.node.http_url');
                // logger()->warning("Попытка #$attempt к $url не удалась ($u): " . $e->getMessage());

                $u = config('services.node.http_url');
                logger()->error("NodeService exception", [
                    'url' => $url,
                    'base_url' => $u,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'attempt' => $attempt + 1
                ]);

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
