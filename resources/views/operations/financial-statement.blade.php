@extends('layouts.app')

@section('title', 'Financial Statement')

@section('content')
<div class="hero-card">
    <h1 class="hero-title">Financial Statement</h1>
    <p class="hero-copy">A consolidated snapshot of revenue, costs, and margin performance for executive review.</p>
</div>

<div class="panel-grid">
    <div class="metric-card teal">
        <div class="metric-label">Gross Revenue</div>
        <div class="metric-value">${{ number_format($summary['gross_revenue'] ?? 0, 2) }}</div>
        <div class="metric-meta">Total invoiced before deductions</div>
    </div>
    <div class="metric-card amber">
        <div class="metric-label">Operating Expenses</div>
        <div class="metric-value">${{ number_format($summary['operating_expenses'] ?? 0, 2) }}</div>
        <div class="metric-meta">Tracked expenses from all categories</div>
    </div>
    <div class="metric-card navy">
        <div class="metric-label">Net Position</div>
        <div class="metric-value">${{ number_format($summary['net_position'] ?? 0, 2) }}</div>
        <div class="metric-meta">Revenue minus expenses and adjustments</div>
    </div>
</div>

<div class="card">
    <div class="toolbar-row">
        <h3 class="section-title">Statement Breakdown</h3>
        <div class="inline-actions">
            <a href="/sales/financial-statement/export/csv" class="btn btn-ghost btn-sm"><i class="fas fa-file-csv"></i> Export CSV</a>
            <a href="/sales/financial-statement/export/pdf" class="btn btn-ghost btn-sm"><i class="fas fa-file-pdf"></i> Export PDF</a>
        </div>
    </div>
    <table class="data-table">
        <thead>
            <tr>
                <th>Line Item</th>
                <th style="text-align: right;">Amount</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Gross Revenue (Invoiced)</td>
                <td style="text-align: right; color: var(--teal);">${{ number_format($summary['gross_revenue'] ?? 0, 2) }}</td>
            </tr>
            <tr>
                <td>Less: Operating Expenses</td>
                <td style="text-align: right; color: var(--rose);">(${{ number_format($summary['operating_expenses'] ?? 0, 2) }})</td>
            </tr>
            <tr style="font-weight: 700; border-top: 2px solid var(--line);">
                <td>Net Position</td>
                <td style="text-align: right;">${{ number_format($summary['net_position'] ?? 0, 2) }}</td>
            </tr>
        </tbody>
    </table>
</div>

<div style="margin-top: 16px;">
    <a href="/reports" class="btn btn-ghost btn-sm"><i class="fas fa-arrow-left"></i> Back to Reports</a>
    <a href="/sales" class="btn btn-ghost btn-sm"><i class="fas fa-chart-line"></i> Sales Overview</a>
</div>
@endsection
