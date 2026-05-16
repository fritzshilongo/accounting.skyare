@extends('layouts.app')

@section('title', 'Reports')

@section('content')
<div class="hero-card">
    <h1 class="hero-title">Reports Center</h1>
    <p class="hero-copy">Generate operational and financial reporting packages for leadership, tax, and audit teams.</p>
</div>

<div class="panel-grid">
    @foreach($reports as $report)
        <div class="card">
            <div class="toolbar-row">
                <h3 class="section-title"><i class="fas {{ $report['icon'] ?? 'fa-chart-column' }}" style="margin-right: 8px; color: var(--teal);"></i>{{ $report['name'] }}</h3>
                <a href="{{ $report['route'] }}" class="btn btn-secondary btn-sm">Open</a>
            </div>
            <p class="section-copy">{{ $report['desc'] ?? 'Launch this report module to view, filter, and export the latest accounting data.' }}</p>
        </div>
    @endforeach
</div>

<div class="card" style="margin-top: 24px;">
    <h3 class="section-title"><i class="fas fa-download" style="margin-right: 8px; color: var(--navy);"></i>Quick Exports</h3>
    <div class="inline-actions" style="margin-top: 14px; display: flex; flex-wrap: wrap; gap: 10px;">
        <a href="/sales/export/csv" class="btn btn-ghost btn-sm">Sales CSV</a>
        <a href="/sales/export/pdf" class="btn btn-ghost btn-sm">Sales PDF</a>
        <a href="/sales/general-ledger/export/csv" class="btn btn-ghost btn-sm">General Ledger CSV</a>
        <a href="/sales/general-ledger/export/pdf" class="btn btn-ghost btn-sm">General Ledger PDF</a>
        <a href="/credit-management/export/csv" class="btn btn-ghost btn-sm">Credits CSV</a>
        <a href="/credit-management/export/pdf" class="btn btn-ghost btn-sm">Credits PDF</a>
        <a href="/inventory/export/csv" class="btn btn-ghost btn-sm">Inventory CSV</a>
    </div>
</div>
@endsection
