<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

final class Company
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function findBySubdomain(string $subdomain, bool $activeOnly = true): ?array
    {
        try {
            $sql = 'SELECT *
                 FROM companies
                 WHERE subdomain = :subdomain';
            $params = ['subdomain' => $subdomain];

            if ($activeOnly) {
                $sql .= ' AND status = :status';
                $params['status'] = 'active';
            }

            $sql .= ' LIMIT 1';

            return $this->fetchOne($sql, $params);
        } catch (\PDOException $exception) {
            return null;
        }
    }

    public function create(string $companyName, string $subdomain): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO companies (company_name, subdomain, status, created_at, updated_at)
             VALUES (:company_name, :subdomain, :status, NOW(), NOW())'
        );

        $stmt->execute([
            'company_name' => $companyName,
            'subdomain' => $subdomain,
            'status' => 'active',
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function findById(string|int $companyId): ?array
    {
        $companyId = (int) $companyId;
        if ($companyId <= 0) {
            return null;
        }

        try {
            return $this->fetchOne(
                'SELECT *
                 FROM companies
                 WHERE company_id = :company_id
                 LIMIT 1',
                ['company_id' => $companyId]
            );
        } catch (\PDOException $exception) {
            return null;
        }
    }

    public function findAny(): ?array
    {
        try {
            return $this->fetchOne(
                'SELECT *
                 FROM companies
                 WHERE status = :status
                 ORDER BY company_id ASC
                 LIMIT 1',
                ['status' => 'active']
            );
        } catch (\PDOException $exception) {
            return null;
        }
    }

    public function findActiveCompanies(): array
    {
        try {
            $stmt = $this->pdo->prepare(
                'SELECT company_id, company_name, subdomain
                 FROM companies
                 WHERE status = :status
                 ORDER BY company_name ASC'
            );
            $stmt->execute(['status' => 'active']);
            $results = $stmt->fetchAll();

            return is_array($results) ? $results : [];
        } catch (\PDOException $exception) {
            error_log('[company_find_active_failed] ' . $exception->getMessage());
            return [];
        }
    }

    public function findAllCompanies(): array
    {
        try {
            $stmt = $this->pdo->prepare(
                'SELECT company_id, company_name, subdomain, status, email
                 FROM companies
                 ORDER BY company_name ASC'
            );
            $stmt->execute();
            $results = $stmt->fetchAll();

            return is_array($results) ? $results : [];
        } catch (\PDOException $exception) {
            error_log('[company_find_all_failed] ' . $exception->getMessage());
            return [];
        }
    }

    public function listActive(): array
    {
        try {
            $stmt = $this->pdo->prepare(
                'SELECT company_id, company_name, subdomain, status
                 FROM companies
                 WHERE status = :status
                 ORDER BY company_name ASC'
            );
            if ($stmt === false) {
                return [];
            }
            $stmt->execute(['status' => 'active']);
            return $stmt->fetchAll() ?: [];
        } catch (\PDOException $exception) {
            error_log('[company_list_active_failed] ' . $exception->getMessage());
            return [];
        }
    }

    public function updateStatus(int $companyId, string $status): bool
    {
        if (!in_array($status, ['active', 'inactive', 'deleted'], true)) {
            return false;
        }

        try {
            $stmt = $this->pdo->prepare(
                'UPDATE companies
                 SET status = :status
                 WHERE company_id = :company_id'
            );

            return $stmt->execute([
                'status' => $status,
                'company_id' => $companyId,
            ]);
        } catch (\Throwable $e) {
            error_log('[company_update_status_failed] ' . $e->getMessage());
            return false;
        }
    }

    public function updateProfile(int $companyId, array $payload): bool
    {
        $allowedColumns = [
            'company_name', 'registration_number', 'phone', 'email', 'address', 'city', 'province', 'postal_code',
            'country', 'tax_number', 'vat_number', 'logo_data', 'bank_name', 'bank_account_holder', 'bank_account_number',
            'bank_account_type', 'bank_branch_code', 'bank_routing_number', 'bank_swift_code', 'bank_iban',
        ];

        $tableColumns = $this->getTableColumns('companies');
        $updateColumns = array_values(array_intersect($allowedColumns, $tableColumns, array_keys($payload)));

        if ($updateColumns === []) {
            error_log('[company_update_failed] no updatable company columns found');
            return false;
        }

        return $this->executeUpdate($companyId, $updateColumns, $payload);
    }

    private function executeUpdate(int $companyId, array $columns, array $payload): bool
    {
        $assignments = array_map(static fn (string $column): string => "{$column} = :{$column}", $columns);
        $sql = 'UPDATE companies SET ' . implode(', ', $assignments) . ' WHERE company_id = :company_id';

        $params = ['company_id' => $companyId];
        foreach ($columns as $column) {
            $params[$column] = $payload[$column] ?? null;
        }

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
    }

    public function missingColumns(array $columns): array
    {
        $tableColumns = $this->getTableColumns('companies');
        if ($tableColumns === []) {
            return $columns;
        }

        return array_values(array_diff($columns, $tableColumns));
    }

    private function getTableColumns(string $table): array
    {
        try {
            $stmt = $this->pdo->prepare('SHOW COLUMNS FROM `' . str_replace('`', '``', $table) . '`');
            $stmt->execute();
            return array_map(static fn ($row) => $row['Field'], $stmt->fetchAll(\PDO::FETCH_ASSOC));
        } catch (\Throwable $e) {
            error_log('[company_get_table_columns_failed] ' . $e->getMessage());
            return [];
        }
    }

    private function fetchOne(string $sql, array $params = []): ?array
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();

        return $row ?: null;
    }
}
