@extends('layouts.app')

@section('title', $customer['customer_name'] . ' — Credit Customer')

@section('content')
@php($currencySymbol = $_SESSION['user']['currency_symbol'] ?? 'N$')
<div class="hero-card">
    <h1 class="hero-title">{{ $customer['customer_name'] }}</h1>
    <p class="hero-copy">
        Customer profile, credit facilities, and payment history.
        <span class="badge {{ ($customer['status'] ?? 'active') === 'active' ? 'teal' : 'rose' }}" style="margin-left: 8px;">
            {{ ucfirst($customer['status'] ?? 'active') }}
        </span>
    </p>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

{{-- Portfolio Summary --}}
<div class="panel-grid">
    <div class="metric-card navy">
        <div class="metric-label">Credit Facilities</div>
        <div class="metric-value">{{ $summary['facility_count'] }}</div>
    </div>
    <div class="metric-card teal">
        <div class="metric-label">Total Issued</div>
        <div class="metric-value">{{ $currencySymbol }}{{ number_format($summary['total_issued'], 2) }}</div>
    </div>
    <div class="metric-card amber">
        <div class="metric-label">Total Paid</div>
        <div class="metric-value">{{ $currencySymbol }}{{ number_format($summary['total_paid'], 2) }}</div>
    </div>
    <div class="metric-card {{ $summary['total_outstanding'] > 0 ? 'rose' : '' }}">
        <div class="metric-label">Outstanding</div>
        <div class="metric-value">{{ $currencySymbol }}{{ number_format($summary['total_outstanding'], 2) }}</div>
    </div>
</div>

{{-- Customer Details --}}
<div class="card" style="margin-top: 16px;">
    <div class="toolbar-row">
        <h3 class="section-title">Customer Details</h3>
        <div class="inline-actions">
            <a href="/credit-customers/{{ $customer['customer_id'] }}/edit" class="btn btn-secondary btn-sm"><i class="fas fa-edit"></i> Edit</a>
            <form method="POST" action="/credit-customers/{{ $customer['customer_id'] }}" style="display: inline;" onsubmit="return confirm('Delete this customer? This cannot be undone.');">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger btn-sm"><i class="fas fa-trash"></i> Delete</button>
            </form>
        </div>
    </div>

    <div class="form-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px 24px; margin-top: 14px;">
        <div>
            <strong>Email:</strong><br>
            {{ $customer['email'] ?: '—' }}
        </div>
        <div>
            <strong>Phone:</strong><br>
            {{ $customer['phone'] ?: '—' }}
        </div>
        <div>
            <strong>Address:</strong><br>
            {{ $customer['address'] ?: '—' }}
        </div>
        <div>
            <strong>City:</strong><br>
            {{ $customer['city'] ?: '—' }}{{ $customer['province'] ? ', ' . $customer['province'] : '' }}
            {{ $customer['postal_code'] ?? '' }}
        </div>
        <div>
            <strong>Country:</strong><br>
            {{ $customer['country'] ?: '—' }}
        </div>
        <div>
            <strong>Tax Number:</strong><br>
            {{ $customer['tax_number'] ?: '—' }}
        </div>
        <div>
            <strong>ID Number:</strong><br>
            {{ $customer['id_number'] ?? '' ?: '—' }}
        </div>
        @if($customer['notes'])
            <div style="grid-column: 1 / -1;">
                <strong>Notes:</strong><br>
                {{ $customer['notes'] }}
            </div>
        @endif
    </div>
</div>

{{-- Credit Facilities --}}
<div class="card" style="margin-top: 16px;">
    <div class="toolbar-row">
        <h3 class="section-title">Credit Facilities ({{ count($credits) }})</h3>
        <a href="/credit-management" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Issue New Credit</a>
    </div>

    @if(count($credits) > 0)
        <div class="table-scroll" style="margin-top: 14px;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Credit #</th>
                        <th>Issue Date</th>
                        <th>Due Date</th>
                        <th style="text-align: right;">Principal</th>
                        <th style="text-align: right;">Interest</th>
                        <th style="text-align: right;">Total</th>
                        <th style="text-align: right;">Paid</th>
                        <th style="text-align: right;">Outstanding</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($credits as $credit)
                        @php
                            $outstanding = (float) $credit['total_amount'] - (float) $credit['amount_paid'];
                        @endphp
                        <tr>
                            <td><strong>{{ $credit['credit_no'] }}</strong></td>
                            <td>{{ $credit['issue_date'] }}</td>
                            <td>{{ $credit['due_date'] ?? '—' }}</td>
                            <td style="text-align: right;">{{ $currencySymbol }}{{ number_format((float) $credit['amount'], 2) }}</td>
                            <td style="text-align: right;">
                                {{ $currencySymbol }}{{ number_format((float) $credit['interest_amount'], 2) }}
                                <small style="color: var(--ink-muted);">({{ $credit['interest_percent'] }}% {{ $credit['interest_type'] }})</small>
                            </td>
                            <td style="text-align: right;">{{ $currencySymbol }}{{ number_format((float) $credit['total_amount'], 2) }}</td>
                            <td style="text-align: right;">{{ $currencySymbol }}{{ number_format((float) $credit['amount_paid'], 2) }}</td>
                            <td style="text-align: right;">
                                @if($outstanding > 0)
                                    <span style="color: var(--rose); font-weight: 600;">{{ $currencySymbol }}{{ number_format($outstanding, 2) }}</span>
                                @else
                                    {{ $currencySymbol }}0.00
                                @endif
                            </td>
                            <td>
                                @php
                                    $statusColors = ['ACTIVE' => 'teal', 'OVERDUE' => 'amber', 'PAID' => 'navy', 'BAD_DEBT' => 'rose'];
                                @endphp
                                <span class="badge {{ $statusColors[$credit['status']] ?? '' }}">{{ $credit['status'] }}</span>
                            </td>
                            <td class="inline-actions">
                                <a href="/credit-management/view?credit_id={{ $credit['credit_id'] }}" class="btn btn-secondary btn-sm">View</a>
                                <a href="/credit-management/agreement?credit_id={{ $credit['credit_id'] }}" class="btn btn-ghost btn-sm">Agreement</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="empty-state" style="margin-top: 16px;">
            <i class="fas fa-file-invoice-dollar" style="font-size: 28px; color: var(--ink-muted); margin-bottom: 8px;"></i>
            <p>No credit facilities issued to this customer yet.</p>
        </div>
    @endif
</div>

{{-- Payment History --}}
<div class="card" style="margin-top: 16px;">
    <h3 class="section-title">Payment History ({{ count($payments) }})</h3>

    @if(count($payments) > 0)
        <div class="table-scroll" style="margin-top: 14px;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Credit #</th>
                        <th style="text-align: right;">Amount</th>
                        <th>Method</th>
                        <th>Reference</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($payments as $payment)
                        <tr>
                            <td>{{ $payment['payment_date'] }}</td>
                            <td>{{ $payment['credit_no'] ?? '—' }}</td>
                            <td style="text-align: right; color: var(--teal); font-weight: 600;">{{ $currencySymbol }}{{ number_format((float) $payment['amount'], 2) }}</td>
                            <td>{{ ucfirst($payment['payment_method'] ?? '—') }}</td>
                            <td>{{ $payment['reference'] ?? '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <p style="color: var(--ink-muted); margin-top: 12px;">No payments recorded yet.</p>
    @endif
</div>

<div class="toolbar-row" style="margin-top: 16px;">
    <a href="/credit-customers" class="btn btn-ghost"><i class="fas fa-arrow-left"></i> Back to Customers</a>
</div>
@endsection
