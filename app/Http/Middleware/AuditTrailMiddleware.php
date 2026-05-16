<?php

namespace App\Http\Middleware;

use App\Core\AuditLogger;
use App\Core\Database;
use App\Core\RequestContext;
use Closure;
use Illuminate\Http\Request;

class AuditTrailMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        try {
            $response = $next($request);
        } catch (\Throwable $e) {
            $this->logRequest($request, 500, true, $e->getMessage());
            throw $e;
        }

        if (!$this->shouldLog($request)) {
            return $response;
        }

        $statusCode = method_exists($response, 'getStatusCode') ? $response->getStatusCode() : 200;
        $this->logRequest($request, $statusCode, false, null);

        return $response;
    }

    private function logRequest(Request $request, int $statusCode, bool $failed, ?string $error): void
    {
        try {
            $db = app()->make(Database::class);
            $context = app()->make(RequestContext::class);

            $method = strtolower($request->method());
            $path = '/' . ltrim($request->path(), '/');

            $inputKeys = array_keys($request->except([
                '_token',
                'password',
                'password_confirm',
                'password_confirmation',
                'current_password',
                'DB_PASSWORD',
                'MAIL_PASSWORD',
            ]));

            $details = [
                'method' => strtoupper($method),
                'path' => $path,
                'status_code' => $statusCode,
                'failed' => $failed,
                'error' => $error,
                'ip' => $request->ip(),
                'query' => $request->query(),
                'input_keys' => $inputKeys,
                'user_agent' => substr((string) $request->userAgent(), 0, 250),
                'at' => date('c'),
            ];

            AuditLogger::log(
                $db,
                $context,
                'request.' . $method,
                $path,
                null,
                json_encode($details, JSON_UNESCAPED_SLASHES)
            );
        } catch (\Throwable $e) {
            error_log('AuditTrailMiddleware log failed: ' . $e->getMessage());
        }
    }

    private function shouldLog(Request $request): bool
    {
        if ($request->method() === 'OPTIONS') {
            return false;
        }

        $path = ltrim($request->path(), '/');
        $ignoredPrefixes = [
            'assets/',
            'css/',
            'js/',
            'images/',
            '_debugbar/',
            'vendor/',
        ];

        foreach ($ignoredPrefixes as $prefix) {
            if (str_starts_with($path, $prefix)) {
                return false;
            }
        }

        if ($path === 'favicon.ico') {
            return false;
        }

        return true;
    }
}