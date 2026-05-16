@extends('layouts.app')

@section('title', 'Credit Management')

@section('content')
<section class="module-page">
    <header class="module-header">
        <i class="module-icon fas fa-hand-holding-usd"></i>
        <h1>Credit Management</h1>
        <p class="hint">Overdue debts, payments, and reconcilement</p>
    </header>

    <div class="section-row">
        <a href="/dashboard" class="btn btn-secondary">Back to Dashboard</a>
        <a href="/credit-management/export/csv" class="btn btn-success">Export CSV</a>
        <a href="/credit-management/export/pdf" class="btn btn-success">Export PDF</a>
    </div>

    <div class="module-grid" style="margin-bottom:20px;">
        <div class="module-card module-credit_management">
            <h3>Total Issued</h3>
            <p>N$ {{ number_format((float) ($summary['issued'] ?? 0), 2) }}</p>
        </div>
        <div class="module-card module-credit_management">
            <h3>Total Paid</h3>
            <p>N$ {{ number_format((float) ($summary['paid'] ?? 0), 2) }}</p>
        </div>
        <div class="module-card module-credit_management">
            <h3>Outstanding</h3>
            <p>N$ {{ number_format((float) ($summary['outstanding'] ?? 0), 2) }}</p>
        </div>
    </div>

    @include('partials.alerts')

    @yield('credit-table')
</section>
@endsection