<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\RequestContext;
use App\Core\View;
use App\Models\JournalEntry;

final class JournalController
{
    /** Predefined chart-of-accounts suggestions shown via <datalist>. */
    private const ACCOUNT_OPTIONS = [
        // Revenue
        'Sales Revenue',
        'Service Revenue',
        'Interest Income',
        'Other Revenue',
        // Assets
        'Cash and Bank',
        'Accounts Receivable',
        'Inventory',
        'Prepaid Expenses',
        'Fixed Assets',
        // Liabilities
        'Accounts Payable',
        'Loans Payable',
        'VAT Payable',
        'Tax Payable',
        'Accrued Liabilities',
        // Expenses
        'Rent',
        'Salaries',
        'Utilities',
        'Transport',
        'Office Supplies',
        'Marketing',
        'Maintenance',
        'Software',
        'Professional Fees',
        'Interest Expense',
        'Depreciation',
        'Bad Debt Expense',
        'Other Expense',
        // Equity
        "Owner's Equity",
        'Retained Earnings',
        'Capital Contribution',
        'Drawings',
    ];

    public static function index(Database $db, RequestContext $context): void
    {
        $companyId = self::resolveCompanyId($context);
        if ($companyId === null) {
            http_response_code(403);
            echo 'Tenant context is invalid.';
            return;
        }

        $search        = trim((string) ($_GET['q'] ?? ''));
        $accountFilter = trim((string) ($_GET['account'] ?? ''));
        $fromDate      = trim((string) ($_GET['from'] ?? ''));
        $toDate        = trim((string) ($_GET['to'] ?? ''));

        $model   = new JournalEntry($db->pdo());
        $allRows = $model->listByCompany($companyId, null, null, 500);
        $rows    = $allRows;

        if ($search !== '') {
            $needle = mb_strtolower($search);
            $rows   = array_values(array_filter($rows, static function (array $row) use ($needle): bool {
                $haystack = mb_strtolower(
                    (string) ($row['entry_id']    ?? '') . ' ' .
                    (string) ($row['account']     ?? '') . ' ' .
                    (string) ($row['reference']   ?? '') . ' ' .
                    (string) ($row['description'] ?? '') . ' ' .
                    (string) ($row['entry_date']  ?? '')
                );
                return str_contains($haystack, $needle);
            }));
        }

        if ($accountFilter !== '') {
            $rows = array_values(array_filter($rows, static function (array $row) use ($accountFilter): bool {
                return mb_strtolower((string) ($row['account'] ?? '')) === mb_strtolower($accountFilter);
            }));
        }

        if ($fromDate !== '' || $toDate !== '') {
            $rows = array_values(array_filter($rows, static function (array $row) use ($fromDate, $toDate): bool {
                $date = (string) ($row['entry_date'] ?? '');
                if ($date === '') {
                    return false;
                }
                if ($fromDate !== '' && $date < $fromDate) {
                    return false;
                }
                if ($toDate !== '' && $date > $toDate) {
                    return false;
                }
                return true;
            }));
        }

        $totalDebit  = (float) array_sum(array_column($rows, 'debit'));
        $totalCredit = (float) array_sum(array_column($rows, 'credit'));
        $summary     = [
            'count'        => count($rows),
            'total_debit'  => $totalDebit,
            'total_credit' => $totalCredit,
            'net'          => $totalCredit - $totalDebit,
        ];

        View::render('journal/index', [
            'company'        => $context->company(),
            'rows'           => $rows,
            'accounts'       => self::ACCOUNT_OPTIONS,
            'search'         => $search,
            'account_filter' => $accountFilter,
            'from_date'      => $fromDate,
            'to_date'        => $toDate,
            'summary'        => $summary,
            'token'          => \App\Middleware\CsrfMiddleware::token(),
            'errors'         => [],
            'old'            => [],
        ]);
    }

    public static function store(Database $db, RequestContext $context): void
    {
        $companyId = self::resolveCompanyId($context);
        if ($companyId === null) {
            http_response_code(403);
            echo 'Tenant context is invalid.';
            return;
        }

        [$entryDate, $account, $reference, $description, $debit, $credit, $errors] = self::input();
        $old = [
            'entry_date'  => $_POST['entry_date']  ?? '',
            'account'     => $_POST['account']     ?? '',
            'reference'   => $_POST['reference']   ?? '',
            'description' => $_POST['description'] ?? '',
            'debit'       => $_POST['debit']       ?? '',
            'credit'      => $_POST['credit']      ?? '',
        ];

        if ($errors !== []) {
            $model       = new JournalEntry($db->pdo());
            $allRows     = $model->listByCompany($companyId, null, null, 500);
            $totalDebit  = (float) array_sum(array_column($allRows, 'debit'));
            $totalCredit = (float) array_sum(array_column($allRows, 'credit'));
            View::render('journal/index', [
                'company'        => $context->company(),
                'rows'           => $allRows,
                'accounts'       => self::ACCOUNT_OPTIONS,
                'search'         => '',
                'account_filter' => '',
                'from_date'      => '',
                'to_date'        => '',
                'summary'        => [
                    'count'        => count($allRows),
                    'total_debit'  => $totalDebit,
                    'total_credit' => $totalCredit,
                    'net'          => $totalCredit - $totalDebit,
                ],
                'token'  => \App\Middleware\CsrfMiddleware::token(),
                'errors' => $errors,
                'old'    => $old,
            ]);
            return;
        }

        $userId = (int) ($_SESSION['user']['user_id'] ?? 0);
        (new JournalEntry($db->pdo()))->createForCompany(
            $companyId,
            $entryDate,
            $account,
            $reference   !== '' ? $reference   : null,
            $description !== '' ? $description : null,
            $debit,
            $credit,
            $userId > 0 ? $userId : null
        );

        header('Location: /journal-entries?saved=1');
        exit;
    }

    public static function delete(Database $db, RequestContext $context): void
    {
        $companyId = self::resolveCompanyId($context);
        if ($companyId === null) {
            http_response_code(403);
            echo 'Tenant context is invalid.';
            return;
        }

        $entryId = (int) ($_POST['entry_id'] ?? 0);
        if ($entryId > 0) {
            (new JournalEntry($db->pdo()))->deleteForCompany($entryId, $companyId);
        }

        header('Location: /journal-entries?deleted=1');
        exit;
    }

    /** Parse and validate POST input. Returns [date, account, ref, desc, debit, credit, errors]. */
    private static function input(): array
    {
        $errors      = [];
        $entryDate   = trim((string) ($_POST['entry_date']  ?? ''));
        $account     = trim((string) ($_POST['account']     ?? ''));
        $reference   = trim((string) ($_POST['reference']   ?? ''));
        $description = trim((string) ($_POST['description'] ?? ''));
        $debitRaw    = trim((string) ($_POST['debit']       ?? '0'));
        $creditRaw   = trim((string) ($_POST['credit']      ?? '0'));

        if ($entryDate === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $entryDate)) {
            $errors['entry_date'] = 'A valid date (YYYY-MM-DD) is required.';
        }

        if ($account === '') {
            $errors['account'] = 'Account name is required.';
        } elseif (!in_array($account, self::ACCOUNT_OPTIONS, true)) {
            $errors['account'] = 'Please select a valid account from the dropdown.';
        } elseif (mb_strlen($account) > 100) {
            $errors['account'] = 'Account name must be 100 characters or fewer.';
        }

        if (mb_strlen($reference) > 100) {
            $errors['reference'] = 'Reference must be 100 characters or fewer.';
        }

        $debit  = (float) $debitRaw;
        $credit = (float) $creditRaw;

        if ($debit < 0.0) {
            $errors['debit'] = 'Debit amount cannot be negative.';
        }
        if ($credit < 0.0) {
            $errors['credit'] = 'Credit amount cannot be negative.';
        }
        if (!isset($errors['debit']) && !isset($errors['credit'])) {
            if ($debit === 0.0 && $credit === 0.0) {
                $errors['amount'] = 'At least one of Debit or Credit must be greater than zero.';
            } elseif ($debit > 0.0 && $credit > 0.0) {
                $errors['amount'] = 'Enter either a Debit or a Credit amount — not both on the same line.';
            }
        }

        return [$entryDate, $account, $reference, $description, $debit, $credit, $errors];
    }

    private static function resolveCompanyId(RequestContext $context): ?int
    {
        $id = (int) ($context->company()['company_id'] ?? 0);
        return $id > 0 ? $id : null;
    }
}
