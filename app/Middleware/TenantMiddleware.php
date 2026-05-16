<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Database;
use App\Core\RequestContext;

final class TenantMiddleware
{
    public function handle(Database $db, RequestContext $context): bool
    {
        $company = $context->company();
        if ($company === null) {
            http_response_code(404);
            echo 'Company not found for this subdomain.';
            return false;
        }

        if (($company['status'] ?? 'active') !== 'active') {
            http_response_code(403);
            echo 'Company is inactive.';
            return false;
        }

        return true;
    }
}
