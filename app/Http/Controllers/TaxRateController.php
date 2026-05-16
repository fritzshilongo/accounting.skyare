<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Core\RequestContext;
use App\Core\Database;

class TaxRateController extends Controller
{
    public function index(RequestContext $context, Database $db)
    {
        $company = $context->company();
        if (!$company) return response('Company not found', 404);
        $companyId = (int) $company['company_id'];

        $taxRates = [];
        try {
            $stmt = $db->pdo()->prepare('SELECT * FROM tax_rates WHERE company_id = :cid ORDER BY name');
            $stmt->execute(['cid' => $companyId]);
            $taxRates = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable $e) {}

        return view('tax-rates.index', [
            'company' => $company,
            'user' => $_SESSION['user'] ?? null,
            'taxRates' => $taxRates,
        ]);
    }

    public function store(Request $request, RequestContext $context, Database $db)
    {
        $company = $context->company();
        if (!$company) return response('Company not found', 404);
        $companyId = (int) $company['company_id'];

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'rate' => 'required|numeric|min:0|max:100',
            'type' => 'required|in:percentage,fixed',
            'is_default' => 'nullable|boolean',
        ]);

        try {
            // If marking as default, clear other defaults
            if (!empty($validated['is_default'])) {
                $db->pdo()->prepare('UPDATE tax_rates SET is_default = 0 WHERE company_id = :cid')
                    ->execute(['cid' => $companyId]);
            }

            $db->pdo()->prepare(
                'INSERT INTO tax_rates (company_id, name, rate, type, is_default, is_active, created_at, updated_at)
                 VALUES (:cid, :name, :rate, :type, :is_default, 1, NOW(), NOW())'
            )->execute([
                'cid' => $companyId,
                'name' => $validated['name'],
                'rate' => $validated['rate'],
                'type' => $validated['type'],
                'is_default' => !empty($validated['is_default']) ? 1 : 0,
            ]);
        } catch (\Throwable $e) {
            return back()->withErrors(['name' => 'Could not create tax rate.'])->withInput();
        }

        return redirect('/tax-rates')->with('success', 'Tax rate created.');
    }

    public function update(Request $request, $id, RequestContext $context, Database $db)
    {
        $company = $context->company();
        if (!$company) return response('Company not found', 404);
        $companyId = (int) $company['company_id'];

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'rate' => 'required|numeric|min:0|max:100',
            'type' => 'required|in:percentage,fixed',
            'is_default' => 'nullable|boolean',
        ]);

        try {
            if (!empty($validated['is_default'])) {
                $db->pdo()->prepare('UPDATE tax_rates SET is_default = 0 WHERE company_id = :cid')
                    ->execute(['cid' => $companyId]);
            }

            $db->pdo()->prepare(
                'UPDATE tax_rates SET name = :name, rate = :rate, type = :type, is_default = :is_default, updated_at = NOW()
                 WHERE tax_rate_id = :id AND company_id = :cid'
            )->execute([
                'name' => $validated['name'],
                'rate' => $validated['rate'],
                'type' => $validated['type'],
                'is_default' => !empty($validated['is_default']) ? 1 : 0,
                'id' => (int) $id,
                'cid' => $companyId,
            ]);
        } catch (\Throwable $e) {
            return back()->withErrors(['name' => 'Could not update tax rate.']);
        }

        return redirect('/tax-rates')->with('success', 'Tax rate updated.');
    }

    public function toggleActive($id, RequestContext $context, Database $db)
    {
        $company = $context->company();
        if (!$company) return response('Company not found', 404);
        $companyId = (int) $company['company_id'];

        try {
            $db->pdo()->prepare(
                'UPDATE tax_rates SET is_active = NOT is_active, updated_at = NOW() WHERE tax_rate_id = :id AND company_id = :cid'
            )->execute(['id' => (int) $id, 'cid' => $companyId]);
        } catch (\Throwable $e) {
            return back()->withErrors(['tax_rate' => 'Could not toggle status.']);
        }

        return redirect('/tax-rates')->with('success', 'Tax rate status updated.');
    }

    public function destroy($id, RequestContext $context, Database $db)
    {
        $company = $context->company();
        if (!$company) return response('Company not found', 404);
        $companyId = (int) $company['company_id'];

        try {
            $db->pdo()->prepare('DELETE FROM tax_rates WHERE tax_rate_id = :id AND company_id = :cid')
                ->execute(['id' => (int) $id, 'cid' => $companyId]);
        } catch (\Throwable $e) {
            return back()->withErrors(['tax_rate' => 'Could not delete tax rate.']);
        }

        return redirect('/tax-rates')->with('success', 'Tax rate deleted.');
    }
}
