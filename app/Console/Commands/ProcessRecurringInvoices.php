<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Core\Database;
use App\Support\SchemaCompat;

class ProcessRecurringInvoices extends Command
{
    protected $signature = 'invoices:process-recurring';
    protected $description = 'Generate invoices from active recurring invoice templates that are due today';

    public function handle(Database $db)
    {
        $pdo = $db->pdo();
        $today = date('Y-m-d');

        $clientNameCol = SchemaCompat::firstExisting('clients', ['name', 'client_name'], 'name') ?? 'name';

        try {
            $stmt = $pdo->prepare(
                "SELECT r.*, c." . $clientNameCol . " AS client_name_live
                 FROM recurring_invoices r
                 LEFT JOIN clients c ON c.client_id = r.client_id
                 WHERE r.status = 'active'
                   AND r.next_run_date <= :today
                   AND (r.end_date IS NULL OR r.end_date >= :today2)"
            );
            $stmt->execute(['today' => $today, 'today2' => $today]);
            $dueRecurring = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable $e) {
            $this->error('Recurring scheduler failed to load due templates: ' . $e->getMessage());
            return 1;
        }

        $generated = 0;

        foreach ($dueRecurring as $rec) {
            $recurringId = (int) $rec['recurring_id'];
            $companyId = (int) $rec['company_id'];

            // Check max occurrences
            if (!empty($rec['max_occurrences'])) {
                $countStmt = $pdo->prepare(
                    "SELECT COUNT(*) FROM invoices WHERE company_id = :cid AND recurring_id = :rid"
                );
                $countStmt->execute(['cid' => $companyId, 'rid' => $recurringId]);
                $existingCount = (int) $countStmt->fetchColumn();
                if ($existingCount >= (int) $rec['max_occurrences']) {
                    // Pause the recurring invoice
                    $pdo->prepare("UPDATE recurring_invoices SET status = 'completed', updated_at = NOW() WHERE recurring_id = :id")
                        ->execute(['id' => $recurringId]);
                    continue;
                }
            }

            // Generate invoice number
            $prefixStmt = $pdo->prepare(
                "SELECT preference_value FROM company_preferences WHERE company_id = :cid AND preference_key = 'invoice_prefix' LIMIT 1"
            );
            $prefixStmt->execute(['cid' => $companyId]);
            $prefix = $prefixStmt->fetchColumn() ?: 'INV-';

            $nextStmt = $pdo->prepare(
                "SELECT preference_value FROM company_preferences WHERE company_id = :cid AND preference_key = 'next_invoice_number' LIMIT 1"
            );
            $nextStmt->execute(['cid' => $companyId]);
            $nextNum = (int) ($nextStmt->fetchColumn() ?: 1001);

            $invoiceNo = $prefix . str_pad((string) $nextNum, 4, '0', STR_PAD_LEFT);

            // Get default payment terms
            $termsStmt = $pdo->prepare(
                "SELECT preference_value FROM company_preferences WHERE company_id = :cid AND preference_key = 'default_payment_terms' LIMIT 1"
            );
            $termsStmt->execute(['cid' => $companyId]);
            $terms = (int) ($termsStmt->fetchColumn() ?: 7);

            $issueDate = $today;
            $dueDate = date('Y-m-d', strtotime("+{$terms} days"));

            try {
                $pdo->beginTransaction();

                // Create invoice
                $now = date('Y-m-d H:i:s');
                $invoiceColumns = [];
                $invoiceParams = [];

                $pushInvoice = static function (string $column, mixed $value) use (&$invoiceColumns, &$invoiceParams): void {
                    if (!SchemaCompat::hasColumn('invoices', $column)) {
                        return;
                    }
                    $invoiceColumns[] = $column;
                    $invoiceParams[$column] = $value;
                };

                $pushInvoice('company_id', $companyId);
                $pushInvoice('client_id', (int) $rec['client_id']);
                $pushInvoice('client_name', $rec['client_name_live'] ?? $rec['client_name'] ?? 'Unknown');
                $pushInvoice('invoice_no', $invoiceNo);
                $pushInvoice('amount', (float) ($rec['total'] ?? $rec['amount'] ?? 0));
                $pushInvoice('tax_rate', (float) ($rec['tax_rate'] ?? 0));
                $pushInvoice('tax_amount', (float) ($rec['tax_amount'] ?? 0));
                $pushInvoice('total', (float) ($rec['total'] ?? $rec['amount'] ?? 0));
                $pushInvoice('status', 'draft');
                $pushInvoice('issue_date', $issueDate);
                $pushInvoice('due_date', $dueDate);
                $pushInvoice('recurring_id', $recurringId);
                $pushInvoice('created_at', $now);
                $pushInvoice('updated_at', $now);

                if ($invoiceColumns === []) {
                    throw new \RuntimeException('Invoices table is missing required writable columns.');
                }

                $invoicePlaceholders = array_map(static fn (string $column): string => ':' . $column, $invoiceColumns);
                $insertInv = $pdo->prepare(
                    'INSERT INTO invoices (' . implode(', ', $invoiceColumns) . ') VALUES (' . implode(', ', $invoicePlaceholders) . ')'
                );
                $insertInv->execute($invoiceParams);
                $invoiceId = (int) $pdo->lastInsertId();

                // Copy line items
                $itemsStmt = $pdo->prepare('SELECT * FROM recurring_invoice_items WHERE recurring_id = :rid');
                $itemsStmt->execute(['rid' => $recurringId]);
                $items = $itemsStmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];

                foreach ($items as $item) {
                    $pdo->prepare(
                        "INSERT INTO invoice_items (invoice_id, description, quantity, unit_price, line_total, created_at, updated_at)
                         VALUES (:iid, :desc, :qty, :price, :total, NOW(), NOW())"
                    )->execute([
                        'iid' => $invoiceId,
                        'desc' => $item['description'],
                        'qty' => (float) $item['quantity'],
                        'price' => (float) $item['unit_price'],
                        'total' => (float) $item['line_total'],
                    ]);
                }

                // Advance next_run_date
                $nextRunDate = $this->advanceDate($rec['next_run_date'], $rec['frequency']);
                $pdo->prepare(
                    "UPDATE recurring_invoices SET next_run_date = :nrd, updated_at = NOW() WHERE recurring_id = :id"
                )->execute(['nrd' => $nextRunDate, 'id' => $recurringId]);

                // Increment invoice number
                $pdo->prepare(
                    "INSERT INTO company_preferences (company_id, preference_key, preference_value, created_at, updated_at)
                     VALUES (:cid, 'next_invoice_number', :val, NOW(), NOW())
                     ON DUPLICATE KEY UPDATE preference_value = VALUES(preference_value), updated_at = NOW()"
                )->execute(['cid' => $companyId, 'val' => (string) ($nextNum + 1)]);

                $pdo->commit();
                $generated++;
                $this->info("Generated {$invoiceNo} for company #{$companyId}");
            } catch (\Throwable $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                $this->error("Failed recurring #{$recurringId}: " . $e->getMessage());
            }
        }

        $this->info("Done. Generated {$generated} invoice(s) from " . count($dueRecurring) . " due recurring template(s).");
        return 0;
    }

    private function advanceDate(string $date, string $frequency): string
    {
        $map = [
            'weekly' => '+1 week',
            'biweekly' => '+2 weeks',
            'monthly' => '+1 month',
            'quarterly' => '+3 months',
            'yearly' => '+1 year',
        ];

        $modifier = $map[$frequency] ?? '+1 month';
        return date('Y-m-d', strtotime($modifier, strtotime($date)));
    }
}
