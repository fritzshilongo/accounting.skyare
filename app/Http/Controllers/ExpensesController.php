<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Core\RequestContext;
use App\Core\Database;
use App\Support\SchemaCompat;

class ExpensesController extends Controller
{
    public function index(Request $request, RequestContext $context, Database $db)
    {
        $company = $context->company();
        if (!$company) {
            return response('Company not found', 404);
        }

        $companyId = (int) $company['company_id'];
        $search = trim((string) $request->input('search', ''));
        $category = trim((string) $request->input('category', ''));
        $entries = collect();
        $total = 0;

        try {
            $pdo = $db->pdo();
            $idCol = SchemaCompat::firstExisting('expenses', ['expense_id', 'id'], 'expense_id') ?? 'expense_id';
            $dateCol = SchemaCompat::firstExisting('expenses', ['date', 'expense_date'], 'date') ?? 'date';
            $sql = 'SELECT *, ' . $idCol . ' AS _expense_id, ' . $dateCol . ' AS _expense_date FROM expenses WHERE company_id = ?';
            $params = [$companyId];

            if ($search !== '') {
                $sql .= ' AND (description LIKE ? OR category LIKE ?)';
                $params[] = "%{$search}%";
                $params[] = "%{$search}%";
            }
            if ($category !== '') {
                $sql .= ' AND category = ?';
                $params[] = $category;
            }

            $sql .= ' ORDER BY ' . $dateCol . ' DESC';

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $all = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
            $entries = collect($all);

            foreach ($all as $i => $e) {
                $all[$i]['expense_id'] = $e['expense_id'] ?? $e['id'] ?? $e['_expense_id'] ?? null;
                $all[$i]['date'] = $e['date'] ?? $e['expense_date'] ?? $e['_expense_date'] ?? null;
                $total += (float) $e['amount'];
            }
            $entries = collect($all);
        } catch (\Exception $e) {
            error_log("Expenses fetch error: " . $e->getMessage());
        }

        $page = max(1, (int) $request->input('page', 1));
        $perPage = 20;
        $items = $entries->slice(($page - 1) * $perPage, $perPage)->values();
        $paginator = new \Illuminate\Pagination\LengthAwarePaginator($items, $entries->count(), $perPage, $page, [
            'path' => '/expenses',
            'query' => $request->query(),
        ]);

        return view('expenses.index', [
            'company' => $company,
            'expenses' => $paginator,
            'total' => $total,
            'search' => $search,
            'selectedCategory' => $category,
            'categories' => ['Office Supplies', 'Travel', 'Meals', 'Utilities', 'Other'],
        ]);
    }

    public function store(Request $request, RequestContext $context, Database $db)
    {
        $company = $context->company();
        if (!$company) {
            return response('Company not found', 404);
        }

        $validated = $request->validate([
            'date' => 'required|date',
            'category' => 'required|string|max:100',
            'description' => 'required|string|max:500',
            'amount' => 'required|numeric|min:0.01',
        ]);

        try {
            $pdo = $db->pdo();
            $dateCol = SchemaCompat::firstExisting('expenses', ['date', 'expense_date'], 'date') ?? 'date';
            $hasUpdatedAt = SchemaCompat::hasColumn('expenses', 'updated_at');
            $hasCreatedAt = SchemaCompat::hasColumn('expenses', 'created_at');
            $hasStatus = SchemaCompat::hasColumn('expenses', 'status');

            $columns = ['company_id', $dateCol, 'category', 'description', 'amount'];
            $values = ['?', '?', '?', '?', '?'];
            $params = [
                (int) $company['company_id'],
                $validated['date'],
                $validated['category'],
                $validated['description'],
                (float) $validated['amount'],
            ];

            if ($hasStatus) {
                $columns[] = 'status';
                $values[] = '?';
                $params[] = 'recorded';
            }

            if ($hasCreatedAt) {
                $columns[] = 'created_at';
                $values[] = 'NOW()';
            }
            if ($hasUpdatedAt) {
                $columns[] = 'updated_at';
                $values[] = 'NOW()';
            }

            $stmt = $pdo->prepare('INSERT INTO expenses (' . implode(', ', $columns) . ') VALUES (' . implode(', ', $values) . ')');
            $stmt->execute($params);

            return redirect('/expenses')->with('success', 'Expense recorded.');
        } catch (\Exception $e) {
            error_log("Expense creation error: " . $e->getMessage());
            return back()->withErrors(['date' => 'Error creating expense.'])->withInput();
        }
    }

    public function edit($id, Request $request, RequestContext $context, Database $db)
    {
        $company = $context->company();
        if (!$company) return response('Company not found', 404);

        $idCol = SchemaCompat::firstExisting('expenses', ['expense_id', 'id'], 'expense_id') ?? 'expense_id';
        $dateCol = SchemaCompat::firstExisting('expenses', ['date', 'expense_date'], 'date') ?? 'date';

        $stmt = $db->pdo()->prepare('SELECT *, ' . $idCol . ' AS _expense_id, ' . $dateCol . ' AS _expense_date FROM expenses WHERE ' . $idCol . ' = ? AND company_id = ? LIMIT 1');
        $stmt->execute([$id, (int) $company['company_id']]);
        $expense = $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
        if (!$expense) abort(404);

        $expense['expense_id'] = $expense['expense_id'] ?? $expense['id'] ?? $expense['_expense_id'] ?? null;
        $expense['date'] = $expense['date'] ?? $expense['expense_date'] ?? $expense['_expense_date'] ?? null;

        return view('expenses.edit', [
            'company' => $company,
            'expense' => $expense,
            'categories' => ['Office Supplies', 'Travel', 'Meals', 'Utilities', 'Other'],
        ]);
    }

    public function update($id, Request $request, RequestContext $context, Database $db)
    {
        $company = $context->company();
        if (!$company) return response('Company not found', 404);

        $validated = $request->validate([
            'date' => 'required|date',
            'category' => 'required|string|max:100',
            'description' => 'required|string|max:500',
            'amount' => 'required|numeric|min:0.01',
        ]);

        $idCol = SchemaCompat::firstExisting('expenses', ['expense_id', 'id'], 'expense_id') ?? 'expense_id';
        $dateCol = SchemaCompat::firstExisting('expenses', ['date', 'expense_date'], 'date') ?? 'date';
        $hasUpdatedAt = SchemaCompat::hasColumn('expenses', 'updated_at');

        $sql = 'UPDATE expenses SET ' . $dateCol . ' = ?, category = ?, description = ?, amount = ?';
        $params = [$validated['date'], $validated['category'], $validated['description'], (float) $validated['amount']];
        if ($hasUpdatedAt) {
            $sql .= ', updated_at = NOW()';
        }
        $sql .= ' WHERE ' . $idCol . ' = ? AND company_id = ?';
        $params[] = $id;
        $params[] = (int) $company['company_id'];

        $stmt = $db->pdo()->prepare($sql);
        $stmt->execute($params);

        return redirect('/expenses')->with('success', 'Expense updated.');
    }

    public function destroy($id, RequestContext $context, Database $db)
    {
        $company = $context->company();
        if (!$company) return response('Company not found', 404);

        $idCol = SchemaCompat::firstExisting('expenses', ['expense_id', 'id'], 'expense_id') ?? 'expense_id';
        $db->pdo()->prepare('DELETE FROM expenses WHERE ' . $idCol . ' = ? AND company_id = ?')
            ->execute([$id, (int) $company['company_id']]);

        return redirect('/expenses')->with('success', 'Expense deleted.');
    }
}
