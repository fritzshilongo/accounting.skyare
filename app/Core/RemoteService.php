<?php

declare(strict_types=1);

namespace App\Core;

final class RemoteService
{
    public static function postJson(string $url, array $payload, int $timeoutSeconds = 5): array
    {
        $ch = curl_init($url);
        if ($ch === false) {
            return ['ok' => false, 'error' => 'Unable to initialize cURL'];
        }

        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_SLASHES),
            CURLOPT_TIMEOUT => $timeoutSeconds,
        ]);

        $body = curl_exec($ch);
        $error = curl_error($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);

        if ($body === false) {
            return ['ok' => false, 'error' => $error ?: 'Request failed'];
        }

        $decoded = json_decode($body, true);
        return ['ok' => $status >= 200 && $status < 300, 'status' => $status, 'body' => $decoded];
    }
}
