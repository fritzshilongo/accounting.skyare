<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

final class InventoryMovement
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function listByCompany(int $companyId, int $limit = 200): array
    {
        $rows = [];

        // Try modern inventory_movements table first (LEFT JOIN so deleted-product rows still appear)
        try {
            $stmt = $this->pdo->prepare(
                'SELECT m.movement_id, m.product_id,
                        COALESCE(p.name, "[Deleted Product]") AS product_name,
                        COALESCE(p.sku, "") AS sku,
                        m.movement_type,
                        m.quantity, m.qty_before, m.qty_after, m.note,
                        m.created_by,
                        COALESCE(u.full_name, CONCAT("User #", m.created_by)) AS actor_name,
                        m.created_at
                 FROM inventory_movements m
                 LEFT JOIN products p ON p.product_id = m.product_id
                 LEFT JOIN users u ON u.id = m.created_by
                 WHERE m.company_id = :company_id
                 ORDER BY m.movement_id DESC
                 LIMIT :limit'
            );
            $stmt->bindValue(':company_id', $companyId, PDO::PARAM_INT);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            $rows = $stmt->fetchAll() ?: [];
        } catch (\Throwable $e) {
            // table may not exist yet — fall through to legacy
        }

        // Also check legacy inventory table if primary returned nothing
        if (empty($rows)) {
            try {
                $stmt = $this->pdo->prepare(
                    'SELECT i.inventory_id AS movement_id, i.product_id,
                            p.name AS product_name, p.sku,
                            i.movement_type,
                            i.quantity, i.qty_before, i.qty_after, i.note,
                            i.actor_id AS created_by,
                            COALESCE(i.actor_name, CONCAT("User #", i.actor_id)) AS actor_name,
                            i.created_at
                     FROM inventory i
                     INNER JOIN products p ON p.product_id = i.product_id
                     WHERE p.company_id = :company_id
                     ORDER BY i.inventory_id DESC
                     LIMIT :limit'
                );
                $stmt->bindValue(':company_id', $companyId, PDO::PARAM_INT);
                $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
                $stmt->execute();
                $rows = $stmt->fetchAll() ?: [];
            } catch (\Throwable $e) {
                // legacy table also unavailable
            }
        }

        return $rows;
    }

    /**
     * Return movements with optional filters on product, type, date range, and keyword search.
     *
     * @return array<int, array<string, mixed>>
     */
    public function listFiltered(
        int    $companyId,
        int    $productId    = 0,
        string $movementType = '',
        string $fromDate     = '',
        string $toDate       = '',
        string $search       = '',
        int    $limit        = 1000
    ): array {
        $sql = 'SELECT m.movement_id, m.product_id,
                       COALESCE(p.name, "[Deleted Product]") AS product_name,
                       COALESCE(p.sku, "") AS sku,
                       m.movement_type, m.quantity, m.qty_before, m.qty_after,
                       m.note, m.created_by,
                       COALESCE(u.full_name, CONCAT("User #", m.created_by)) AS actor_name,
                       m.created_at
                FROM inventory_movements m
                LEFT JOIN products p ON p.product_id = m.product_id
                LEFT JOIN users   u ON u.id         = m.created_by
                WHERE m.company_id = :company_id';

        $params = ['company_id' => $companyId];

        if ($productId > 0) {
            $sql .= ' AND m.product_id = :product_id';
            $params['product_id'] = $productId;
        }
        if ($movementType !== '') {
            $sql .= ' AND m.movement_type = :movement_type';
            $params['movement_type'] = $movementType;
        }
        if ($fromDate !== '') {
            $sql .= ' AND DATE(m.created_at) >= :from_date';
            $params['from_date'] = $fromDate;
        }
        if ($toDate !== '') {
            $sql .= ' AND DATE(m.created_at) <= :to_date';
            $params['to_date'] = $toDate;
        }

        $sql .= ' ORDER BY m.movement_id DESC LIMIT :limit';

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            foreach ($params as $key => $value) {
                $stmt->bindValue(':' . $key, $value);
            }
            $stmt->execute();
            $rows = $stmt->fetchAll() ?: [];
        } catch (\Throwable $e) {
            $rows = [];
        }

        // Also check legacy inventory table if primary returned nothing
        if (empty($rows)) {
            $rows = [];
            $legacySql = 'SELECT i.inventory_id AS movement_id, i.product_id,
                                 p.name AS product_name, p.sku,
                                 i.movement_type, i.quantity, i.qty_before, i.qty_after,
                                 i.note, i.actor_id AS created_by,
                                 COALESCE(i.actor_name, CONCAT("User #", i.actor_id)) AS actor_name,
                                 i.created_at
                          FROM inventory i
                          INNER JOIN products p ON p.product_id = i.product_id
                          WHERE p.company_id = :company_id';

            $legacyParams = ['company_id' => $companyId];

            if ($productId > 0) {
                $legacySql .= ' AND i.product_id = :product_id';
                $legacyParams['product_id'] = $productId;
            }
            if ($movementType !== '') {
                $legacySql .= ' AND i.movement_type = :movement_type';
                $legacyParams['movement_type'] = $movementType;
            }
            if ($fromDate !== '') {
                $legacySql .= ' AND DATE(i.created_at) >= :from_date';
                $legacyParams['from_date'] = $fromDate;
            }
            if ($toDate !== '') {
                $legacySql .= ' AND DATE(i.created_at) <= :to_date';
                $legacyParams['to_date'] = $toDate;
            }

            $legacySql .= ' ORDER BY i.inventory_id DESC LIMIT :limit';
            try {
                $legacyStmt = $this->pdo->prepare($legacySql);
                $legacyStmt->bindValue(':limit', $limit, PDO::PARAM_INT);
                foreach ($legacyParams as $key => $value) {
                    $legacyStmt->bindValue(':' . $key, $value);
                }
                $legacyStmt->execute();
                $rows = $legacyStmt->fetchAll() ?: [];
            } catch (\Throwable $e) {
                // legacy table also unavailable
            }
        }

        // Optional keyword search applied in PHP (covers note, product name, actor)
        if ($search !== '') {
            $needle = mb_strtolower($search);
            $rows = array_values(array_filter($rows, static function (array $r) use ($needle): bool {
                $hay = mb_strtolower(
                    (string) ($r['product_name']   ?? '') . ' ' .
                    (string) ($r['sku']            ?? '') . ' ' .
                    (string) ($r['movement_type']  ?? '') . ' ' .
                    (string) ($r['note']           ?? '') . ' ' .
                    (string) ($r['actor_name']     ?? '')
                );
                return str_contains($hay, $needle);
            }));
        }

        return $rows;
    }

    public function createForCompany(int $companyId, int $productId, string $movementType, float $quantity, float $qtyBefore, float $qtyAfter, ?string $note, ?int $createdBy): int
    {
        try {
            $stmt = $this->pdo->prepare(
                'INSERT INTO inventory_movements (company_id, product_id, movement_type, quantity, qty_before, qty_after, note, created_by, created_at)
                 VALUES (:company_id, :product_id, :movement_type, :quantity, :qty_before, :qty_after, :note, :created_by, NOW())'
            );
            $stmt->execute([
                'company_id'    => $companyId,
                'product_id'    => $productId,
                'movement_type' => $movementType,
                'quantity'      => $quantity,
                'qty_before'    => $qtyBefore,
                'qty_after'     => $qtyAfter,
                'note'          => $note,
                'created_by'    => $createdBy,
            ]);

            return (int) $this->pdo->lastInsertId();
        } catch (\Throwable $e) {
            $actorName = null;
            if ($createdBy !== null && $createdBy > 0) {
                try {
                    $userStmt = $this->pdo->prepare('SELECT COALESCE(full_name, name) AS full_name FROM users WHERE user_id = :user_id LIMIT 1');
                    $userStmt->execute(['user_id' => $createdBy]);
                    $actorName = (string) ($userStmt->fetchColumn() ?: '');
                } catch (\Throwable $ignored) {
                    try {
                        $userStmt = $this->pdo->prepare('SELECT COALESCE(full_name, name) AS full_name FROM users WHERE id = :user_id LIMIT 1');
                        $userStmt->execute(['user_id' => $createdBy]);
                        $actorName = (string) ($userStmt->fetchColumn() ?: '');
                    } catch (\Throwable $ignored2) {
                        $actorName = null;
                    }
                }
            }

            $stmt = $this->pdo->prepare(
                'INSERT INTO inventory (product_id, qty_before, quantity, qty_after, movement_type, note, actor_id, actor_name)
                 VALUES (:product_id, :qty_before, :quantity, :qty_after, :movement_type, :note, :actor_id, :actor_name)'
            );
            $stmt->execute([
                'product_id' => $productId,
                'qty_before' => $qtyBefore,
                'quantity' => $quantity,
                'qty_after' => $qtyAfter,
                'movement_type' => $movementType,
                'note' => $note,
                'actor_id' => $createdBy,
                'actor_name' => $actorName,
            ]);

            return (int) $this->pdo->lastInsertId();
        }
    }
}
