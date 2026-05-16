<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Core\RequestContext;
use App\Core\Database;
use App\Support\SchemaCompat;

class RecurringInvoiceController extends Controller
{
    public function index(RequestContext $context, Database $db)
    {
        $company = $context->company();
        if (!$company) return response('Company not found', 404);
        $companyId = (int) $company['company_id'];

        $recurring = [];
        try {
            $clientNameColumn = SchemaCompat::firstExisting('clients', ['name', 'client_name'], 'name') ?? 'name';
            $stmt = $db->pdo()->prepare(
                'SELECT r.*, c.' . $clientNameColumn . ' AS client_name FROM recurring_invoices r
                 LEFT JOIN clients c ON c.client_id = r.client_id
                 WHERE r.company_id = :cid ORDER BY r.created_at DESC'
            );
            $stmt->execute(['cid' => $companyId]);
            $recurring = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable $e) {}

        return view('recurring.index', [
            'company' => $company,
            'user' => $_SESSION['user'] ?? null,
            'recurring' => $recurring,
        ]);
    }

    public function create(RequestContext $context, Database $db)
    {
        $company = $context->company();
        if (!$company) return response('Company not found', 404);
        $companyId = (int) $company['company_id'];

        $clients = [];
        try {
            $clientNameColumn = SchemaCompat::firstExisting('clients', ['name', 'client_name'], 'name') ?? 'name';
            $stmt = $db->pdo()->prepare(
                'SELECT client_id, ' . $clientNameColumn . ' AS client_name
                 FROM clients
                 WHERE company_id = :cid
                 ORDER BY ' . $clientNameColumn
            );
            $stmt->execute(['cid' => $companyId]);
            $clients = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable $e) {}

        $products = [];
        try {
            $nameColumn = SchemaCompat::productNameColumn();
            $priceColumn = SchemaCompat::productPriceColumn();

            $stmt = $db->pdo()->prepare(
                'SELECT product_id, ' . $nameColumn . ' AS product_name, ' . $priceColumn . ' AS sell_price
                 FROM products
                 WHERE company_id = :cid
                 ORDER BY ' . $nameColumn
            );
            $stmt->execute(['cid' => $companyId]);
            $products = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable $e) {}

        $taxRates = [];
        try {
            $stmt = $db->pdo()->prepare('SELECT * FROM tax_rates WHERE company_id = :cid AND is_active = 1 ORDER BY name');
            $stmt->execute(['cid' => $companyId]);
            $taxRates = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable $e) {}

        return view('recurring.create', [
            'company' => $company,
            'user' => $_SESSION['user'] ?? null,
            'clients' => $clients,
            'products' => $products,
            'taxRates' => $taxRates,
        ]);
    }

    public function store(Request $request, RequestContext $context, Database $db)
    {
        $company = $context->company();
        if (!$company) return response('Company not found', 404);
        $companyId = (int) $company['company_id'];

        $validated = $request->validate([
            'client_id' => 'required|integer',
            'frequency' => 'required|in:weekly,biweekly,monthly,quarterly,yearly',
            'description' => 'nullable|string|max:500',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after:start_date',
            'max_occurrences' => 'nullable|integer|min:1',
            'tax_rate' => 'nullable|numeric|min:0',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'nullable|integer|min:1',
            'items.*.description' => 'required|string|max:191',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        // Verify client belongs to company
        $clientNameColumn = SchemaCompat::firstExisting('clients', ['name', 'client_name'], 'name') ?? 'name';
        try {
            $clientCheck = $db->pdo()->prepare(
                'SELECT client_id, ' . $clientNameColumn . ' AS client_name FROM clients WHERE client_id = :id AND company_id = :cid LIMIT 1'
            );
            $clientCheck->execute(['id' => $validated['client_id'], 'cid' => $companyId]);
            $client = $clientCheck->fetch(\PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            return back()->withErrors(['client_id' => 'Could not verify selected client for this company.'])->withInput();
        }

        if (!$client) {
            return back()->withErrors(['client_id' => 'Client not found.'])->withInput();
        }

        $amount = 0;
        foreach ($validated['items'] as $item) {
            $amount += $item['quantity'] * $item['unit_price'];
        }
        $taxRate = (float) ($validated['tax_rate'] ?? 0);
        $taxAmount = $amount * ($taxRate / 100);
        $total = $amount + $taxAmount;

        try {
            $db->pdo()->beginTransaction();

            $now = now()->toDateTimeString();

            $invoiceValues = [];
            $invoiceParams = [];
            $pushInvoiceValue = static function (string $column, mixed $value) use (&$invoiceValues, &$invoiceParams): void {
                if (!SchemaCompat::hasColumn('recurring_invoices', $column)) {
                    return;
                }
                $invoiceValues[] = $column;
                $invoiceParams[$column] = $value;
            };

            $pushInvoiceValue('company_id', $companyId);
            $pushInvoiceValue('client_id', (int) $validated['client_id']);
            $pushInvoiceValue('client_name', (string) ($client['client_name'] ?? ''));
            $pushInvoiceValue('frequency', $validated['frequency']);
            $pushInvoiceValue('amount', $amount);
            $pushInvoiceValue('tax_rate', $taxRate);
            $pushInvoiceValue('tax_amount', $taxAmount);
            $pushInvoiceValue('total', $total);
            $pushInvoiceValue('description', $validated['description'] ?? null);
            $pushInvoiceValue('start_date', $validated['start_date']);
            $pushInvoiceValue('end_date', $validated['end_date'] ?? null);
            $pushInvoiceValue('next_run_date', $validated['start_date']);
            $pushInvoiceValue('max_occurrences', $validated['max_occurrences'] ?? null);
            $pushInvoiceValue('status', 'active');
            $pushInvoiceValue('created_at', $now);
            $pushInvoiceValue('updated_at', $now);

            if ($invoiceValues === []) {
                throw new \RuntimeException('Recurring invoices table has no writable columns.');
            }

            $invoicePlaceholders = array_map(static fn (string $c): string => ':' . $c, $invoiceValues);
            $stmt = $db->pdo()->prepare(
                'INSERT INTO recurring_invoices (' . implode(', ', $invoiceValues) . ') VALUES (' . implode(', ', $invoicePlaceholders) . ')'
            );
            $stmt->execute($invoiceParams);

            $recurringId = $db->pdo()->lastInsertId();

            foreach ($validated['items'] as $item) {
                $lineTotal = $item['quantity'] * $item['unit_price'];

                $itemValues = [];
                $itemParams = [];
                $pushItemValue = static function (string $column, mixed $value) use (&$itemValues, &$itemParams): void {
                    if (!SchemaCompat::hasColumn('recurring_invoice_items', $column)) {
                        return;
                    }
                    $itemValues[] = $column;
                    $itemParams[$column] = $value;
                };

                $pushItemValue('recurring_id', (int) $recurringId);
                $pushItemValue('product_id', isset($item['product_id']) ? (int) $item['product_id'] : null);
                $pushItemValue('description', $item['description']);
                $pushItemValue('quantity', $item['quantity']);
                $pushItemValue('unit_price', $item['unit_price']);
                $pushItemValue('line_total', $lineTotal);
                $pushItemValue('created_at', $now);
                $pushItemValue('updated_at', $now);

                if ($itemValues === []) {
                    continue;
                }

                $itemPlaceholders = array_map(static fn (string $c): string => ':' . $c, $itemValues);
                $db->pdo()->prepare(
                    'INSERT INTO recurring_invoice_items (' . implode(', ', $itemValues) . ') VALUES (' . implode(', ', $itemPlaceholders) . ')'
                )->execute($itemParams);
            }

            $db->pdo()->commit();
        } catch (\Throwable $e) {
            if ($db->pdo()->inTransaction()) $db->pdo()->rollBack();
            return back()->withErrors(['client_id' => 'Could not create recurring invoice: ' . $e->getMessage()])->withInput();
        }

        return redirect('/recurring-invoices')->with('success', 'Recurring invoice created.');
    }

    public function show($id, RequestContext $context, Database $db)
    {
        $company = $context->company();
        if (!$company) return response('Company not found', 404);
        $companyId = (int) $company['company_id'];

        $recurring = null;
        try {
            $stmt = $db->pdo()->prepare('SELECT * FROM recurring_invoices WHERE recurring_id = :id AND company_id = :cid LIMIT 1');
            $stmt->execute(['id' => (int) $id, 'cid' => $companyId]);
            $recurring = $stmt->fetch(\PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {}

        if (!$recurring) {
            return redirect('/recurring-invoices')->withErrors(['recurring' => 'Recurring invoice not found.']);
        }

        $items = [];
        try {
            $stmt = $db->pdo()->prepare('SELECT * FROM recurring_invoice_items WHERE recurring_id = :id');
            $stmt->execute(['id' => (int) $id]);
            $items = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable $e) {}

        return view('recurring.show', [
            'company' => $company,
            'user' => $_SESSION['user'] ?? null,
            'recurring' => $recurring,
            'items' => $items,
        ]);
    }

    public function toggleStatus($id, RequestContext $context, Database $db)
    {
        $company = $context->company();
        if (!$company) return response('Company not found', 404);
        $companyId = (int) $company['company_id'];

        try {
            $stmt = $db->pdo()->prepare('SELECT recurring_id, status FROM recurring_invoices WHERE recurring_id = :id AND company_id = :cid LIMIT 1');
            $stmt->execute(['id' => (int) $id, 'cid' => $companyId]);
            $rec = $stmt->fetch(\PDO::FETCH_ASSOC);

            if ($rec) {
                $newStatus = $rec['status'] === 'active' ? 'paused' : 'active';
                $db->pdo()->prepare('UPDATE recurring_invoices SET status = :status, updated_at = NOW() WHERE recurring_id = :id AND company_id = :cid')
                    ->execute(['status' => $newStatus, 'id' => (int) $id, 'cid' => $companyId]);
            }
        } catch (\Throwable $e) {
            return back()->withErrors(['recurring' => 'Could not update status.']);
        }

        return redirect('/recurring-invoices')->with('success', 'Status updated.');
    }

    public function destroy($id, RequestContext $context, Database $db)
    {
        $company = $context->company();
        if (!$company) return response('Company not found', 404);
        $companyId = (int) $company['company_id'];

        try {
            $db->pdo()->prepare('DELETE FROM recurring_invoice_items WHERE recurring_id = :id')
                ->execute(['id' => (int) $id]);
            $db->pdo()->prepare('DELETE FROM recurring_invoices WHERE recurring_id = :id AND company_id = :cid')
                ->execute(['id' => (int) $id, 'cid' => $companyId]);
        } catch (\Throwable $e) {
            return back()->withErrors(['recurring' => 'Could not delete.']);
        }

        return redirect('/recurring-invoices')->with('success', 'Recurring invoice deleted.');
    }
}
