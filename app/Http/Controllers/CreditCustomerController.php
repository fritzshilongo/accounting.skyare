<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Core\RequestContext;
use App\Core\Database;
use App\Models\Customer;
use App\Models\Credit;

class CreditCustomerController extends Controller
{
    public function index(Request $request, RequestContext $context, Database $db)
    {
        $company = $context->company();
        if (!$company) {
            return response('Company not found', 404);
        }

        $companyId = (int) $company['company_id'];
        $search = trim((string) $request->query('search'));
        $status = (string) $request->query('status', '');

        $customerModel = new Customer($db->pdo());
        $creditModel = new Credit($db->pdo());
        $customers = $customerModel->listByCompany($companyId, 1000);
        $credits = $creditModel->listByCompany($companyId);

        $byCustomer = [];
        foreach ($credits as $credit) {
            $cid = (int) ($credit['customer_id'] ?? 0);
            if ($cid <= 0) {
                continue;
            }

            if (!isset($byCustomer[$cid])) {
                $byCustomer[$cid] = ['credit_count' => 0, 'total_outstanding' => 0.0];
            }

            $byCustomer[$cid]['credit_count']++;
            if ((string) ($credit['status'] ?? '') === 'ACTIVE') {
                $byCustomer[$cid]['total_outstanding'] += (float) ($credit['outstanding'] ?? 0);
            }
        }

        $customers = array_values(array_map(function (array $c) use ($byCustomer): array {
            $cid = (int) ($c['customer_id'] ?? 0);
            $c['credit_count'] = (int) ($byCustomer[$cid]['credit_count'] ?? 0);
            $c['total_outstanding'] = (float) ($byCustomer[$cid]['total_outstanding'] ?? 0);
            return $c;
        }, $customers));

        if ($search !== '') {
            $needle = strtolower($search);
            $customers = array_values(array_filter($customers, static function (array $c) use ($needle): bool {
                $hay = strtolower(
                    (string) ($c['customer_name'] ?? '') . ' ' .
                    (string) ($c['email'] ?? '') . ' ' .
                    (string) ($c['phone'] ?? '')
                );
                return str_contains($hay, $needle);
            }));
        }

        if (in_array($status, ['active', 'inactive'], true)) {
            $customers = array_values(array_filter($customers, static fn (array $c): bool => (string) ($c['status'] ?? 'active') === $status));
        }

        usort($customers, static fn (array $a, array $b): int => strcmp((string) ($a['customer_name'] ?? ''), (string) ($b['customer_name'] ?? '')));

        // Portfolio summary stats
        $stats = ['total_customers' => 0, 'active_facilities' => 0, 'total_outstanding' => 0, 'total_collected' => 0];
        try {
            $stats['total_customers'] = count($customers);
            foreach ($credits as $credit) {
                if ((string) ($credit['status'] ?? '') === 'ACTIVE') {
                    $stats['active_facilities']++;
                    $stats['total_outstanding'] += (float) ($credit['outstanding'] ?? 0);
                }
                $stats['total_collected'] += (float) ($credit['amount_paid'] ?? 0);
            }
        } catch (\Throwable $e) {
            error_log('CreditCustomerController stats error: ' . $e->getMessage());
        }

        return view('credit-customers.index', [
            'company' => $company,
            'customers' => $customers,
            'stats' => $stats,
            'search' => $search,
            'selectedStatus' => $status,
        ]);
    }

    public function create(RequestContext $context)
    {
        $company = $context->company();
        if (!$company) {
            return response('Company not found', 404);
        }

        return view('credit-customers.create', ['company' => $company]);
    }

    public function store(Request $request, RequestContext $context, Database $db)
    {
        $company = $context->company();
        if (!$company) {
            return response('Company not found', 404);
        }

        $validated = $request->validate([
            'customer_name' => 'required|string|max:191',
            'email' => 'nullable|email|max:191',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:100',
            'province' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:100',
            'tax_number' => 'nullable|string|max:100',
            'id_number' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:2000',
        ]);

        $customerModel = new Customer($db->pdo());
        try {
            $createdId = $customerModel->createForCompany(
                (int) $company['company_id'],
                $validated['customer_name'],
                $validated['tax_number'] ?? null,
                $validated['email'] ?? null,
                $validated['phone'] ?? null,
                $validated['address'] ?? null,
                $validated['notes'] ?? null,
                $validated['city'] ?? null,
                $validated['province'] ?? null,
                $validated['postal_code'] ?? null,
                $validated['country'] ?? null,
                $validated['id_number'] ?? null
            );
        } catch (\Throwable $e) {
            error_log('CreditCustomerController store error: ' . $e->getMessage());
            $createdId = 0;
        }

        if ($createdId <= 0) {
            return back()->withErrors(['customer_name' => 'Could not create credit customer with current database schema.'])->withInput();
        }

        return redirect('/credit-customers')->with('success', 'Customer created successfully.');
    }

    public function show($id, RequestContext $context, Database $db)
    {
        $company = $context->company();
        if (!$company) {
            return response('Company not found', 404);
        }

        $companyId = (int) $company['company_id'];
        $customerModel = new Customer($db->pdo());
        $customer = $customerModel->findByIdForCompany((int) $id, $companyId);

        if (!$customer) {
            return redirect('/credit-customers')->withErrors(['customer' => 'Customer not found.']);
        }

        $creditModel = new Credit($db->pdo());
        $credits = [];
        $payments = [];
        $summary = ['total_issued' => 0, 'total_paid' => 0, 'total_outstanding' => 0, 'facility_count' => 0];

        try {
            $credits = array_values(array_filter(
                $creditModel->listByCompany($companyId),
                static fn (array $credit): bool => (int) ($credit['customer_id'] ?? 0) === (int) $id
            ));

            foreach ($credits as $c) {
                $summary['total_issued'] += (float) ($c['total_amount'] ?? 0);
                $summary['total_paid'] += (float) ($c['amount_paid'] ?? 0);
                $summary['total_outstanding'] += (float) ($c['outstanding'] ?? 0);
                $summary['facility_count']++;
            }

            foreach ($credits as $c) {
                $creditId = (int) ($c['credit_id'] ?? 0);
                if ($creditId <= 0) {
                    continue;
                }

                $rows = $creditModel->paymentListByCreditForCompany($companyId, $creditId);
                foreach ($rows as $row) {
                    $row['credit_no'] = $c['credit_no'] ?? null;
                    $payments[] = $row;
                }
            }

            usort($payments, static function (array $a, array $b): int {
                return strcmp((string) ($b['payment_date'] ?? ''), (string) ($a['payment_date'] ?? ''));
            });
            $payments = array_slice($payments, 0, 50);
        } catch (\Throwable $e) {
            error_log('CreditCustomer show error: ' . $e->getMessage());
        }

        return view('credit-customers.show', [
            'company' => $company,
            'customer' => $customer,
            'credits' => $credits,
            'payments' => $payments,
            'summary' => $summary,
        ]);
    }

    public function edit($id, RequestContext $context, Database $db)
    {
        $company = $context->company();
        if (!$company) {
            return response('Company not found', 404);
        }

        $customerModel = new Customer($db->pdo());
        $customer = $customerModel->findByIdForCompany((int) $id, (int) $company['company_id']);

        if (!$customer) {
            return redirect('/credit-customers')->withErrors(['customer' => 'Customer not found.']);
        }

        return view('credit-customers.edit', ['company' => $company, 'customer' => $customer]);
    }

    public function update(Request $request, $id, RequestContext $context, Database $db)
    {
        $company = $context->company();
        if (!$company) {
            return response('Company not found', 404);
        }

        $validated = $request->validate([
            'customer_name' => 'required|string|max:191',
            'email' => 'nullable|email|max:191',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:100',
            'province' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:100',
            'tax_number' => 'nullable|string|max:100',
            'id_number' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:2000',
            'status' => 'nullable|in:active,inactive',
        ]);

        $customerModel = new Customer($db->pdo());
        try {
            $updated = $customerModel->updateForCompany(
                (int) $id,
                (int) $company['company_id'],
                $validated['customer_name'],
                $validated['tax_number'] ?? null,
                $validated['email'] ?? null,
                $validated['phone'] ?? null,
                $validated['address'] ?? null,
                $validated['notes'] ?? null,
                $validated['status'] ?? 'active',
                $validated['city'] ?? null,
                $validated['province'] ?? null,
                $validated['postal_code'] ?? null,
                $validated['country'] ?? null,
                $validated['id_number'] ?? null
            );
        } catch (\Throwable $e) {
            error_log('CreditCustomerController update error: ' . $e->getMessage());
            $updated = false;
        }

        if (!$updated) {
            return back()->withErrors(['customer_name' => 'Could not update customer.'])->withInput();
        }

        return redirect("/credit-customers/{$id}")->with('success', 'Customer updated.');
    }

    public function destroy($id, RequestContext $context, Database $db)
    {
        $company = $context->company();
        if (!$company) {
            return response('Company not found', 404);
        }

        $customerModel = new Customer($db->pdo());
        try {
            $deleted = $customerModel->deleteForCompany((int) $id, (int) $company['company_id']);
        } catch (\Throwable $e) {
            error_log('CreditCustomerController delete error: ' . $e->getMessage());
            $deleted = false;
        }

        if (!$deleted) {
            return redirect('/credit-customers')->withErrors(['customer' => 'Could not delete customer.']);
        }

        return redirect('/credit-customers')->with('success', 'Customer deleted.');
    }

    public function toggleStatus($id, RequestContext $context, Database $db)
    {
        $company = $context->company();
        if (!$company) {
            return response('Company not found', 404);
        }

        $companyId = (int) $company['company_id'];
        $customerModel = new Customer($db->pdo());
        $customer = $customerModel->findByIdForCompany((int) $id, $companyId);

        if (!$customer) {
            return redirect('/credit-customers')->withErrors(['customer' => 'Customer not found.']);
        }

        $currentStatus = (string) ($customer['status'] ?? 'active');
        $newStatus = $currentStatus === 'active' ? 'inactive' : 'active';

        try {
            $db->pdo()->prepare(
                'UPDATE customers SET status = :status WHERE customer_id = :id AND company_id = :cid'
            )->execute(['status' => $newStatus, 'id' => (int) $id, 'cid' => $companyId]);
        } catch (\Throwable $e) {
            error_log('CreditCustomerController toggleStatus error: ' . $e->getMessage());
            return redirect('/credit-customers')->withErrors(['customer' => 'Could not update status.']);
        }

        return redirect('/credit-customers')->with('success', 'Customer status changed to ' . $newStatus . '.');
    }
}
