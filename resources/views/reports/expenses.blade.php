@extends('layouts.app')

@section('title', 'Expense Report')

@section('content')
<div class="hero-card">
    <h1 class="hero-title">Expense Report</h1>
    <p class="hero-copy">Analyze operating costs by category, month, and individual transactions.</p>
</div>

{{-- Date Filter --}}
<div class="card" style="margin-bottom: 24px;">
    <form method="GET" action="/reports/expenses" class="form-grid three" style="align-items: end;">
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
            <a href="/reports/expenses/export/csv?from={{ $from }}&to={{ $to }}" class="btn btn-ghost btn-sm" style="margin-left:8px;">CSV</a>
            <a href="/reports/expenses/export/pdf?from={{ $from }}&to={{ $to }}" class="btn btn-ghost btn-sm">PDF</a>
        </div>
    </form>
</div>

{{-- Summary Metrics --}}
<div class="metric-grid">
    <div class="metric-card rose">
        <div class="metric-label">Total Expenses</div>
        <div class="metric-value">${{ number_format((float) ($summary['total'] ?? 0), 2) }}</div>
        <div class="metric-meta">{{ number_format((int) ($summary['count'] ?? 0)) }} transactions recorded</div>
    </div>
    <div class="metric-card amber">
        <div class="metric-label">Categories</div>
        <div class="metric-value">{{ count($byCategory) }}</div>
        <div class="metric-meta">Distinct expense categories</div>
    </div>
    @if(!empty($byCategory))
    <div class="metric-card navy">
        <div class="metric-label">Largest Category</div>
        <div class="metric-value">{{ $byCategory[0]['category'] ?? '-' }}</div>
        <div class="metric-meta">${{ number_format((float) ($byCategory[0]['total'] ?? 0), 2) }}</div>
    </div>
    @endif
</div>

{{-- By Category --}}
@if(!empty($byCategory))
<div class="card">
    <h3 class="section-title">Expenses by Category</h3>
    <div class="table-wrap" style="margin-top: 14px;">
        <table>
            <thead>
                <tr>
                    <th>Category</th>
                    <th>Transactions</th>
                    <th style="text-align:right;">Total</th>
                    <th style="text-align:right;">% of Total</th>
                </tr>
            </thead>
            <tbody>
                @php $grandTotal = (float) ($summary['total'] ?? 1); @endphp
                @foreach($byCategory as $cat)
                    @php $pct = $grandTotal > 0 ? round(((float) $cat['total'] / $grandTotal) * 100, 1) : 0; @endphp
                    <tr>
                        <td>{{ $cat['category'] }}</td>
                        <td>{{ $cat['count'] }}</td>
                        <td style="text-align:right;">${{ number_format((float) $cat['total'], 2) }}</td>
                        <td style="text-align:right;">
                            <div style="display:flex; align-items:center; justify-content:flex-end; gap:8px;">
                                <div style="width:60px; height:8px; background:#eee; border-radius:4px; overflow:hidden;">
                                    <div style="width:{{ $pct }}%; height:100%; background:var(--rose);"></div>
                                </div>
                                {{ $pct }}%
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

{{-- Monthly Trend --}}
@if(!empty($monthly))
<div class="card">
    <h3 class="section-title">Monthly Trend</h3>
    <div class="table-wrap" style="margin-top: 14px;">
        <table>
            <thead>
                <tr>
                    <th>Month</th>
                    <th style="text-align:right;">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($monthly as $m)
                    <tr>
                        <td>{{ $m['month'] }}</td>
                        <td style="text-align:right;">${{ number_format((float) $m['total'], 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

{{-- Detail --}}
<div class="card">
    <h3 class="section-title">Expense Detail</h3>
    @if(!empty($expenses))
        <div class="table-wrap" style="margin-top: 14px;">
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Category</th>
                        <th>Description</th>
                        <th style="text-align:right;">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($expenses as $exp)
                        <tr>
                            <td>{{ $exp['date'] ?? '-' }}</td>
                            <td>{{ $exp['category'] ?? '-' }}</td>
                            <td>{{ $exp['description'] ?? '-' }}</td>
                            <td style="text-align:right;">${{ number_format((float) ($exp['amount'] ?? 0), 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="empty-state" style="margin-top: 14px;">No expenses found for this period.</div>
    @endif
</div>

<div style="margin-top: 16px;">
    <a href="/reports" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i> Back to Reports</a>
</div>
@endsection
