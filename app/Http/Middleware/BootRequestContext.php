<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Core\RequestContext;
use App\Core\Database;
use Illuminate\Support\Facades\View;

class BootRequestContext
{
    /**
     * Handle an incoming request by booting the RequestContext with tenant identification.
     *
     * @param  Request  $request
     * @param  Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Boot the RequestContext singleton with the current HTTP request
        try {
            $context = app()->make(RequestContext::class);
            $db = app()->make(Database::class);
            if (!$context->company()) {  // Only boot if not already booted
                $context->boot($db);
            }

            View::share('tenantCompany', $context->company());
            View::share('tenantUser', $_SESSION['user'] ?? null);
        } catch (\Symfony\Component\HttpKernel\Exception\HttpExceptionInterface $e) {
            throw $e;
        } catch (\Throwable $e) {
            error_log("RequestContext middleware boot failed: " . $e->getMessage());
        }

        return $next($request);
    }
}
