@extends('layouts.app')

@section('title', 'Credit View')

@section('content')
@php($currencySymbol = $_SESSION['user']['currency_symbol'] ?? 'N$')
<div class="hero-card">
    <h1 class="hero-title">Credit Facility {{ $credit['credit_no'] ?? '' }}</h1>
    <p class="hero-copy">Customer: {{ $credit['customer_name'] ?? '-' }} · issued {{ $credit['issue_date'] ?? '-' }} · due {{ $credit['due_date'] ?? '-' }}</p>
    @if(!empty($credit['customer_phone']) || !empty($credit['customer_email']) || !empty($credit['customer_address']) || !empty($credit['tax_number']) || !empty($credit['id_number']))
        <p class="hero-copy" style="margin-top:4px;">
            @if(!empty($credit['id_number']))ID Number: {{ $credit['id_number'] }} · @endif
            @if(!empty($credit['tax_number']))Tax/Reg: {{ $credit['tax_number'] }} · @endif
            @if(!empty($credit['customer_phone']))Phone: {{ $credit['customer_phone'] }} · @endif
            @if(!empty($credit['customer_email']))Email: {{ $credit['customer_email'] }} · @endif
            @if(!empty($credit['customer_address']))Address: {{ $credit['customer_address'] }}@endif
        </p>
    @endif
</div>

<div class="card">
    <div class="metric-grid">
        <div>
            <div class="metric-card navy">
                <div class="metric-label">Principal</div>
                <div class="metric-value">{{ $currencySymbol }}{{ number_format((float) ($credit['amount'] ?? 0), 2) }}</div>
            </div>
        </div>
        <div>
            <div class="metric-card amber">
                <div class="metric-label">Total With Interest</div>
                <div class="metric-value">{{ $currencySymbol }}{{ number_format((float) ($credit['total_amount'] ?? 0), 2) }}</div>
            </div>
        </div>
        <div>
            <div class="metric-card teal">
                <div class="metric-label">Amount Paid</div>
                <div class="metric-value">{{ $currencySymbol }}{{ number_format((float) ($credit['amount_paid'] ?? 0), 2) }}</div>
            </div>
        </div>
        <div>
            <div class="metric-card rose">
                <div class="metric-label">Outstanding</div>
                <div class="metric-value">{{ $currencySymbol }}{{ number_format((float) ($credit['outstanding'] ?? 0), 2) }}</div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="toolbar-row">
        <h3 class="section-title">Repayment History</h3>
        <div class="inline-actions">
            <span class="badge {{ ($credit['status'] ?? '') === 'PAID' ? 'teal' : 'amber' }}">{{ $credit['status'] ?? 'ACTIVE' }}</span>
            <a href="/credit-management/agreement?credit_id={{ $credit['credit_id'] }}" class="btn btn-secondary btn-sm">Agreement</a>
            <a href="/credit-management/agreement?credit_id={{ $credit['credit_id'] }}&download=1" class="btn btn-ghost btn-sm">PDF</a>
        </div>
    </div>

    @if(!empty($payments))
        @php($runningPaid = 0)
        @php($totalRepayable = (float) ($credit['total_amount'] ?? 0))
        <div class="table-wrap" style="margin-top:18px;">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Date</th>
                        <th>Amount Paid</th>
                        <th>Total Paid</th>
                        <th>Remaining Balance</th>
                        <th>Method</th>
                        <th>Reference</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($payments as $index => $payment)
                        @php($runningPaid += (float) ($payment['amount'] ?? 0))
                        @php($remainingBalance = max(0, $totalRepayable - $runningPaid))
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>{{ $payment['payment_date'] ?? '-' }}</td>
                            <td>{{ $currencySymbol }}{{ number_format((float) ($payment['amount'] ?? 0), 2) }}</td>
                            <td><strong>{{ $currencySymbol }}{{ number_format($runningPaid, 2) }}</strong></td>
                            <td style="color: {{ $remainingBalance > 0 ? 'var(--rose, #e74c3c)' : 'var(--teal, #27ae60)' }}; font-weight: 600;">
                                {{ $currencySymbol }}{{ number_format($remainingBalance, 2) }}
                            </td>
                            <td>{{ ucwords(str_replace('_', ' ', (string) ($payment['payment_method'] ?? '-'))) }}</td>
                            <td>{{ $payment['reference'] ?? '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="empty-state" style="margin-top:18px;">No payments posted yet for this facility.</div>
    @endif
</div>
@endsection
