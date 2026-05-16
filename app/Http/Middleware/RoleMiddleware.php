<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, $role)
    {
        $userRole = $_SESSION['user']['role_key']
            ?? $request->session()->get('user.role_key');

        if (empty($userRole) || $userRole !== $role) {
            abort(403, 'Access denied.');
        }

        return $next($request);
    }
}
