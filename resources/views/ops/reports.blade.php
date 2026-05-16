@extends('layouts.app')

@section('title', 'Reports')

@section('content')
<section class="module-page">
    <header class="module-header">
        <h1>Reports</h1>
        <p class="hint">Cross-module KPIs</p>
    </header>

    <div class="module-grid">
        <section class="module-card module-invoices">
            <i class="module-icon fas fa-file-invoice-dollar"></i>
            <h2>Invoices</h2>
            <p>{{ number_format((float) ($stats['invoices'] ?? 0), 0) }}</p>
        </section>
        <section class="module-card module-customers">
            <i class="module-icon fas fa-users"></i>
            <h2>Customers</h2>
            <p>{{ number_format((float) ($stats['customers'] ?? 0), 0) }}</p>
        </section>
        <section class="module-card module-products">
            <i class="module-icon fas fa-box-open"></i>
            <h2>Products</h2>
            <p>{{ number_format((float) ($stats['products'] ?? 0), 0) }}</p>
        </section>
        <section class="module-card module-expenses">
            <i class="module-icon fas fa-credit-card"></i>
            <h2>Expenses</h2>
            <p>N$ {{ number_format((float) ($stats['expenses'] ?? 0), 2) }}</p>
        </section>
        <section class="module-card module-sales">
            <i class="module-icon fas fa-chart-line"></i>
            <h2>Sales</h2>
            <p>N$ {{ number_format((float) ($stats['sales'] ?? 0), 2) }}</p>
        </section>
    </div>

    <div class="section-row">
        <a href="/dashboard" class="btn btn-secondary">Back to Dashboard</a>
    </div>
</section>
@endsection