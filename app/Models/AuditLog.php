<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

final class AuditLog
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function create(int $companyId, ?int $userId, string $actionKey, string $entityType, ?string $entityId, ?string $details): int
    {
        try {
            $stmt = $this->pdo->prepare(
                'INSERT INTO audit_logs (company_id, user_id, action_key, entity_type, entity_id, details)
                 VALUES (:company_id, :user_id, :action_key, :entity_type, :entity_id, :details)'
            );

            $stmt->execute([
                'company_id' => $companyId,
                'user_id' => $userId,
                'action_key' => $actionKey,
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'details' => $details,
            ]);

            return (int) $this->pdo->lastInsertId();
        } catch (\Throwable $e) {
            error_log('[audit_log_create_error] ' . $e->getMessage());
            return 0;
        }
    }

    public function listByCompany(int $companyId, int $limit = 200): array
    {
        try {
            $stmt = $this->pdo->prepare(
                'SELECT audit_id, user_id, action_key, entity_type, entity_id, details, created_at
                 FROM audit_logs
                 WHERE company_id = :company_id
                 ORDER BY audit_id DESC
                 LIMIT :limit'
            );

            $stmt->bindValue(':company_id', $companyId, PDO::PARAM_INT);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll() ?: [];
        } catch (\Throwable $e) {
            error_log('[audit_log_list_error] ' . $e->getMessage());
            return [];
        }
    }
}
