<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

final class JournalEntry
{
    public function __construct(private PDO $pdo) {}

    /**
     * Return all journal entries for a company, optionally within a date range.
     *
     * @return array<int, array<string, mixed>>
     */
    public function listByCompany(
        int    $companyId,
        ?string $from  = null,
        ?string $to    = null,
        int    $limit  = 500
    ): array {
        $sql    = 'SELECT entry_id, date, account_code, reference, description, debit_amount, credit_amount, created_at
                   FROM journal_entries
                   WHERE company_id = :company_id';
        $params = ['company_id' => $companyId];

        if ($from !== null && $from !== '') {
            $sql .= ' AND date >= :from';
            $params['from'] = $from;
        }
        if ($to !== null && $to !== '') {
            $sql .= ' AND date <= :to';
            $params['to'] = $to;
        }

        $sql .= ' ORDER BY date ASC, entry_id ASC LIMIT :limit';

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            foreach ($params as $key => $value) {
                $stmt->bindValue(':' . $key, $value);
            }
            $stmt->execute();
            return $stmt->fetchAll() ?: [];
        } catch (\Throwable $e) {
            error_log('[journal_entry_list_error] ' . $e->getMessage());
            return [];
        }
    }

    /** Insert a new journal entry and return the new entry_id. */
    public function createForCompany(
        int     $companyId,
        string  $entryDate,
        string  $accountCode,
        ?string $reference,
        ?string $description,
        float   $debit,
        float   $credit,
        ?int    $createdBy = null
    ): int {
        $stmt = $this->pdo->prepare(
            'INSERT INTO journal_entries
                (company_id, date, account_code, reference, description, debit_amount, credit_amount, created_at, updated_at)
             VALUES
                (:company_id, :date, :account_code, :reference, :description, :debit_amount, :credit_amount, NOW(), NOW())'
        );
        $stmt->execute([
            'company_id'    => $companyId,
            'date'          => $entryDate,
            'account_code'  => $accountCode,
            'reference'     => $reference,
            'description'   => $description,
            'debit_amount'  => $debit,
            'credit_amount' => $credit,
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    /** Delete a single entry that belongs to the given company. */
    public function deleteForCompany(int $entryId, int $companyId): bool
    {
        try {
            $stmt = $this->pdo->prepare(
                'DELETE FROM journal_entries WHERE entry_id = :entry_id AND company_id = :company_id'
            );
            $stmt->execute(['entry_id' => $entryId, 'company_id' => $companyId]);
            return $stmt->rowCount() > 0;
        } catch (\Throwable $e) {
            error_log('[journal_entry_delete_error] ' . $e->getMessage());
            return false;
        }
    }
}
