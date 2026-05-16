<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Core\Database;
use App\Core\RequestContext;

/**
 * Lightweight helper to record activity feed entries.
 * Usage: ActivityFeed::log($db, $context, 'created', 'invoice', 42, 'INV-0042');
 */
final class ActivityFeed
{
    public static function log(Database $db, RequestContext $context, string $action, string $entityType, ?int $entityId = null, ?string $entityLabel = null, ?string $details = null): void
    {
        $company = $context->company();
        $companyId = (int) ($company['company_id'] ?? 0);
        if ($companyId <= 0) return;

        $userId = (int) ($_SESSION['user']['user_id'] ?? 0) ?: null;
        $userName = $_SESSION['user']['full_name'] ?? $_SESSION['user']['email'] ?? null;

        try {
            $db->pdo()->prepare(
                'INSERT INTO activity_feed (company_id, user_id, user_name, action, entity_type, entity_id, entity_label, details, created_at, updated_at)
                 VALUES (:cid, :uid, :un, :action, :etype, :eid, :elabel, :details, NOW(), NOW())'
            )->execute([
                'cid' => $companyId,
                'uid' => $userId,
                'un' => $userName,
                'action' => $action,
                'etype' => $entityType,
                'eid' => $entityId,
                'elabel' => $entityLabel,
                'details' => $details,
            ]);
        } catch (\Throwable $e) {
            // Non-blocking — activity logging should never break flows
        }
    }

    public static function notify(Database $db, RequestContext $context, string $type, string $title, ?string $body = null, ?string $actionUrl = null, ?string $icon = null, ?int $userId = null): void
    {
        $company = $context->company();
        $companyId = (int) ($company['company_id'] ?? 0);
        if ($companyId <= 0) return;

        try {
            $db->pdo()->prepare(
                'INSERT INTO notifications (company_id, user_id, type, title, body, action_url, icon, created_at, updated_at)
                 VALUES (:cid, :uid, :type, :title, :body, :url, :icon, NOW(), NOW())'
            )->execute([
                'cid' => $companyId,
                'uid' => $userId,
                'type' => $type,
                'title' => $title,
                'body' => $body,
                'url' => $actionUrl,
                'icon' => $icon ?? 'fa-bell',
            ]);
        } catch (\Throwable $e) {
            // Non-blocking
        }
    }
}
