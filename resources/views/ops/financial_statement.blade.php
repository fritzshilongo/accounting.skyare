@extends('layouts.app')

@section('title', 'Financial Statement')

@section('content')
<section class="module-page">
    <header class="module-header">
        <i class="module-icon fas fa-book"></i>
        <h1>Financial Statement</h1>
        <p class="hint">Year-to-date profit/loss and ledger details</p>
    </header>

    <div class="section-row">
        <a href="/sales" class="btn btn-secondary">Back to Sales</a>
        <a href="/sales/financial-statement/export/csv?from={{ $from_date }}&to={{ $to_date }}" class="btn btn-success">Export CSV</a>
        <a href="/sales/financial-statement/export/pdf?from={{ $from_date }}&to={{ $to_date }}" class="btn btn-success">Export PDF</a>
    </div>

    <div class="table-wrap"><table class="app-table"><thead><tr><th>Type</th><th>Total</th></tr></thead><tbody><tr><td>Income</td><td>N$ {{ number_format((float) ($total_income ?? 0), 2) }}</td></tr><tr><td>Expenses</td><td>N$ {{ number_format((float) ($total_expenses ?? 0), 2) }}</td></tr><tr><td>Net Income</td><td>N$ {{ number_format((float) ($net_income ?? 0), 2) }}</td></tr></tbody></table></div>
</section>
@endsection