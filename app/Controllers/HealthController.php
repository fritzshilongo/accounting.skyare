<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\RequestContext;

final class HealthController
{
    public static function ping(Database $db, RequestContext $context): void
    {
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'ok',
            'app' => $context->appConfig()['app_name'],
            'host' => $context->host(),
            'company' => $context->company(),
        ], JSON_UNESCAPED_SLASHES);
    }
}
