<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

final class Expense
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function listByCompany(int $companyId, int $limit = 100): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT expense_id, category, description, amount, date, created_at
             FROM expenses
             WHERE company_id = :company_id
             ORDER BY date DESC, expense_id DESC
             LIMIT :limit'
        );

        $stmt->bindValue(':company_id', $companyId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll() ?: [];
    }

    public function createForCompany(
        int $companyId,
        string $category,
        ?string $description,
        float $amount,
        string $expenseDate
    ): int {
        $stmt = $this->pdo->prepare(
            'INSERT INTO expenses (company_id, category, description, amount, date, created_at, updated_at)
             VALUES (:company_id, :category, :description, :amount, :date, NOW(), NOW())'
        );

        $stmt->execute([
            'company_id' => $companyId,
            'category' => $category,
            'description' => $description,
            'amount' => $amount,
            'date' => $expenseDate,
        ]);

        return (int) $this->pdo->lastInsertId();
    }
}
