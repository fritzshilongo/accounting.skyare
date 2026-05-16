<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\RequestContext;
use App\Core\View;
use App\Models\AuditLog;

final class AuditTrailController
{
    public static function index(Database $db, RequestContext $context): void
    {
        $companyId = (int) ($context->company()['company_id'] ?? 0);
        $sessionCompanyId = (int) ($_SESSION['user']['company_id'] ?? 0);
        if ($companyId <= 0 || $sessionCompanyId <= 0 || $companyId !== $sessionCompanyId) {
            http_response_code(403);
            echo 'Tenant context is invalid.';
            return;
        }

        $rows = (new AuditLog($db->pdo()))->listByCompany($companyId);
        View::render('audit/index', [
            'company' => $context->company(),
            'rows' => $rows,
        ]);
    }
}
