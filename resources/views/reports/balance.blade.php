@extends('layouts.app')

@section('title', 'Balance Sheet')

@section('content')
<div class="hero-card">
    <h1 class="hero-title">Balance Sheet</h1>
    <p class="hero-copy">Snapshot of assets, liabilities, and equity as of a specific date.</p>
</div>

{{-- Date Filter --}}
<div class="card" style="margin-bottom: 24px;">
    <form method="GET" action="/reports/balance" class="form-grid three" style="align-items: end;">
        <div>
            <label>As Of Date</label>
            <input type="date" name="as_of" value="{{ $asOf }}">
        </div>
        <div>
            <button type="submit" class="btn btn-primary btn-sm">Update</button>
            <a href="/reports/balance/export/pdf?as_of={{ $asOf }}" class="btn btn-ghost btn-sm" style="margin-left:8px;">PDF</a>
        </div>
    </form>
</div>

{{-- Quick Summary --}}
<div class="metric-grid">
    <div class="metric-card teal">
        <div class="metric-label">Total Assets</div>
        <div class="metric-value">${{ number_format($balance['total_assets'], 2) }}</div>
    </div>
    <div class="metric-card rose">
        <div class="metric-label">Total Liabilities</div>
        <div class="metric-value">${{ number_format($balance['total_liabilities'], 2) }}</div>
    </div>
    <div class="metric-card {{ $balance['total_equity'] >= 0 ? 'navy' : 'rose' }}">
        <div class="metric-label">Total Equity</div>
        <div class="metric-value">${{ number_format($balance['total_equity'], 2) }}</div>
    </div>
</div>

{{-- Assets --}}
<div class="card">
    <h3 class="section-title" style="color: var(--teal);">Assets</h3>
    <div class="table-wrap" style="margin-top: 14px;">
        <table>
            <thead>
                <tr>
                    <th>Account</th>
                    <th style="text-align:right;">Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach($balance['assets'] as $label => $amount)
                    <tr>
                        <td>{{ $label }}</td>
                        <td style="text-align:right;">${{ number_format((float) $amount, 2) }}</td>
                    </tr>
                @endforeach
                <tr style="font-weight: 700; border-top: 2px solid var(--teal);">
                    <td>Total Assets</td>
                    <td style="text-align:right;">${{ number_format($balance['total_assets'], 2) }}</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

{{-- Liabilities --}}
<div class="card">
    <h3 class="section-title" style="color: var(--rose);">Liabilities</h3>
    <div class="table-wrap" style="margin-top: 14px;">
        <table>
            <thead>
                <tr>
                    <th>Account</th>
                    <th style="text-align:right;">Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach($balance['liabilities'] as $label => $amount)
                    <tr>
                        <td>{{ $label }}</td>
                        <td style="text-align:right;">${{ number_format((float) $amount, 2) }}</td>
                    </tr>
                @endforeach
                <tr style="font-weight: 700; border-top: 2px solid var(--rose);">
                    <td>Total Liabilities</td>
                    <td style="text-align:right;">${{ number_format($balance['total_liabilities'], 2) }}</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

{{-- Equity --}}
<div class="card">
    <h3 class="section-title" style="color: var(--navy);">Equity</h3>
    <div class="table-wrap" style="margin-top: 14px;">
        <table>
            <thead>
                <tr>
                    <th>Account</th>
                    <th style="text-align:right;">Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach($balance['equity'] as $label => $amount)
                    <tr>
                        <td>{{ $label }}</td>
                        <td style="text-align:right;">${{ number_format((float) $amount, 2) }}</td>
                    </tr>
                @endforeach
                <tr style="font-weight: 700; border-top: 2px solid var(--navy);">
                    <td>Total Equity</td>
                    <td style="text-align:right;">${{ number_format($balance['total_equity'], 2) }}</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

{{-- Journal Summary --}}
<div class="card">
    <h3 class="section-title">Journal Entries Summary</h3>
    <div class="metric-grid" style="margin-top: 14px;">
        <div class="metric-card navy" style="padding: 20px;">
            <div class="metric-label">Total Debits</div>
            <div class="metric-value">${{ number_format($balance['journal_debits'], 2) }}</div>
        </div>
        <div class="metric-card amber" style="padding: 20px;">
            <div class="metric-label">Total Credits</div>
            <div class="metric-value">${{ number_format($balance['journal_credits'], 2) }}</div>
        </div>
    </div>
</div>

{{-- Balance Check --}}
@php
    $balanceCheck = $balance['total_assets'] - $balance['total_liabilities'] - $balance['total_equity'];
@endphp
<div class="card" style="border-left: 4px solid {{ abs($balanceCheck) < 0.01 ? 'var(--teal)' : 'var(--amber)' }};">
    <h3 class="section-title">Balance Check</h3>
    <p style="margin-top: 10px;">
        Assets ({{ number_format($balance['total_assets'], 2) }}) − Liabilities ({{ number_format($balance['total_liabilities'], 2) }}) − Equity ({{ number_format($balance['total_equity'], 2) }})
        = <strong>${{ number_format($balanceCheck, 2) }}</strong>
        @if(abs($balanceCheck) < 0.01)
            <span class="badge teal">Balanced</span>
        @else
            <span class="badge amber">Variance</span>
        @endif
    </p>
</div>

<div style="margin-top: 16px;">
    <a href="/reports" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i> Back to Reports</a>
</div>
@endsection
