<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\AuditLogger;
use App\Core\Database;
use App\Core\RequestContext;
use App\Core\View;
use App\Models\Customer;

final class CustomersController
{
    public static function index(Database $db, RequestContext $context): void
    {
        $companyId = self::companyId($context);
        if ($companyId === null) {
            self::deny();
            return;
        }

        $search = trim((string) ($_GET['q'] ?? ''));
        $statusFilter = trim((string) ($_GET['status'] ?? ''));
        $fromDate = trim((string) ($_GET['from'] ?? ''));
        $toDate = trim((string) ($_GET['to'] ?? ''));
        $model = new Customer($db->pdo());
        $rows = $model->listByCompany($companyId);
        if ($search !== '') {
            $needle = mb_strtolower($search);
            $rows = array_values(array_filter($rows, static function (array $row) use ($needle): bool {
                $haystack = mb_strtolower(
                    (string) ($row['customer_name'] ?? '') . ' ' .
                    (string) ($row['company_name'] ?? '') . ' ' .
                    (string) ($row['email'] ?? '') . ' ' .
                    (string) ($row['phone'] ?? '') . ' ' .
                    (string) ($row['id_number'] ?? '')
                );
                return str_contains($haystack, $needle);
            }));
        }

        if ($statusFilter !== '' && in_array($statusFilter, ['active', 'inactive'], true)) {
            $rows = array_values(array_filter($rows, static function (array $row) use ($statusFilter): bool {
                $isActive = ((int) ($row['is_active'] ?? 0) === 1);
                return $statusFilter === 'active' ? $isActive : !$isActive;
            }));
        }

        if ($fromDate !== '' || $toDate !== '') {
            $rows = array_values(array_filter($rows, static function (array $row) use ($fromDate, $toDate): bool {
                $created = substr((string) ($row['created_at'] ?? ''), 0, 10);
                if ($created === '') {
                    return false;
                }
                if ($fromDate !== '' && $created < $fromDate) {
                    return false;
                }
                if ($toDate !== '' && $created > $toDate) {
                    return false;
                }
                return true;
            }));
        }

        View::render('customers/index', [
            'company' => $context->company(),
            'rows' => $rows,
            'search' => $search,
            'status_filter' => $statusFilter,
            'from_date' => $fromDate,
            'to_date' => $toDate,
            'token' => \App\Middleware\CsrfMiddleware::token(),
            'errors' => [],
            'old' => ['customer_name' => '', 'customer_type' => 'individual', 'company_name' => '',
                      'registration_number' => '', 'tax_number' => '', 'id_number' => '',
                      'email' => '', 'phone' => '', 'address' => '', 'notes' => '',
                      'credit_limit' => '0.00', 'is_active' => '1'],
        ]);
    }

    public static function store(Database $db, RequestContext $context): void
    {
        $companyId = self::companyId($context);
        if ($companyId === null) {
            self::deny();
            return;
        }

        $fields = self::input();
        [$name, $customerType, $companyName, $registrationNumber, $taxNumber, $idNumber,
         $email, $phone, $address, $notes, $creditLimit, $isActive, $errors] = $fields;
        $model = new Customer($db->pdo());

        if ($errors !== []) {
            http_response_code(422);
            View::render('customers/index', [
                'company' => $context->company(),
                'rows' => $model->listByCompany($companyId),
                'token' => \App\Middleware\CsrfMiddleware::token(),
                'errors' => $errors,
                'old' => self::postToOld(),
            ]);
            return;
        }

        $id = $model->createForCompany($companyId, $name, $customerType, $companyName,
            $registrationNumber, $taxNumber, $idNumber, $email, $phone, $address, $notes, $creditLimit);
        AuditLogger::log($db, $context, 'customer.create', 'customer', (string) $id, 'Created customer: ' . $name);
        header('Location: /customers');
    }

    public static function edit(Database $db, RequestContext $context): void
    {
        $companyId = self::companyId($context);
        if ($companyId === null) {
            self::deny();
            return;
        }

        $id = (int) ($_GET['customer_id'] ?? 0);
        $model = new Customer($db->pdo());
        $row = $model->findByIdForCompany($id, $companyId);
        if ($row === null) {
            http_response_code(404);
            echo 'Customer not found.';
            return;
        }

        View::render('customers/edit', ['company' => $context->company(), 'row' => $row, 'token' => \App\Middleware\CsrfMiddleware::token(), 'errors' => []]);
    }

    public static function update(Database $db, RequestContext $context): void
    {
        $companyId = self::companyId($context);
        if ($companyId === null) {
            self::deny();
            return;
        }

        $id = (int) ($_POST['customer_id'] ?? 0);
        $fields = self::input();
        [$name, $customerType, $companyName, $registrationNumber, $taxNumber, $idNumber,
         $email, $phone, $address, $notes, $creditLimit, $isActive, $errors] = $fields;
        $model = new Customer($db->pdo());

        if ($errors !== []) {
            http_response_code(422);
            $old = self::postToOld();
            $old['customer_id'] = $id;
            View::render('customers/edit', [
                'company' => $context->company(),
                'row' => $old,
                'token' => \App\Middleware\CsrfMiddleware::token(),
                'errors' => $errors,
            ]);
            return;
        }

        $model->updateForCompany($id, $companyId, $name, $customerType, $companyName,
            $registrationNumber, $taxNumber, $idNumber, $email, $phone, $address, $notes, $creditLimit, $isActive);
        AuditLogger::log($db, $context, 'customer.update', 'customer', (string) $id, 'Updated customer: ' . $name);
        header('Location: /customers');
    }

    public static function delete(Database $db, RequestContext $context): void
    {
        $companyId = self::companyId($context);
        if ($companyId === null) {
            self::deny();
            return;
        }

        $id = (int) ($_POST['customer_id'] ?? 0);
        $model = new Customer($db->pdo());
        $model->deleteForCompany($id, $companyId);
        AuditLogger::log($db, $context, 'customer.delete', 'customer', (string) $id, 'Deleted customer');
        header('Location: /customers');
    }

    private static function input(): array
    {
        $name             = trim((string) ($_POST['customer_name'] ?? ''));
        $customerType     = trim((string) ($_POST['customer_type'] ?? 'individual'));
        $companyName      = trim((string) ($_POST['company_name'] ?? ''));
        $registrationNum  = trim((string) ($_POST['registration_number'] ?? ''));
        $taxNumber        = trim((string) ($_POST['tax_number'] ?? ''));
        $idNumber         = trim((string) ($_POST['id_number'] ?? ''));
        $emailRaw         = trim((string) ($_POST['email'] ?? ''));
        $phoneRaw         = trim((string) ($_POST['phone'] ?? ''));
        $address          = trim((string) ($_POST['address'] ?? ''));
        $notes            = trim((string) ($_POST['notes'] ?? ''));
        $creditRaw        = trim((string) ($_POST['credit_limit'] ?? '0'));
        $isActive         = (string) ($_POST['is_active'] ?? '1') === '1';

        if (!in_array($customerType, ['individual', 'company'], true)) {
            $customerType = 'individual';
        }

        $errors = [];
        if ($name === '') {
            $errors[] = 'Customer name is required.';
        }
        if ($emailRaw !== '' && !filter_var($emailRaw, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid customer email.';
        }
        if (!is_numeric($creditRaw) || (float) $creditRaw < 0) {
            $errors[] = 'Credit limit must be 0 or greater.';
        }

        return [
            $name,
            $customerType,
            $companyName !== '' ? $companyName : null,
            $registrationNum !== '' ? $registrationNum : null,
            $taxNumber !== '' ? $taxNumber : null,
            $idNumber !== '' ? $idNumber : null,
            $emailRaw !== '' ? strtolower($emailRaw) : null,
            $phoneRaw !== '' ? $phoneRaw : null,
            $address !== '' ? $address : null,
            $notes !== '' ? $notes : null,
            (float) $creditRaw,
            $isActive,
            $errors,
        ];
    }

    private static function postToOld(): array
    {
        return [
            'customer_name'       => trim((string) ($_POST['customer_name'] ?? '')),
            'customer_type'       => trim((string) ($_POST['customer_type'] ?? 'individual')),
            'company_name'        => trim((string) ($_POST['company_name'] ?? '')),
            'registration_number' => trim((string) ($_POST['registration_number'] ?? '')),
            'tax_number'          => trim((string) ($_POST['tax_number'] ?? '')),
            'id_number'           => trim((string) ($_POST['id_number'] ?? '')),
            'email'               => trim((string) ($_POST['email'] ?? '')),
            'phone'               => trim((string) ($_POST['phone'] ?? '')),
            'address'             => trim((string) ($_POST['address'] ?? '')),
            'notes'               => trim((string) ($_POST['notes'] ?? '')),
            'credit_limit'        => trim((string) ($_POST['credit_limit'] ?? '0.00')),
            'is_active'           => (string) ($_POST['is_active'] ?? '1'),
        ];
    }

    private static function companyId(RequestContext $context): ?int
    {
        $cid = (int) ($context->company()['company_id'] ?? 0);
        $sid = (int) ($_SESSION['user']['company_id'] ?? 0);
        return ($cid > 0 && $sid > 0 && $cid === $sid) ? $cid : null;
    }

    private static function deny(): void
    {
        http_response_code(403);
        echo 'Tenant context is invalid.';
    }
}
