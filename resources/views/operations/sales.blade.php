@extends('layouts.app')

@section('title', 'Sales')

@section('content')
<div class="hero-card">
    <h1 class="hero-title">Sales Overview</h1>
    <p class="hero-copy">Monitor invoicing volume and recognized revenue with export-ready summaries.</p>
</div>

<div class="metric-grid">
    <div class="metric-card navy">
        <div class="metric-label">Total Invoices</div>
        <div class="metric-value">{{ $total_invoices ?? 0 }}</div>
        <div class="metric-meta">Issued documents across the selected period</div>
    </div>
    <div class="metric-card teal">
        <div class="metric-label">Total Sales</div>
        <div class="metric-value">${{ number_format($total_sales ?? 0, 2) }}</div>
        <div class="metric-meta">Gross sales value booked to invoices</div>
    </div>
</div>

<div class="card">
    <div class="toolbar-row">
        <h3 class="section-title">Sales Reports</h3>
        <div class="inline-actions">
            <a href="/sales/financial-statement" class="btn btn-secondary btn-sm">Financial Statement</a>
            <a href="/sales/export/csv" class="btn btn-ghost btn-sm">Export CSV</a>
            <a href="/sales/export/pdf" class="btn btn-ghost btn-sm">Export PDF</a>
        </div>
    </div>
</div>
@endsection
