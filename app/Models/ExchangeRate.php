<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

final class ExchangeRate
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function listByCompany(int $companyId, int $limit = 200): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT rate_id, from_currency, to_currency, rate, effective_date, created_at
             FROM exchange_rates
             WHERE company_id = :company_id
             ORDER BY effective_date DESC, rate_id DESC
             LIMIT :limit'
        );
        $stmt->bindValue(':company_id', $companyId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll() ?: [];
    }

    public function createForCompany(int $companyId, string $fromCurrency, string $toCurrency, float $rate, string $effectiveDate): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO exchange_rates (company_id, from_currency, to_currency, rate, effective_date, created_at, updated_at)
             VALUES (:company_id, :from_currency, :to_currency, :rate, :effective_date, NOW(), NOW())'
        );
        $stmt->execute([
            'company_id' => $companyId,
            'from_currency' => strtoupper($fromCurrency),
            'to_currency' => strtoupper($toCurrency),
            'rate' => $rate,
            'effective_date' => $effectiveDate,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function deleteForCompany(int $rateId, int $companyId): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM exchange_rates WHERE rate_id = :rate_id AND company_id = :company_id');
        $stmt->execute(['rate_id' => $rateId, 'company_id' => $companyId]);

        return $stmt->rowCount() > 0;
    }
}
