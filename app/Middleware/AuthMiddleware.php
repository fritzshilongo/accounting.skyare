<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Database;
use App\Core\RequestContext;

final class AuthMiddleware
{
    public function handle(Database $db, RequestContext $context): bool
    {
        if (!isset($_SESSION['user'])) {
            header('Location: /login');
            return false;
        }

        return true;
    }
}
