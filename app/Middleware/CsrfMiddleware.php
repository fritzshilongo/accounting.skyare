<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Database;
use App\Core\RequestContext;

final class CsrfMiddleware
{
    public function handle(Database $db, RequestContext $context): bool
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            return true;
        }

        $token = $_POST['_token'] ?? '';
        $sessionToken = $_SESSION['_token'] ?? '';

        if ($token === '' || $sessionToken === '' || !hash_equals($sessionToken, $token)) {
            http_response_code(419);
            echo 'Invalid CSRF token.';
            return false;
        }

        return true;
    }

    public static function token(): string
    {
        if (function_exists('csrf_token')) {
            $token = (string) csrf_token();
            if ($token !== '') {
                $_SESSION['_token'] = $token;
                return $token;
            }
        }

        if (!isset($_SESSION['_token']) || (string) $_SESSION['_token'] === '') {
            $_SESSION['_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['_token'];
    }
}
