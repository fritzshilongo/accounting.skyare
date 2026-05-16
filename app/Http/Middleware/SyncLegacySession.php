<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SyncLegacySession
{
    public function handle(Request $request, Closure $next)
    {
        $session = $request->session();

        // Keep legacy $_SESSION in sync because many custom controllers read it directly.
        if ($session->has('user')) {
            $_SESSION['user'] = $session->get('user');
        } elseif (isset($_SESSION['user'])) {
            $session->put('user', $_SESSION['user']);
        }

        // Align CSRF token used by legacy views with Laravel's token source.
        $token = $session->token();
        if (is_string($token) && $token !== '') {
            $_SESSION['_token'] = $token;
        }

        return $next($request);
    }
}
