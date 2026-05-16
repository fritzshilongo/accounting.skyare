@extends('layouts.app')

@section('title', 'Import Data - ' . ($company['company_name'] ?? 'Skyare'))

@section('content')
<div class="hero-card">
    <h1 class="hero-title">Import Data</h1>
    <p class="hero-copy">Bulk import clients and products from CSV files.</p>
</div>

<div class="panel-grid">
    {{-- Import Clients --}}
    <div class="form-card">
        <h3 class="section-title" style="margin-bottom:18px;"><i class="fas fa-users" style="color:var(--teal);margin-right:8px;"></i>Import Clients</h3>
        <form method="POST" action="/import/clients" enctype="multipart/form-data">
            <input type="hidden" name="_token" value="{{ \App\Middleware\CsrfMiddleware::token() }}">
            <p style="color:var(--muted);font-size:14px;margin-bottom:16px;">
                Upload a CSV with columns: <strong>name</strong> (required), email, phone, address.
            </p>
            <div style="margin-bottom:16px;">
                <label for="csv_clients">CSV File</label>
                <input type="file" id="csv_clients" name="csv_file" accept=".csv,.txt" required style="padding:12px;">
            </div>
            <button type="submit" class="btn btn-primary"><i class="fas fa-upload" style="margin-right:6px;"></i>Import Clients</button>
        </form>

        <div style="margin-top:20px;padding:16px;border-radius:14px;background:var(--teal-soft);">
            <div style="font-weight:700;font-size:13px;margin-bottom:8px;"><i class="fas fa-info-circle" style="margin-right:6px;"></i>Sample CSV Format</div>
            <code style="font-size:12px;white-space:pre;display:block;overflow-x:auto;">name,email,phone,address
Acme Corp,acme@example.com,555-0100,123 Main St
Widget Co,info@widget.co,555-0200,456 Oak Ave</code>
        </div>
    </div>

    {{-- Import Products --}}
    <div class="form-card">
        <h3 class="section-title" style="margin-bottom:18px;"><i class="fas fa-boxes-stacked" style="color:var(--amber);margin-right:8px;"></i>Import Products</h3>
        <form method="POST" action="/import/products" enctype="multipart/form-data">
            <input type="hidden" name="_token" value="{{ \App\Middleware\CsrfMiddleware::token() }}">
            <p style="color:var(--muted);font-size:14px;margin-bottom:16px;">
                Upload a CSV with columns: <strong>name</strong> (required), sell_price, cost_price, sku, stock_qty.
            </p>
            <div style="margin-bottom:16px;">
                <label for="csv_products">CSV File</label>
                <input type="file" id="csv_products" name="csv_file" accept=".csv,.txt" required style="padding:12px;">
            </div>
            <button type="submit" class="btn btn-accent"><i class="fas fa-upload" style="margin-right:6px;"></i>Import Products</button>
        </form>

        <div style="margin-top:20px;padding:16px;border-radius:14px;background:var(--amber-soft);">
            <div style="font-weight:700;font-size:13px;margin-bottom:8px;"><i class="fas fa-info-circle" style="margin-right:6px;"></i>Sample CSV Format</div>
            <code style="font-size:12px;white-space:pre;display:block;overflow-x:auto;">name,sell_price,cost_price,sku,stock_qty
Premium Widget,29.99,15.00,WDG-001,100
Basic Widget,14.99,8.00,WDG-002,250</code>
        </div>
    </div>
</div>

<div class="card">
    <h3 class="section-title" style="margin-bottom:12px;"><i class="fas fa-lightbulb" style="color:var(--amber);margin-right:8px;"></i>Import Tips</h3>
    <ul style="color:var(--muted);font-size:14px;line-height:1.8;padding-left:20px;">
        <li>First row must be column headers</li>
        <li>Column names are flexible — "name", "client_name", "company_name" all work for the name field</li>
        <li>Duplicate entries (same name + company) are automatically skipped</li>
        <li>Maximum file size: 2MB</li>
        <li>Supported formats: .csv, .txt (comma-separated)</li>
        <li>Empty rows are skipped automatically</li>
    </ul>
</div>
@endsection
