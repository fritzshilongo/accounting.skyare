<?php

declare(strict_types=1);

namespace App\Core;

use App\Models\AuditLog;

final class AuditLogger
{
    public static function log(Database $db, RequestContext $context, string $actionKey, string $entityType, ?string $entityId = null, ?string $details = null): void
    {
        $companyId = (int) ($context->company()['company_id'] ?? 0);
        if ($companyId <= 0) {
            return;
        }

        $userId = (int) ($_SESSION['user']['user_id'] ?? 0);
        $model = new AuditLog($db->pdo());
        $model->create($companyId, $userId > 0 ? $userId : null, $actionKey, $entityType, $entityId, $details);
    }
}
