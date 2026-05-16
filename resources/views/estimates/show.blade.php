@extends('layouts.app')

@section('title', 'Estimate #' . $estimate->estimate_id)

@section('content')
@php($currencySymbol = $_SESSION['user']['currency_symbol'] ?? 'N$')
<section class="hero-card">
    <div class="toolbar">
        <div>
            <h1 class="hero-title">Estimate #{{ $estimate->estimate_id }}</h1>
            <p class="hero-copy">{{ $estimate->client?->name ?? $estimate->client_name ?? 'Client #' . $estimate->customer_id }} · expires {{ $estimate->expiry_date }}</p>
        </div>
        <div class="toolbar-right">
            <span class="badge {{ $estimate->status === 'accepted' ? 'teal' : ($estimate->status === 'declined' ? 'rose' : 'amber') }}">
                {{ ucfirst($estimate->status) }}
            </span>
            <a href="/estimates/{{ $estimate->estimate_id }}/pdf" class="btn btn-secondary">Download PDF</a>
            @if($estimate->status !== 'accepted')
                <a href="/estimates/{{ $estimate->estimate_id }}/edit" class="btn btn-ghost">Edit</a>
                <a href="/estimates/{{ $estimate->estimate_id }}/convert" class="btn btn-primary">Convert to Invoice</a>
            @endif
        </div>
    </div>
</section>

<div class="metric-grid">
    <div class="metric-card teal">
        <div class="metric-label">Estimate total</div>
        <div class="metric-value">{{ $currencySymbol }}{{ number_format((float) ($estimate->total ?? $estimate->amount), 2) }}</div>
        <div class="metric-meta">Tax included</div>
    </div>
    <div class="metric-card amber">
        <div class="metric-label">Tax amount</div>
        <div class="metric-value">{{ $currencySymbol }}{{ number_format((float) ($estimate->tax_amount ?? 0), 2) }}</div>
        <div class="metric-meta">Applied at issue</div>
    </div>
    <div class="metric-card navy">
        <div class="metric-label">Quantity</div>
        <div class="metric-value">{{ number_format((float) ($estimate->quantity ?? 1), 2) }}</div>
        <div class="metric-meta">Units quoted</div>
    </div>
</div>

@if($estimate->items->isNotEmpty())
    <section class="form-card">
        <h2 class="section-title">Line Items</h2>
        <div class="table-wrap" style="margin-top:18px;">
            <table class="table">
                <thead>
                    <tr>
                        <th>Product / service</th>
                        <th>Description</th>
                        <th>Qty</th>
                        <th>Unit Price</th>
                        <th>Line Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($estimate->items as $item)
                        <tr>
                            <td>{{ $item->product?->name ?? 'Product #' . $item->product_id }}</td>
                            <td>{{ $item->description ?? '—' }}</td>
                            <td>{{ number_format((float) $item->quantity, 2) }}</td>
                            <td>{{ $currencySymbol }}{{ number_format((float) $item->price, 2) }}</td>
                            <td>{{ $currencySymbol }}{{ number_format((float) $item->price * (float) $item->quantity, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
@endif

<div class="panel-grid">
    <section class="form-card">
        <h2 class="section-title">Estimate Details</h2>
        <dl class="detail-list" style="margin-top:18px;">
            <dt>Client</dt>
            <dd>{{ $estimate->client?->name ?? $estimate->client_name ?? '—' }}</dd>
            <dt>Product / service</dt>
            <dd>{{ $estimate->product->name ?? 'Product #' . $estimate->product_id }}</dd>
            <dt>Issue date</dt>
            <dd>{{ $estimate->estimate_date ?? '—' }}</dd>
            <dt>Expiry date</dt>
            <dd>{{ $estimate->expiry_date ?? '—' }}</dd>
            <dt>Unit price</dt>
            <dd>{{ $currencySymbol }}{{ number_format((float) ($estimate->unit_price ?? $estimate->amount), 2) }}</dd>
            <dt>Quantity</dt>
            <dd>{{ number_format((float) ($estimate->quantity ?? 1), 2) }}</dd>
            <dt>Sub total</dt>
            <dd>{{ $currencySymbol }}{{ number_format((float) ($estimate->amount ?? 0), 2) }}</dd>
            <dt>Tax</dt>
            <dd>{{ $currencySymbol }}{{ number_format((float) ($estimate->tax_amount ?? 0), 2) }}</dd>
            <dt>Total</dt>
            <dd><strong>{{ $currencySymbol }}{{ number_format((float) ($estimate->total ?? $estimate->amount), 2) }}</strong></dd>
        </dl>
    </section>

    <section class="form-card">
        <h2 class="section-title">Actions</h2>
        <div style="display:flex; flex-direction:column; gap:12px; margin-top:18px;">
            <a href="/estimates/{{ $estimate->estimate_id }}/pdf" class="btn btn-secondary">Download PDF</a>
            @if($estimate->status !== 'accepted')
                <a href="/estimates/{{ $estimate->estimate_id }}/convert" class="btn btn-primary">Convert to Invoice</a>
                <a href="/estimates/{{ $estimate->estimate_id }}/edit" class="btn btn-ghost">Edit estimate</a>
                <form method="POST" action="/estimates/{{ $estimate->estimate_id }}" onsubmit="return confirm('Delete this estimate?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-ghost" style="color:var(--rose);width:100%;">Delete estimate</button>
                </form>
            @endif
            <a href="/estimates" class="btn btn-secondary">Back to estimates</a>
        </div>
    </section>
</div>
@endsection
