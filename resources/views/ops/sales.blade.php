@extends('layouts.app')

@section('title', 'Sales')

@section('content')
<section class="module-page">
    <header class="module-header">
        <i class="module-icon fas fa-cart-arrow-down"></i>
        <h1>Sales</h1>
        <p class="hint">Invoice and revenue transactions</p>
    </header>

    <div class="section-row">
        <a href="/dashboard" class="btn btn-secondary">Back to Dashboard</a>
        <a href="/sales/export/csv" class="btn btn-success">Export CSV</a>
        <a href="/sales/export/pdf" class="btn btn-success">Export PDF</a>
    </div>

    <div class="module-grid" style="margin-bottom:20px;">
        <section class="module-card module-sales">
            <h3>Total Sales</h3>
            <p>N$ {{ number_format((float) ($summary['total_sales'] ?? 0), 2) }}</p>
        </section>
        <section class="module-card module-sales">
            <h3>Paid Sales</h3>
            <p>N$ {{ number_format((float) ($summary['paid_sales'] ?? 0), 2) }}</p>
        </section>
        <section class="module-card module-sales">
            <h3>Outstanding</h3>
            <p>N$ {{ number_format((float) ($summary['outstanding_sales'] ?? 0), 2) }}</p>
        </section>
    </div>

    @include('partials.alerts')

    <div class="table-wrap"><table class="app-table"><thead><tr><th>Invoice No</th><th>Client</th><th>Status</th><th>Amount</th><th>Issue Date</th><th>Due Date</th></tr></thead><tbody>@forelse($rows as $row)<tr><td>{{ $row['invoice_id'] }}</td><td>{{ $row['client_name'] }}</td><td>{{ ucfirst($row['status']) }}</td><td>N$ {{ number_format((float) $row['amount'], 2) }}</td><td>{{ $row['issue_date'] }}</td><td>{{ $row['due_date'] }}</td></tr>@empty<tr><td colspan="6">No rows found.</td></tr>@endforelse</tbody></table></div>
</section>
@endsection