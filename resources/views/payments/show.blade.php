@extends('layouts.app')

@section('title', 'Payment #' . $payment->payment_id)

@section('content')
<section class="hero-card">
    <div class="toolbar">
        <div>
            <h1 class="hero-title">Payment #{{ $payment->payment_id }}</h1>
            <p class="hero-copy">
                Applied to {{ $payment->invoice->invoice_no ?? 'Invoice #' . $payment->invoice_id }}
                · {{ $payment->payment_date }}
            </p>
        </div>
        <div class="toolbar-right">
            <span class="badge teal">{{ ucfirst(str_replace('_', ' ', $payment->method)) }}</span>
        </div>
    </div>
</section>

<div class="metric-grid">
    <div class="metric-card teal">
        <div class="metric-label">Amount collected</div>
        <div class="metric-value">${{ number_format((float) $payment->amount, 2) }}</div>
        <div class="metric-meta">Payment total</div>
    </div>
    <div class="metric-card navy">
        <div class="metric-label">Invoice total</div>
        <div class="metric-value">${{ number_format((float) ($payment->invoice->total ?? $payment->invoice->amount ?? 0), 2) }}</div>
        <div class="metric-meta">Full invoice value</div>
    </div>
    <div class="metric-card {{ ($payment->invoice->balance ?? 0) > 0 ? 'amber' : 'teal' }}">
        <div class="metric-label">Invoice balance</div>
        <div class="metric-value">${{ number_format((float) ($payment->invoice->balance ?? 0), 2) }}</div>
        <div class="metric-meta">{{ ($payment->invoice->balance ?? 0) > 0 ? 'Still outstanding' : 'Fully paid' }}</div>
    </div>
</div>

<div class="panel-grid">
    <section class="form-card">
        <h2 class="section-title">Payment Details</h2>
        <dl class="detail-list" style="margin-top:18px;">
            <dt>Payment ID</dt>
            <dd>#{{ $payment->payment_id }}</dd>
            <dt>Invoice</dt>
            <dd>
                @if($payment->invoice)
                    <a href="/invoices/{{ $payment->invoice_id }}">{{ $payment->invoice->invoice_no }}</a>
                @else
                    Invoice #{{ $payment->invoice_id }}
                @endif
            </dd>
            <dt>Client</dt>
            <dd>{{ $payment->invoice->client->name ?? $payment->invoice->client_name ?? '—' }}</dd>
            <dt>Amount</dt>
            <dd><strong>${{ number_format((float) $payment->amount, 2) }}</strong></dd>
            <dt>Date</dt>
            <dd>{{ $payment->payment_date }}</dd>
            <dt>Method</dt>
            <dd>{{ ucfirst(str_replace('_', ' ', $payment->method)) }}</dd>
        </dl>
    </section>

    <section class="form-card">
        <h2 class="section-title">Actions</h2>
        <div style="display:flex; flex-direction:column; gap:12px; margin-top:18px;">
            @if($payment->invoice)
                <a href="/invoices/{{ $payment->invoice_id }}" class="btn btn-primary">View invoice</a>
            @endif
            <a href="/payments" class="btn btn-secondary">Back to payments</a>
        </div>

        <p class="section-copy" style="margin-top:18px; font-size:12px; color:var(--muted);">
            Payments are immutable records. Contact your accountant to reverse a payment.
        </p>
    </section>
</div>
@endsection
