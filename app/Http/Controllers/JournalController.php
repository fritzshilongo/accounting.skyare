<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Core\RequestContext;
use App\Core\Database;
use App\Support\SchemaCompat;

class JournalController extends Controller
{
    public function index(Request $request, RequestContext $context, Database $db)
    {
        $company = $context->company();
        if (!$company) {
            return response('Company not found', 404);
        }

        $companyId = (int) $company['company_id'];
        $search = trim((string) $request->input('search', ''));
        $entries = collect();

        try {
            $pdo = $db->pdo();
            $idCol = SchemaCompat::firstExisting('journal_entries', ['entry_id', 'id'], 'entry_id') ?? 'entry_id';
            $dateCol = SchemaCompat::firstExisting('journal_entries', ['date', 'entry_date'], 'date') ?? 'date';
            $sql = 'SELECT *, ' . $idCol . ' AS _entry_id, ' . $dateCol . ' AS _entry_date FROM journal_entries WHERE company_id = ?';
            $params = [$companyId];

            if ($search !== '') {
                $sql .= ' AND (reference LIKE ? OR description LIKE ?)';
                $params[] = "%{$search}%";
                $params[] = "%{$search}%";
            }

            $sql .= ' ORDER BY ' . $dateCol . ' DESC';

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $all = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
            foreach ($all as $i => $row) {
                $all[$i]['entry_id'] = $row['entry_id'] ?? $row['id'] ?? $row['_entry_id'] ?? null;
                $all[$i]['date'] = $row['date'] ?? $row['entry_date'] ?? $row['_entry_date'] ?? null;
            }
            $entries = collect($all);
        } catch (\Exception $e) {
            error_log("Journal entries fetch error: " . $e->getMessage());
        }

        // Manual pagination
        $page = max(1, (int) $request->input('page', 1));
        $perPage = 20;
        $total = $entries->count();
        $items = $entries->slice(($page - 1) * $perPage, $perPage)->values();
        $paginator = new \Illuminate\Pagination\LengthAwarePaginator($items, $total, $perPage, $page, [
            'path' => '/journal-entries',
            'query' => $request->query(),
        ]);

        return view('journal.index', [
            'company' => $company,
            'entries' => $paginator,
            'search' => $search,
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
            'reference' => 'required|string|max:100',
            'description' => 'required|string|max:500',
            'debit_amount' => 'required|numeric|min:0',
            'credit_amount' => 'required|numeric|min:0',
        ]);

        $debit = (float) $validated['debit_amount'];
        $credit = (float) $validated['credit_amount'];

        if (abs($debit - $credit) > 0.01) {
            return back()->withErrors(['debit_amount' => 'Debit and credit amounts must be equal for a balanced entry.'])->withInput();
        }

        if ($debit <= 0) {
            return back()->withErrors(['debit_amount' => 'Amounts must be greater than zero.'])->withInput();
        }

        try {
            $pdo = $db->pdo();
            $dateCol = SchemaCompat::firstExisting('journal_entries', ['date', 'entry_date'], 'date') ?? 'date';
            $hasCreatedAt = SchemaCompat::hasColumn('journal_entries', 'created_at');
            $hasUpdatedAt = SchemaCompat::hasColumn('journal_entries', 'updated_at');
            $hasStatus = SchemaCompat::hasColumn('journal_entries', 'status');
            $hasCurrency = SchemaCompat::hasColumn('journal_entries', 'currency');
            $hasAccountCode = SchemaCompat::hasColumn('journal_entries', 'account_code');

            $columns = ['company_id', $dateCol, 'reference', 'description', 'debit_amount', 'credit_amount'];
            $values = ['?', '?', '?', '?', '?', '?'];
            $params = [
                (int) $company['company_id'],
                $validated['date'],
                $validated['reference'],
                $validated['description'],
                $debit,
                $credit,
            ];

            if ($hasStatus) {
                $columns[] = 'status';
                $values[] = '?';
                $params[] = 'draft';
            }
            if ($hasCurrency) {
                $columns[] = 'currency';
                $values[] = '?';
                $params[] = 'USD';
            }
            if ($hasAccountCode) {
                $columns[] = 'account_code';
                $values[] = '?';
                $params[] = null;
            }
            if ($hasCreatedAt) {
                $columns[] = 'created_at';
                $values[] = 'NOW()';
            }
            if ($hasUpdatedAt) {
                $columns[] = 'updated_at';
                $values[] = 'NOW()';
            }

            $stmt = $pdo->prepare('INSERT INTO journal_entries (' . implode(', ', $columns) . ') VALUES (' . implode(', ', $values) . ')');
            $stmt->execute($params);

            return redirect('/journal-entries')->with('success', 'Journal entry recorded.');
        } catch (\Exception $e) {
            error_log("Journal entry creation error: " . $e->getMessage());
            return back()->withErrors(['date' => 'Error creating journal entry.'])->withInput();
        }
    }

    public function edit($id, Request $request, RequestContext $context, Database $db)
    {
        $company = $context->company();
        if (!$company) return response('Company not found', 404);

        $entry = null;
        try {
            $idCol = SchemaCompat::firstExisting('journal_entries', ['entry_id', 'id'], 'entry_id') ?? 'entry_id';
            $dateCol = SchemaCompat::firstExisting('journal_entries', ['date', 'entry_date'], 'date') ?? 'date';
            $stmt = $db->pdo()->prepare('SELECT *, ' . $idCol . ' AS _entry_id, ' . $dateCol . ' AS _entry_date FROM journal_entries WHERE ' . $idCol . ' = ? AND company_id = ? LIMIT 1');
            $stmt->execute([$id, (int) $company['company_id']]);
            $entry = $stmt->fetch(\PDO::FETCH_ASSOC);
            if ($entry) {
                $entry['entry_id'] = $entry['entry_id'] ?? $entry['id'] ?? $entry['_entry_id'] ?? null;
                $entry['date'] = $entry['date'] ?? $entry['entry_date'] ?? $entry['_entry_date'] ?? null;
            }
        } catch (\Throwable $e) {
            return redirect('/journal-entries')->withErrors(['entry' => 'Could not load journal entry.']);
        }
        if (!$entry) abort(404);

        return view('journal.edit', ['company' => $company, 'entry' => $entry]);
    }

    public function update($id, Request $request, RequestContext $context, Database $db)
    {
        $company = $context->company();
        if (!$company) return response('Company not found', 404);

        $validated = $request->validate([
            'date' => 'required|date',
            'reference' => 'required|string|max:100',
            'description' => 'required|string|max:500',
            'debit_amount' => 'required|numeric|min:0',
            'credit_amount' => 'required|numeric|min:0',
        ]);

        $debit = (float) $validated['debit_amount'];
        $credit = (float) $validated['credit_amount'];

        if (abs($debit - $credit) > 0.01) {
            return back()->withErrors(['debit_amount' => 'Debit and credit amounts must be equal.'])->withInput();
        }

        try {
            $idCol = SchemaCompat::firstExisting('journal_entries', ['entry_id', 'id'], 'entry_id') ?? 'entry_id';
            $dateCol = SchemaCompat::firstExisting('journal_entries', ['date', 'entry_date'], 'date') ?? 'date';
            $hasUpdatedAt = SchemaCompat::hasColumn('journal_entries', 'updated_at');

            $sql = 'UPDATE journal_entries SET ' . $dateCol . ' = ?, reference = ?, description = ?, debit_amount = ?, credit_amount = ?';
            if ($hasUpdatedAt) {
                $sql .= ', updated_at = NOW()';
            }
            $sql .= ' WHERE ' . $idCol . ' = ? AND company_id = ?';

            $stmt = $db->pdo()->prepare($sql);
            $stmt->execute([
                $validated['date'], $validated['reference'], $validated['description'],
                $debit, $credit, $id, (int) $company['company_id'],
            ]);
        } catch (\Throwable $e) {
            return back()->withErrors(['date' => 'Could not update journal entry.'])->withInput();
        }

        return redirect('/journal-entries')->with('success', 'Journal entry updated.');
    }

    public function destroy($id, RequestContext $context, Database $db)
    {
        $company = $context->company();
        if (!$company) return response('Company not found', 404);

        try {
            $idCol = SchemaCompat::firstExisting('journal_entries', ['entry_id', 'id'], 'entry_id') ?? 'entry_id';
            $db->pdo()->prepare('DELETE FROM journal_entries WHERE ' . $idCol . ' = ? AND company_id = ?')
                ->execute([$id, (int) $company['company_id']]);
        } catch (\Throwable $e) {
            return redirect('/journal-entries')->withErrors(['entry' => 'Could not delete journal entry.']);
        }

        return redirect('/journal-entries')->with('success', 'Journal entry deleted.');
    }
}
