@extends('layouts.app')

@section('title', 'General Ledger')

@section('content')
<div class="hero-card">
    <h1 class="hero-title">General Ledger</h1>
    <p class="hero-copy">Journal entries with account codes, debits, and credits for the selected period.</p>
</div>

<div class="card">
    <form method="GET" action="/sales/general-ledger" class="inline-actions" style="gap: 12px; flex-wrap: wrap;">
        <label style="font-weight: 600;">From
            <input type="date" name="from" value="{{ $from }}" class="form-control" style="display:inline-block;width:auto;">
        </label>
        <label style="font-weight: 600;">To
            <input type="date" name="to" value="{{ $to }}" class="form-control" style="display:inline-block;width:auto;">
        </label>
        <button type="submit" class="btn btn-secondary btn-sm">Filter</button>
    </form>
</div>

<div class="panel-grid" style="margin-top: 16px;">
    <div class="metric-card teal">
        <div class="metric-label">Total Debits</div>
        <div class="metric-value">${{ number_format($totals['debit'], 2) }}</div>
    </div>
    <div class="metric-card rose">
        <div class="metric-label">Total Credits</div>
        <div class="metric-value">${{ number_format($totals['credit'], 2) }}</div>
    </div>
    <div class="metric-card {{ abs($totals['debit'] - $totals['credit']) < 0.01 ? 'navy' : 'amber' }}">
        <div class="metric-label">Balance</div>
        <div class="metric-value">${{ number_format($totals['debit'] - $totals['credit'], 2) }}</div>
        <div class="metric-meta">{{ abs($totals['debit'] - $totals['credit']) < 0.01 ? 'Balanced' : 'Variance detected' }}</div>
    </div>
</div>

<div class="card" style="margin-top: 16px;">
    <div class="toolbar-row">
        <h3 class="section-title">Journal Entries ({{ count($entries) }})</h3>
        <div class="inline-actions">
            <a href="/sales/general-ledger/export/csv" class="btn btn-ghost btn-sm"><i class="fas fa-file-csv"></i> CSV</a>
            <a href="/sales/general-ledger/export/pdf" class="btn btn-ghost btn-sm"><i class="fas fa-file-pdf"></i> PDF</a>
        </div>
    </div>
    @if(count($entries) > 0)
        <div class="table-scroll">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Account</th>
                        <th>Reference</th>
                        <th>Description</th>
                        <th style="text-align: right;">Debit</th>
                        <th style="text-align: right;">Credit</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($entries as $entry)
                        <tr>
                            <td>{{ $entry['date'] }}</td>
                            <td><strong>{{ $entry['account_code'] ?? '-' }}</strong></td>
                            <td>{{ $entry['reference'] }}</td>
                            <td>{{ $entry['description'] }}</td>
                            <td style="text-align: right;">{{ number_format((float) $entry['debit_amount'], 2) }}</td>
                            <td style="text-align: right;">{{ number_format((float) $entry['credit_amount'], 2) }}</td>
                            <td><span class="badge badge-{{ $entry['status'] === 'posted' ? 'success' : 'warning' }}">{{ ucfirst($entry['status']) }}</span></td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr style="font-weight: 700; border-top: 2px solid var(--line);">
                        <td colspan="4">Totals</td>
                        <td style="text-align: right;">${{ number_format($totals['debit'], 2) }}</td>
                        <td style="text-align: right;">${{ number_format($totals['credit'], 2) }}</td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    @else
        <p class="section-copy" style="margin-top: 12px;">No journal entries found for the selected period.</p>
    @endif
</div>

<div style="margin-top: 16px;">
    <a href="/reports" class="btn btn-ghost btn-sm"><i class="fas fa-arrow-left"></i> Back to Reports</a>
    <a href="/journal-entries" class="btn btn-ghost btn-sm"><i class="fas fa-book"></i> Journal Entry Form</a>
</div>
@endsection
