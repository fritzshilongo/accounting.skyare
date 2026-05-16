@extends('layouts.app')

@section('title', 'Sales Report')

@section('content')
<div class="hero-card">
    <h1 class="hero-title">Sales Report</h1>
    <p class="hero-copy">Comprehensive view of invoicing activity, client performance, and sales trends.</p>
</div>

{{-- Date Filter --}}
<div class="card" style="margin-bottom: 24px;">
    <form method="GET" action="/reports/sales" class="form-grid three" style="align-items: end;">
        <div>
            <label>From</label>
            <input type="date" name="from" value="{{ $from }}">
        </div>
        <div>
            <label>To</label>
            <input type="date" name="to" value="{{ $to }}">
        </div>
        <div>
            <button type="submit" class="btn btn-primary btn-sm">Filter</button>
            <a href="/reports/sales/export/csv?from={{ $from }}&to={{ $to }}" class="btn btn-ghost btn-sm" style="margin-left:8px;">CSV</a>
            <a href="/reports/sales/export/pdf?from={{ $from }}&to={{ $to }}" class="btn btn-ghost btn-sm">PDF</a>
        </div>
    </form>
</div>

{{-- Summary Metrics --}}
<div class="metric-grid">
    <div class="metric-card navy">
        <div class="metric-label">Total Invoices</div>
        <div class="metric-value">{{ number_format((int) ($summary['count'] ?? 0)) }}</div>
        <div class="metric-meta">Documents issued in period</div>
    </div>
    <div class="metric-card teal">
        <div class="metric-label">Total Sales</div>
        <div class="metric-value">${{ number_format((float) ($summary['total'] ?? 0), 2) }}</div>
        <div class="metric-meta">Gross invoiced amount</div>
    </div>
    <div class="metric-card amber">
        <div class="metric-label">Collected</div>
        <div class="metric-value">${{ number_format((float) ($summary['paid'] ?? 0), 2) }}</div>
        <div class="metric-meta">Paid invoices total</div>
    </div>
    <div class="metric-card rose">
        <div class="metric-label">Outstanding</div>
        <div class="metric-value">${{ number_format((float) ($summary['outstanding'] ?? 0), 2) }}</div>
        <div class="metric-meta">Unpaid invoice balance</div>
    </div>
</div>

{{-- Monthly Breakdown --}}
@if(!empty($monthly))
<div class="card">
    <h3 class="section-title">Monthly Breakdown</h3>
    <div class="table-wrap" style="margin-top: 14px;">
        <table>
            <thead>
                <tr>
                    <th>Month</th>
                    <th>Invoices</th>
                    <th style="text-align:right;">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($monthly as $m)
                    <tr>
                        <td>{{ $m['month'] }}</td>
                        <td>{{ $m['count'] }}</td>
                        <td style="text-align:right;">${{ number_format((float) $m['total'], 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

{{-- Top Clients --}}
@if(!empty($topClients))
<div class="card">
    <h3 class="section-title">Top Clients by Revenue</h3>
    <div class="table-wrap" style="margin-top: 14px;">
        <table>
            <thead>
                <tr>
                    <th>Client</th>
                    <th>Invoices</th>
                    <th style="text-align:right;">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($topClients as $c)
                    <tr>
                        <td>{{ $c['client_name'] }}</td>
                        <td>{{ $c['invoices'] }}</td>
                        <td style="text-align:right;">${{ number_format((float) $c['total'], 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

{{-- Recent Invoices --}}
<div class="card">
    <h3 class="section-title">Invoice Detail</h3>
    @if(!empty($invoices))
        <div class="table-wrap" style="margin-top: 14px;">
            <table>
                <thead>
                    <tr>
                        <th>Invoice #</th>
                        <th>Date</th>
                        <th>Client</th>
                        <th style="text-align:right;">Amount</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($invoices as $inv)
                        <tr>
                            <td>{{ $inv['invoice_no'] ?? '-' }}</td>
                            <td>{{ $inv['issue_date'] ?? '-' }}</td>
                            <td>{{ $inv['client_name'] ?? '-' }}</td>
                            <td style="text-align:right;">${{ number_format((float) ($inv['total'] ?? 0), 2) }}</td>
                            <td><span class="badge {{ ($inv['status'] ?? '') === 'paid' ? 'teal' : 'amber' }}">{{ ucfirst($inv['status'] ?? 'unknown') }}</span></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="empty-state" style="margin-top: 14px;">No invoices found for this period.</div>
    @endif
</div>

<div style="margin-top: 16px;">
    <a href="/reports" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i> Back to Reports</a>
</div>
@endsection
