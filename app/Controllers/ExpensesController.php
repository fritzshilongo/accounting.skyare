<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\RequestContext;
use App\Core\View;
use App\Models\Expense;

final class ExpensesController
{
    private const CATEGORY_OPTIONS = [
        'Rent',
        'Salaries',
        'Utilities',
        'Transport',
        'Office Supplies',
        'Marketing',
        'Maintenance',
        'Software',
        'Professional Fees',
        'Other',
    ];

    public static function index(Database $db, RequestContext $context): void
    {
        $companyId = self::resolveCompanyId($context);
        if ($companyId === null) {
            http_response_code(403);
            echo 'Tenant context is invalid.';
            return;
        }

        $search = trim((string) ($_GET['q'] ?? ''));
        $categoryFilter = trim((string) ($_GET['category'] ?? ''));
        $fromDate = trim((string) ($_GET['from'] ?? ''));
        $toDate = trim((string) ($_GET['to'] ?? ''));

        $expenseModel = new Expense($db->pdo());
        $allRows = $expenseModel->listByCompany($companyId, 500);
        $rows = $allRows;

        if ($search !== '') {
            $needle = mb_strtolower($search);
            $rows = array_values(array_filter($rows, static function (array $row) use ($needle): bool {
                $haystack = mb_strtolower(
                    (string) ($row['expense_id'] ?? '') . ' ' .
                    (string) ($row['category'] ?? '') . ' ' .
                    (string) ($row['description'] ?? '') . ' ' .
                    (string) ($row['amount'] ?? '') . ' ' .
                    (string) ($row['expense_date'] ?? '')
                );
                return str_contains($haystack, $needle);
            }));
        }

        if ($categoryFilter !== '') {
            $rows = array_values(array_filter($rows, static function (array $row) use ($categoryFilter): bool {
                return mb_strtolower((string) ($row['category'] ?? '')) === mb_strtolower($categoryFilter);
            }));
        }

        if ($fromDate !== '' || $toDate !== '') {
            $rows = array_values(array_filter($rows, static function (array $row) use ($fromDate, $toDate): bool {
                $date = (string) ($row['expense_date'] ?? '');
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

        $summary = [
            'count' => count($rows),
            'total_amount' => array_reduce(
                $rows,
                static fn(float $carry, array $row): float => $carry + (float) ($row['amount'] ?? 0),
                0.0
            ),
        ];

        View::render('expenses/index', [
            'company' => $context->company(),
            'rows' => $rows,
            'token' => \App\Middleware\CsrfMiddleware::token(),
            'errors' => [],
            'categories' => self::CATEGORY_OPTIONS,
            'search' => $search,
            'category_filter' => $categoryFilter,
            'from_date' => $fromDate,
            'to_date' => $toDate,
            'summary' => $summary,
            'old' => [
                'category' => '',
                'description' => '',
                'amount' => '',
                'expense_date' => date('Y-m-d'),
            ],
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

        $category = trim((string) ($_POST['category'] ?? ''));
        $description = trim((string) ($_POST['description'] ?? ''));
        $amountRaw = trim((string) ($_POST['amount'] ?? ''));
        $expenseDate = trim((string) ($_POST['expense_date'] ?? ''));

        $errors = [];
        if ($category === '') {
            $errors[] = 'Category is required.';
        } elseif (!in_array($category, self::CATEGORY_OPTIONS, true)) {
            $errors[] = 'Please select a valid category from the dropdown.';
        }

        if ($amountRaw === '' || !is_numeric($amountRaw) || (float) $amountRaw <= 0) {
            $errors[] = 'Amount must be a positive number.';
        }

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $expenseDate)) {
            $errors[] = 'Expense date must be in YYYY-MM-DD format.';
        }

        $expenseModel = new Expense($db->pdo());

        if ($errors !== []) {
            http_response_code(422);
            $rows = $expenseModel->listByCompany($companyId, 500);
            View::render('expenses/index', [
                'company' => $context->company(),
                'rows' => $rows,
                'token' => \App\Middleware\CsrfMiddleware::token(),
                'errors' => $errors,
                'categories' => self::CATEGORY_OPTIONS,
                'search' => '',
                'category_filter' => '',
                'from_date' => '',
                'to_date' => '',
                'summary' => [
                    'count' => count($rows),
                    'total_amount' => array_reduce(
                        $rows,
                        static fn(float $carry, array $row): float => $carry + (float) ($row['amount'] ?? 0),
                        0.0
                    ),
                ],
                'old' => [
                    'category' => $category,
                    'description' => $description,
                    'amount' => $amountRaw,
                    'expense_date' => $expenseDate,
                ],
            ]);
            return;
        }

        $expenseModel->createForCompany(
            $companyId,
            $category,
            $description === '' ? null : $description,
            (float) $amountRaw,
            $expenseDate
        );

        header('Location: /expenses');
    }

    private static function resolveCompanyId(RequestContext $context): ?int
    {
        $companyId = (int) ($context->company()['company_id'] ?? 0);
        $sessionCompanyId = (int) ($_SESSION['user']['company_id'] ?? 0);

        if ($companyId <= 0 || $sessionCompanyId <= 0 || $companyId !== $sessionCompanyId) {
            return null;
        }

        return $companyId;
    }
}
