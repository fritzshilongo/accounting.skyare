<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Skyare Trading CC') }} Dashboard</title>
    <link rel="stylesheet" href="/css/app.css">
</head>
<body>
<h1>{{ config('app.name', 'Skyare Trading CC') }} Dashboard</h1>

<div class="dashboard-summary">
    <div class="card">
        <h3>Total revenue (month)</h3>
        <p>{{ number_format(($stats['total_revenue_month'] ?? 0), 2) }}</p>
    </div>
    <div class="card">
        <h3>Total revenue (year)</h3>
        <p>{{ number_format(($stats['total_revenue_year'] ?? 0), 2) }}</p>
    </div>
    <div class="card">
        <h3>Payments received</h3>
        <p>{{ number_format(($stats['payments_received'] ?? 0), 2) }}</p>
    </div>
    <div class="card">
        <h3>Outstanding invoices</h3>
        <p>{{ number_format(($stats['outstanding_invoices'] ?? 0), 2) }}</p>
    </div>
    <div class="card">
        <h3>Overdue invoices</h3>
        <p>{{ $stats['overdue_invoices'] ?? 0 }}</p>
    </div>
    <div class="card">
        <h3>Active clients</h3>
        <p>{{ $stats['active_clients'] ?? 0 }}</p>
    </div>
</div>

@if(!empty($isIssuerHost))
    <div class="hero-card" style="margin-bottom:20px;">
        <h1 class="hero-title">Tenant Management Dashboard</h1>
        <p class="hero-copy">This space is for managing tenant licenses, access, and audit trails. Use the sidebar to navigate licensing, tenant users, audit logs, and backups.</p>
    </div>
@else
    <div class="quick-actions">
        <a class="btn btn-primary" href="/invoices/create">Create invoice</a>
        <a class="btn btn-primary" href="/clients/create">Add client</a>
        <a class="btn btn-primary" href="/payments/create">Record payment</a>
    </div>

    <hr>

    <h2>Sales trend (last 14 days)</h2>
    <ul>
        @if(!empty($stats['sales_trend']))
            @foreach($stats['sales_trend'] as $row)
                <li>{{ $row['day'] }}: {{ number_format($row['value'], 2) }}</li>
            @endforeach
        @else
            <li>No sales data available.</li>
        @endif
    </ul>

    <hr>

    <h2>Modules</h2>

    <div class="module-grid">
        <div class="module-card module-customers">
            <h2>👥 Clients</h2>
            <p><a href="/clients">Manage clients</a></p>
        </div>

        <div class="module-card module-invoices">
            <h2>🧾 Invoices</h2>
            <p><a href="/invoices">View invoices</a></p>
        </div>

        <div class="module-card module-products">
            <h2>📦 Products / Services</h2>
            <p><a href="/products">Manage products</a></p>
        </div>

        <div class="module-card module-payments">
            <h2>💰 Payments</h2>
            <p><a href="/payments">Payments</a></p>
        </div>

        <div class="module-card module-estimates">
            <h2>📑 Estimates</h2>
            <p><a href="/estimates">Estimates</a></p>
        </div>

        <div class="module-card module-inventory">
            <h2>📊 Inventory</h2>
            <p><a href="/inventory">Inventory</a></p>
        </div>

        <div class="module-card module-audit_trail">
            <h2>🔍 Audit Logs</h2>
            <p><a href="/audit">Audit Trail</a></p>
        </div>

        <div class="module-card module-license">
            <h2>🔐 License</h2>
            <p><a href="/license-required">License</a></p>
        </div>
    </div>
@endif
</body>
</html>
