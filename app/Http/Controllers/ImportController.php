<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Core\RequestContext;
use App\Core\Database;

class ImportController extends Controller
{
    public function index(RequestContext $context)
    {
        $company = $context->company();
        if (!$company) return response('Company not found', 404);

        return view('import.index', [
            'company' => $company,
            'user' => $_SESSION['user'] ?? null,
        ]);
    }

    public function importClients(Request $request, RequestContext $context, Database $db)
    {
        $company = $context->company();
        if (!$company) return response('Company not found', 404);
        $companyId = (int) $company['company_id'];

        $request->validate([
            'csv_file' => 'required|file|max:2048',
        ]);

        $file = $request->file('csv_file');
        $ext = strtolower($file->getClientOriginalExtension());
        if (!in_array($ext, ['csv', 'txt'], true)) {
            return back()->withErrors(['csv_file' => 'File must be a CSV (.csv or .txt) file.']);
        }

        $path = $file->getRealPath();
        $handle = fopen($path, 'r');
        if (!$handle) {
            return back()->withErrors(['csv_file' => 'Could not read file.']);
        }

        $header = fgetcsv($handle);
        if (!$header) {
            fclose($handle);
            return back()->withErrors(['csv_file' => 'Empty CSV file.']);
        }

        // Handle BOM (byte order mark) in first header
        if (isset($header[0])) {
            $header[0] = preg_replace('/^\x{FEFF}/u', '', $header[0]);
        }

        // Normalize headers
        $header = array_map(function ($h) {
            return strtolower(trim(str_replace([' ', '-'], '_', $h)));
        }, $header);

        $nameCol = $this->findColumn($header, ['client_name', 'name', 'company_name', 'company']);
        $emailCol = $this->findColumn($header, ['email', 'email_address', 'client_email']);
        $phoneCol = $this->findColumn($header, ['phone', 'telephone', 'phone_number', 'client_phone']);
        $addressCol = $this->findColumn($header, ['address', 'street_address', 'client_address']);

        if ($nameCol === null) {
            fclose($handle);
            return back()->withErrors(['csv_file' => 'CSV must have a "name" or "client_name" column.']);
        }

        $imported = 0;
        $skipped = 0;

        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) <= $nameCol) continue;

            $name = trim($row[$nameCol] ?? '');
            if ($name === '') { $skipped++; continue; }

            $email = $emailCol !== null ? trim($row[$emailCol] ?? '') : null;
            $phone = $phoneCol !== null ? trim($row[$phoneCol] ?? '') : null;
            $address = $addressCol !== null ? trim($row[$addressCol] ?? '') : null;

            // Skip duplicate by name+company
            $check = $db->pdo()->prepare('SELECT client_id FROM clients WHERE name = :name AND company_id = :cid LIMIT 1');
            $check->execute(['name' => $name, 'cid' => $companyId]);
            if ($check->fetch()) { $skipped++; continue; }

            try {
                $db->pdo()->prepare(
                    'INSERT INTO clients (company_id, name, email, phone, address, created_at, updated_at)
                     VALUES (:cid, :name, :email, :phone, :address, NOW(), NOW())'
                )->execute([
                    'cid' => $companyId,
                    'name' => $name,
                    'email' => $email ?: null,
                    'phone' => $phone ?: null,
                    'address' => $address ?: null,
                ]);
                $imported++;
            } catch (\Throwable $e) {
                $skipped++;
            }
        }

        fclose($handle);
        return redirect('/import')->with('success', "Imported {$imported} clients. Skipped {$skipped} rows.");
    }

    public function importProducts(Request $request, RequestContext $context, Database $db)
    {
        $company = $context->company();
        if (!$company) return response('Company not found', 404);
        $companyId = (int) $company['company_id'];

        $request->validate([
            'csv_file' => 'required|file|max:2048',
        ]);

        $file = $request->file('csv_file');
        $ext = strtolower($file->getClientOriginalExtension());
        if (!in_array($ext, ['csv', 'txt'], true)) {
            return back()->withErrors(['csv_file' => 'File must be a CSV (.csv or .txt) file.']);
        }

        $path = $file->getRealPath();
        $handle = fopen($path, 'r');
        if (!$handle) {
            return back()->withErrors(['csv_file' => 'Could not read file.']);
        }

        $header = fgetcsv($handle);
        if (!$header) {
            fclose($handle);
            return back()->withErrors(['csv_file' => 'Empty CSV file.']);
        }

        // Handle BOM (byte order mark) in first header
        if (isset($header[0])) {
            $header[0] = preg_replace('/^\x{FEFF}/u', '', $header[0]);
        }

        $header = array_map(function ($h) {
            return strtolower(trim(str_replace([' ', '-'], '_', $h)));
        }, $header);

        $nameCol = $this->findColumn($header, ['product_name', 'name', 'item_name', 'product']);
        $priceCol = $this->findColumn($header, ['sell_price', 'price', 'unit_price', 'selling_price']);
        $costCol = $this->findColumn($header, ['cost_price', 'cost', 'buy_price', 'purchase_price']);
        $skuCol = $this->findColumn($header, ['sku', 'product_code', 'code', 'item_code']);
        $stockCol = $this->findColumn($header, ['stock_qty', 'stock', 'quantity', 'qty', 'on_hand']);

        if ($nameCol === null) {
            fclose($handle);
            return back()->withErrors(['csv_file' => 'CSV must have a "name" or "product_name" column.']);
        }

        $imported = 0;
        $skipped = 0;

        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) <= $nameCol) continue;

            $name = trim($row[$nameCol] ?? '');
            if ($name === '') { $skipped++; continue; }

            $price = $priceCol !== null ? (float) ($row[$priceCol] ?? 0) : 0;
            $cost = $costCol !== null ? (float) ($row[$costCol] ?? 0) : 0;
            $sku = $skuCol !== null ? trim($row[$skuCol] ?? '') : null;
            $stock = $stockCol !== null ? (int) ($row[$stockCol] ?? 0) : 0;

            // Skip duplicate by name+company
            $check = $db->pdo()->prepare('SELECT product_id FROM products WHERE name = :name AND company_id = :cid LIMIT 1');
            $check->execute(['name' => $name, 'cid' => $companyId]);
            if ($check->fetch()) { $skipped++; continue; }

            try {
                $db->pdo()->prepare(
                    'INSERT INTO products (company_id, name, price, sku, stock_qty, created_at, updated_at)
                     VALUES (:cid, :name, :price, :sku, :stock, NOW(), NOW())'
                )->execute([
                    'cid' => $companyId,
                    'name' => $name,
                    'price' => $price,
                    'sku' => $sku ?: null,
                    'stock' => $stock,
                ]);
                $imported++;
            } catch (\Throwable $e) {
                $skipped++;
            }
        }

        fclose($handle);
        return redirect('/import')->with('success', "Imported {$imported} products. Skipped {$skipped} rows.");
    }

    private function findColumn(array $headers, array $candidates): ?int
    {
        foreach ($candidates as $candidate) {
            $idx = array_search($candidate, $headers, true);
            if ($idx !== false) return $idx;
        }
        return null;
    }
}
