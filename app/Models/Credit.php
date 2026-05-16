<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\SchemaCompat;
use PDO;

final class Credit
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function listByCompany(int $companyId): array
    {
        $totalExpr = $this->creditTotalExpression();
        $paidExpr = $this->creditAmountPaidExpression();

        $stmt = $this->pdo->prepare(
            'SELECT c.credit_id,
                    ' . $this->creditColumnExpr('credit_no', 'credit_no', "''") . ',
                    ' . $this->creditColumnExpr('customer_id', 'customer_id', 'NULL') . ',
                    ' . $this->creditColumnExpr('customer_name', 'customer_name', "''") . ',
                    ' . $this->creditColumnExpr('amount', 'amount', '0') . ',
                    ' . $this->creditColumnExpr('interest_type', 'interest_type', "'flat'") . ',
                    ' . $this->creditColumnExpr('interest_percent', 'interest_percent', '0') . ',
                    ' . $this->creditColumnExpr('interest_amount', 'interest_amount', '0') . ',
                    ' . $totalExpr . ' AS total_amount,
                    ' . $paidExpr . ' AS amount_paid,
                    GREATEST(' . $totalExpr . ' - ' . $paidExpr . ', 0) AS outstanding,
                    ' . $this->creditColumnExpr('due_date', 'due_date', 'NULL') . ',
                    ' . $this->creditColumnExpr('issue_date', 'issue_date', 'NULL') . ',
                    ' . $this->creditColumnExpr('last_payment_date', 'last_payment_date', 'NULL') . ',
                    ' . $this->creditColumnExpr('status', 'status', "'ACTIVE'") . ',
                    ' . $this->creditColumnExpr('reason', 'reason', 'NULL') . ',
                    ' . $this->creditColumnExpr('write_off_reason', 'write_off_reason', 'NULL') . ',
                    ' . $this->creditColumnExpr('write_off_amount', 'write_off_amount', '0') . ',
                    ' . $this->creditColumnExpr('created_at', 'created_at', 'NULL') . '
             FROM credits c
             WHERE c.company_id = :company_id
             ORDER BY c.credit_id DESC'
        );
        $stmt->execute(['company_id' => $companyId]);
        return $stmt->fetchAll() ?: [];
    }

    public function reconcileByCompany(int $companyId): void
    {
        if (!SchemaCompat::hasColumn('credit_payments', 'company_id')) {
            return;
        }

        $stmt = $this->pdo->prepare(
            'UPDATE credits c
             LEFT JOIN (
                SELECT credit_id, COALESCE(SUM(amount), 0) AS paid_sum, MAX(payment_date) AS last_payment_date
                FROM credit_payments
                WHERE company_id = :company_id
                GROUP BY credit_id
             ) p ON p.credit_id = c.credit_id
             SET c.amount_paid = LEAST(c.total_amount, COALESCE(p.paid_sum, 0)),
                 c.last_payment_date = COALESCE(p.last_payment_date, c.last_payment_date),
                 c.status = CASE
                    WHEN c.status = :bad_debt_current THEN :bad_debt_keep
                    WHEN LEAST(c.total_amount, COALESCE(p.paid_sum, 0)) >= c.total_amount THEN :paid
                    WHEN c.due_date IS NOT NULL AND c.due_date < CURDATE() THEN :overdue
                    ELSE :active
                 END
             WHERE c.company_id = :company_id_scope'
        );
        $stmt->execute([
            'company_id' => $companyId,
            'company_id_scope' => $companyId,
            'paid' => 'PAID',
            'overdue' => 'OVERDUE',
            'active' => 'ACTIVE',
            'bad_debt_current' => 'BAD_DEBT',
            'bad_debt_keep' => 'BAD_DEBT',
        ]);
    }

    public function paymentListByCompany(int $companyId): array
    {
        try {
            if (SchemaCompat::hasColumn('credit_payments', 'company_id')) {
                $stmt = $this->pdo->prepare(
                    'SELECT ' . $this->creditPaymentColumnExpr('payment_date', 'payment_date', 'NULL') . ',
                            ' . $this->creditPaymentColumnExpr('customer_name', 'customer_name', "''") . ',
                            ' . $this->creditPaymentColumnExpr('amount', 'amount', '0') . ',
                            ' . $this->creditPaymentColumnExpr('payment_method', 'payment_method', 'NULL') . ',
                            ' . $this->creditPaymentColumnExpr('reference', 'reference', 'NULL') . ',
                            ' . $this->creditPaymentColumnExpr('credit_id', 'credit_id', 'NULL') . '
                     FROM credit_payments
                     WHERE company_id = :company_id
                     ORDER BY payment_id DESC LIMIT 50'
                );
                $stmt->execute(['company_id' => $companyId]);

                return $stmt->fetchAll() ?: [];
            }

            $stmt = $this->pdo->prepare(
                'SELECT
                    cp.' . (SchemaCompat::hasColumn('credit_payments', 'payment_date') ? 'payment_date' : 'created_at') . ' AS payment_date,
                    COALESCE(cp.customer_name, c.customer_name, "") AS customer_name,
                    COALESCE(cp.amount, 0) AS amount,
                    ' . $this->creditPaymentColumnExpr('payment_method', 'payment_method', 'NULL') . ',
                    ' . $this->creditPaymentColumnExpr('reference', 'reference', 'NULL') . ',
                    cp.credit_id AS credit_id
                 FROM credit_payments cp
                 INNER JOIN credits c ON c.credit_id = cp.credit_id
                 WHERE c.company_id = :company_id
                 ORDER BY cp.payment_id DESC
                 LIMIT 50'
            );
            $stmt->execute(['company_id' => $companyId]);

            return $stmt->fetchAll() ?: [];
        } catch (\Throwable $e) {
            error_log('paymentListByCompany SchemaCompat error: ' . $e->getMessage());
        }

        // Direct fallback
        try {
            $stmt = $this->pdo->prepare(
                'SELECT cp.*, c.customer_name
                 FROM credit_payments cp
                 INNER JOIN credits c ON c.credit_id = cp.credit_id
                 WHERE c.company_id = :company_id
                 ORDER BY cp.payment_id DESC LIMIT 50'
            );
            $stmt->execute(['company_id' => $companyId]);
            return $stmt->fetchAll() ?: [];
        } catch (\Throwable $e2) {
            error_log('paymentListByCompany fallback error: ' . $e2->getMessage());
            return [];
        }
    }

    public function paymentListByCreditForCompany(int $companyId, int $creditId): array
    {
        try {
            if (SchemaCompat::hasColumn('credit_payments', 'company_id')) {
                $stmt = $this->pdo->prepare(
                    'SELECT payment_id,
                            ' . $this->creditPaymentColumnExpr('payment_date', 'payment_date', 'NULL') . ',
                            ' . $this->creditPaymentColumnExpr('amount', 'amount', '0') . ',
                            ' . $this->creditPaymentColumnExpr('payment_method', 'payment_method', 'NULL') . ',
                            ' . $this->creditPaymentColumnExpr('reference', 'reference', 'NULL') . ',
                            ' . $this->creditPaymentColumnExpr('created_at', 'created_at', 'NULL') . '
                     FROM credit_payments
                     WHERE company_id = :company_id AND credit_id = :credit_id
                     ORDER BY payment_date ASC, payment_id ASC'
                );
                $stmt->execute(['company_id' => $companyId, 'credit_id' => $creditId]);

                return $stmt->fetchAll() ?: [];
            }

            $stmt = $this->pdo->prepare(
                'SELECT cp.payment_id,
                        cp.' . (SchemaCompat::hasColumn('credit_payments', 'payment_date') ? 'payment_date' : 'created_at') . ' AS payment_date,
                        COALESCE(cp.amount, 0) AS amount,
                        ' . $this->creditPaymentColumnExpr('payment_method', 'payment_method', 'NULL') . ',
                        ' . $this->creditPaymentColumnExpr('reference', 'reference', 'NULL') . ',
                        ' . $this->creditPaymentColumnExpr('created_at', 'created_at', 'NULL') . '
                 FROM credit_payments cp
                 INNER JOIN credits c ON c.credit_id = cp.credit_id
                 WHERE c.company_id = :company_id
                   AND cp.credit_id = :credit_id
                 ORDER BY payment_date ASC, cp.payment_id ASC'
            );
            $stmt->execute(['company_id' => $companyId, 'credit_id' => $creditId]);

            return $stmt->fetchAll() ?: [];
        } catch (\Throwable $e) {
            error_log('paymentListByCreditForCompany SchemaCompat error: ' . $e->getMessage());
        }

        // Direct fallback: no SchemaCompat, just SELECT * from credit_payments
        try {
            $stmt = $this->pdo->prepare(
                'SELECT * FROM credit_payments WHERE credit_id = :credit_id ORDER BY payment_id ASC'
            );
            $stmt->execute(['credit_id' => $creditId]);
            return $stmt->fetchAll() ?: [];
        } catch (\Throwable $e2) {
            error_log('paymentListByCreditForCompany fallback error: ' . $e2->getMessage());
            return [];
        }
    }

    public function create(int $companyId, int $customerId, string $customerName, float $amount, string $interestType, float $interestPercent, string $reason, ?string $dueDate): int
    {
        if (!in_array($interestType, ['flat', 'monthly', 'daily'], true)) {
            $interestType = 'flat';
        }

        $interestAmount = round($amount * ($interestPercent / 100), 2);
        $totalAmount = round($amount + $interestAmount, 2);

        $creditNo = 'CR-' . date('Ymd') . '-' . substr((string) time(), -4) . '-' . random_int(100, 999);

        $columns = ['company_id', 'credit_no', 'customer_name', 'amount'];
        $values = [
            'company_id' => $companyId,
            'credit_no' => $creditNo,
            'customer_name' => $customerName,
            'amount' => $amount,
        ];

        $optional = [
            'customer_id' => $customerId > 0 ? $customerId : null,
            'interest_type' => $interestType,
            'interest_percent' => $interestPercent,
            'interest_amount' => $interestAmount,
            'total_amount' => $totalAmount,
            'amount_paid' => 0,
            'due_date' => $dueDate,
            'issue_date' => date('Y-m-d'),
            'status' => 'ACTIVE',
            'reason' => $reason,
        ];

        foreach ($optional as $column => $value) {
            if (SchemaCompat::hasColumn('credits', $column)) {
                $columns[] = $column;
                $values[$column] = $value;
            }
        }

        $sql = 'INSERT INTO credits (' . implode(', ', $columns) . ') VALUES (:' . implode(', :', $columns) . ')';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($values);

        return (int) $this->pdo->lastInsertId();
    }

    public function findByIdForCompany(int $creditId, int $companyId): ?array
    {
        $totalExpr = $this->creditTotalExpression();
        $paidExpr = $this->creditAmountPaidExpression();

        $stmt = $this->pdo->prepare(
            'SELECT credit_id,
                    ' . $this->creditColumnExpr('credit_no', 'credit_no', "''") . ',
                    ' . $this->creditColumnExpr('customer_id', 'customer_id', 'NULL') . ',
                    ' . $this->creditColumnExpr('customer_name', 'customer_name', "''") . ',
                    ' . $this->creditColumnExpr('amount', 'amount', '0') . ',
                    ' . $this->creditColumnExpr('interest_type', 'interest_type', "'flat'") . ',
                    ' . $this->creditColumnExpr('interest_percent', 'interest_percent', '0') . ',
                    ' . $this->creditColumnExpr('interest_amount', 'interest_amount', '0') . ',
                    ' . $totalExpr . ' AS total_amount,
                    ' . $paidExpr . ' AS amount_paid,
                    GREATEST(' . $totalExpr . ' - ' . $paidExpr . ', 0) AS outstanding,
                    ' . $this->creditColumnExpr('due_date', 'due_date', 'NULL') . ',
                    ' . $this->creditColumnExpr('issue_date', 'issue_date', 'NULL') . ',
                    ' . $this->creditColumnExpr('last_payment_date', 'last_payment_date', 'NULL') . ',
                    ' . $this->creditColumnExpr('settlement_date', 'settlement_date', 'NULL') . ',
                    ' . $this->creditColumnExpr('status', 'status', "'ACTIVE'") . ',
                    ' . $this->creditColumnExpr('reason', 'reason', 'NULL') . '
             FROM credits c
             WHERE c.credit_id = :credit_id AND c.company_id = :company_id
             LIMIT 1'
        );
        $stmt->execute(['credit_id' => $creditId, 'company_id' => $companyId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findAgreementByIdForCompany(int $creditId, int $companyId): ?array
    {
        $totalExpr = $this->creditTotalExpression();
        $paidExpr = $this->creditAmountPaidExpression();

        $stmt = $this->pdo->prepare(
            'SELECT c.credit_id,
                    ' . $this->creditColumnExpr('credit_no', 'credit_no', "''") . ',
                    ' . $this->creditColumnExpr('customer_id', 'customer_id', 'NULL') . ',
                    ' . $this->creditColumnExpr('customer_name', 'customer_name', "''") . ',
                    ' . $this->creditColumnExpr('amount', 'amount', '0') . ',
                    ' . $this->creditColumnExpr('interest_type', 'interest_type', "'flat'") . ',
                    ' . $this->creditColumnExpr('interest_percent', 'interest_percent', '0') . ',
                    ' . $this->creditColumnExpr('interest_amount', 'interest_amount', '0') . ',
                    ' . $totalExpr . ' AS total_amount,
                    ' . $paidExpr . ' AS amount_paid,
                    GREATEST(' . $totalExpr . ' - ' . $paidExpr . ', 0) AS outstanding,
                    ' . $this->creditColumnExpr('due_date', 'due_date', 'NULL') . ',
                    ' . $this->creditColumnExpr('issue_date', 'issue_date', 'NULL') . ',
                    ' . $this->creditColumnExpr('last_payment_date', 'last_payment_date', 'NULL') . ',
                    ' . $this->creditColumnExpr('settlement_date', 'settlement_date', 'NULL') . ',
                    ' . $this->creditColumnExpr('status', 'status', "'ACTIVE'") . ',
                    ' . $this->creditColumnExpr('reason', 'reason', 'NULL') . ',
                    ' . $this->customerColumnExpr('tax_number', 'tax_number', 'NULL') . ',
                    ' . $this->customerColumnExpr('id_number', 'id_number', 'NULL') . ',
                    ' . $this->customerColumnExpr('email', 'customer_email', 'NULL') . ',
                    ' . $this->customerColumnExpr('phone', 'customer_phone', 'NULL') . ',
                    ' . $this->customerColumnExpr('address', 'customer_address', 'NULL') . '
             FROM credits c
             LEFT JOIN customers cust
                ON cust.customer_id = c.customer_id AND cust.company_id = c.company_id
             WHERE c.credit_id = :credit_id AND c.company_id = :company_id
             LIMIT 1'
        );
        $stmt->execute(['credit_id' => $creditId, 'company_id' => $companyId]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function recordPayment(int $companyId, int $creditId, float $amount, string $paymentDate, string $paymentMethod, string $reference): bool
    {
        $credit = $this->findByIdForCompany($creditId, $companyId);
        if ($credit === null) {
            return false;
        }

        $outstanding = (float) ($credit['outstanding'] ?? 0);
        if ($amount <= 0 || $outstanding <= 0) {
            return false;
        }

        if ($amount > $outstanding) {
            return false;
        }

        if ((string) ($credit['status'] ?? '') === 'BAD_DEBT') {
            return false;
        }

        $totalExprRaw = $this->creditTotalExpressionRaw();
        $amountPaidExprRaw = $this->creditAmountPaidExpressionRaw();

        $this->pdo->beginTransaction();
        try {
            $columns = ['company_id', 'credit_id', 'amount'];
            $values = [
                'company_id' => $companyId,
                'credit_id' => $creditId,
                'amount' => $amount,
            ];

            $optional = [
                'customer_name' => $credit['customer_name'] ?? '',
                'payment_method' => $paymentMethod,
                'payment_date' => $paymentDate,
                'reference' => $reference,
            ];

            foreach ($optional as $column => $value) {
                if (SchemaCompat::hasColumn('credit_payments', $column)) {
                    $columns[] = $column;
                    $values[$column] = $value;
                }
            }

            $stmt = $this->pdo->prepare(
                'INSERT INTO credit_payments (' . implode(', ', $columns) . ') VALUES (:' . implode(', :', $columns) . ')'
            );
            $stmt->execute($values);

            $updateParts = [];
            if (SchemaCompat::hasColumn('credits', 'amount_paid')) {
                $updateParts[] = 'amount_paid = LEAST(' . $totalExprRaw . ', COALESCE(amount_paid, 0) + :amount_set)';
            }
            if (SchemaCompat::hasColumn('credits', 'last_payment_date')) {
                $updateParts[] = 'last_payment_date = :payment_date_last';
            }
            if (SchemaCompat::hasColumn('credits', 'settlement_date')) {
                $amountPaidExpr = SchemaCompat::hasColumn('credits', 'amount_paid') ? 'COALESCE(amount_paid, 0)' : '0';
                $updateParts[] = 'settlement_date = CASE WHEN LEAST(' . $totalExprRaw . ', ' . $amountPaidExpr . ' + :amount_settlement) >= ' . $totalExprRaw . ' THEN :payment_date_settlement ELSE settlement_date END';
            }
            if (SchemaCompat::hasColumn('credits', 'status')) {
                $amountPaidExpr = SchemaCompat::hasColumn('credits', 'amount_paid') ? 'COALESCE(amount_paid, 0)' : '0';
                $dueDateExpr = SchemaCompat::hasColumn('credits', 'due_date') ? 'due_date' : 'NULL';
                $updateParts[] = 'status = CASE WHEN LEAST(' . $totalExprRaw . ', ' . $amountPaidExpr . ' + :amount_status) >= ' . $totalExprRaw . ' THEN :paid WHEN ' . $dueDateExpr . ' IS NOT NULL AND ' . $dueDateExpr . ' < CURDATE() THEN :overdue ELSE :active END';
            }

            if ($updateParts === []) {
                throw new \RuntimeException('Credit schema is missing updateable payment columns.');
            }

            $update = $this->pdo->prepare(
                'UPDATE credits SET ' . implode(', ', $updateParts) . ' WHERE credit_id = :credit_id AND company_id = :company_id'
            );

            $ok = $update->execute([
                'amount_set' => $amount,
                'amount_settlement' => $amount,
                'amount_status' => $amount,
                'payment_date_last' => $paymentDate,
                'payment_date_settlement' => $paymentDate,
                'paid' => 'PAID',
                'overdue' => 'OVERDUE',
                'active' => 'ACTIVE',
                'credit_id' => $creditId,
                'company_id' => $companyId,
            ]);

            if (!$ok) {
                throw new \RuntimeException('Failed to update credit payment totals.');
            }

            $this->pdo->commit();
            return true;
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            return false;
        }
    }

    public function writeOff(int $companyId, int $creditId, string $reason, string $actor): bool
    {
        if (!SchemaCompat::hasColumn('credits', 'status')) {
            return false;
        }

        $totalExprRaw = $this->creditTotalExpressionRaw();
        $amountPaidExprRaw = $this->creditAmountPaidExpressionRaw();

        $set = ['status = :new_status'];
        if (SchemaCompat::hasColumn('credits', 'written_off_at')) {
            $set[] = 'written_off_at = NOW()';
        }
        if (SchemaCompat::hasColumn('credits', 'written_off_by')) {
            $set[] = 'written_off_by = :written_off_by';
        }
        if (SchemaCompat::hasColumn('credits', 'write_off_reason')) {
            $set[] = 'write_off_reason = :write_off_reason';
        }
        if (SchemaCompat::hasColumn('credits', 'write_off_amount')) {
            $set[] = 'write_off_amount = GREATEST(' . $totalExprRaw . ' - ' . $amountPaidExprRaw . ', 0)';
        }

        $stmt = $this->pdo->prepare(
            'UPDATE credits
             SET ' . implode(', ', $set) . '
             WHERE credit_id = :credit_id
               AND company_id = :company_id
               AND status <> :not_status
               AND ' . $totalExprRaw . ' > ' . $amountPaidExprRaw . '
            '
        );

        return $stmt->execute([
            'new_status'     => 'BAD_DEBT',
            'not_status'     => 'BAD_DEBT',
            'written_off_by' => $actor,
            'write_off_reason' => $reason,
            'credit_id'      => $creditId,
            'company_id'     => $companyId,
        ]);
    }

    public function reopen(int $companyId, int $creditId): bool
    {
        if (!SchemaCompat::hasColumn('credits', 'status')) {
            return false;
        }

        $totalExprRaw = $this->creditTotalExpressionRaw();
        $amountPaidExprRaw = $this->creditAmountPaidExpressionRaw();

        $set = [
            'status = CASE
                    WHEN ' . $amountPaidExprRaw . ' >= ' . $totalExprRaw . ' THEN :paid
                    WHEN ' . (SchemaCompat::hasColumn('credits', 'due_date') ? 'due_date' : 'NULL') . ' IS NOT NULL AND ' . (SchemaCompat::hasColumn('credits', 'due_date') ? 'due_date' : 'NULL') . ' < CURDATE() THEN :overdue
                    ELSE :active
                 END',
        ];
        if (SchemaCompat::hasColumn('credits', 'written_off_at')) {
            $set[] = 'written_off_at = NULL';
        }
        if (SchemaCompat::hasColumn('credits', 'written_off_by')) {
            $set[] = 'written_off_by = NULL';
        }
        if (SchemaCompat::hasColumn('credits', 'write_off_reason')) {
            $set[] = 'write_off_reason = NULL';
        }
        if (SchemaCompat::hasColumn('credits', 'write_off_amount')) {
            $set[] = 'write_off_amount = 0';
        }

        $stmt = $this->pdo->prepare(
            'UPDATE credits
             SET ' . implode(', ', $set) . '
             WHERE credit_id = :credit_id
               AND company_id = :company_id
               AND status = :bad_debt'
        );

        return $stmt->execute([
            'paid' => 'PAID',
            'overdue' => 'OVERDUE',
            'active' => 'ACTIVE',
            'bad_debt' => 'BAD_DEBT',
            'credit_id' => $creditId,
            'company_id' => $companyId,
        ]);
    }

    private function creditColumnExpr(string $column, string $alias, string $defaultSql): string
    {
        return SchemaCompat::hasColumn('credits', $column)
            ? 'c.' . $column . ' AS ' . $alias
            : $defaultSql . ' AS ' . $alias;
    }

    private function customerColumnExpr(string $column, string $alias, string $defaultSql): string
    {
        return SchemaCompat::hasColumn('customers', $column)
            ? 'cust.' . $column . ' AS ' . $alias
            : $defaultSql . ' AS ' . $alias;
    }

    private function creditPaymentColumnExpr(string $column, string $alias, string $defaultSql): string
    {
        return SchemaCompat::hasColumn('credit_payments', $column)
            ? $column . ' AS ' . $alias
            : $defaultSql . ' AS ' . $alias;
    }

    private function creditTotalExpression(): string
    {
        if (SchemaCompat::hasColumn('credits', 'total_amount')) {
            return 'COALESCE(c.total_amount, COALESCE(c.amount, 0))';
        }

        return SchemaCompat::hasColumn('credits', 'amount') ? 'COALESCE(c.amount, 0)' : '0';
    }

    private function creditAmountPaidExpression(): string
    {
        return SchemaCompat::hasColumn('credits', 'amount_paid') ? 'COALESCE(c.amount_paid, 0)' : '0';
    }

    private function creditTotalExpressionRaw(): string
    {
        if (SchemaCompat::hasColumn('credits', 'total_amount')) {
            return 'COALESCE(total_amount, COALESCE(amount, 0))';
        }

        return SchemaCompat::hasColumn('credits', 'amount') ? 'COALESCE(amount, 0)' : '0';
    }

    private function creditAmountPaidExpressionRaw(): string
    {
        return SchemaCompat::hasColumn('credits', 'amount_paid') ? 'COALESCE(amount_paid, 0)' : '0';
    }
}
