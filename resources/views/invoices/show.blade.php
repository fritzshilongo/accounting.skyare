@extends('layouts.app')

@section('title', 'Invoice ' . $invoice->invoice_no)

@section('content')
@php
    $currencySymbol = $_SESSION['user']['currency_symbol'] ?? 'N$';
    $invoiceStatus = strtolower((string) ($invoice->status ?? 'draft'));
    $invoiceStatusLabel = ucwords(str_replace('_', ' ', $invoiceStatus));
    $invoiceEditable = !in_array($invoiceStatus, ['paid', 'finalised', 'cancelled'], true);
@endphp
<section class="hero-card">
    <div class="toolbar">
        <div>
            <h1 class="hero-title">{{ $invoice->invoice_no }}</h1>
            <p class="hero-copy">{{ $invoice->client?->name ?? $invoice->client_name ?? 'Unknown client' }} · due {{ $invoice->due_date }}</p>
        </div>
        <div class="toolbar-right">
            <span class="badge {{ in_array($invoiceStatus, ['paid', 'finalised'], true) ? 'teal' : ($invoiceStatus === 'partial_paid' ? 'amber' : 'navy') }}">{{ $invoiceStatusLabel }}</span>
            @if($invoiceEditable)
                <a href="/invoices/{{ $invoice->invoice_id }}/edit" class="btn btn-secondary">Edit</a>
            @endif
            <a href="/invoices/{{ $invoice->invoice_id }}/pdf" class="btn btn-secondary">Download PDF</a>
            @if($invoice->status !== 'cancelled' && $invoiceEditable)
                <form method="POST" action="/invoices/{{ $invoice->invoice_id }}" style="display:inline;" onsubmit="return confirm('Cancel this invoice? This cannot be undone.')">
                    <input type="hidden" name="_token" value="{{ \App\Middleware\CsrfMiddleware::token() }}">
                    <input type="hidden" name="_method" value="DELETE">
                    <button type="submit" class="btn btn-ghost" style="color:var(--rose);border-color:var(--rose);">Cancel Invoice</button>
                </form>
            @endif
        </div>
    </div>
</section>

@if(isset($invoiceDescriptionColumnExists) && !$invoiceDescriptionColumnExists)
    <section class="alert alert-warning" style="margin:18px 0; padding:16px; border:1px solid #f1c40f; background:#fef7dd; color:#864d05;">
        <strong>Database schema update needed:</strong> the invoice item description column is not available in the database.
        Add line items will still work, but descriptions may not be stored properly until migrations are applied.
    </section>
@endif

<div class="metric-grid">
    <div class="metric-card teal">
        <div class="metric-label">Invoice total</div>
        <div class="metric-value">{{ $currencySymbol }}{{ number_format($invoice->total ?: $invoice->amount, 2) }}</div>
        <div class="metric-meta">Tax included</div>
    </div>
    <div class="metric-card amber">
        <div class="metric-label">Paid</div>
        <div class="metric-value">{{ $currencySymbol }}{{ number_format($invoice->paid_amount, 2) }}</div>
        <div class="metric-meta">Collected so far</div>
    </div>
    <div class="metric-card {{ $invoice->balance > 0 ? 'rose' : 'navy' }}">
        <div class="metric-label">Balance</div>
        <div class="metric-value">{{ $currencySymbol }}{{ number_format($invoice->balance, 2) }}</div>
        <div class="metric-meta">{{ $invoice->is_overdue ? 'Overdue balance' : 'Remaining amount' }}</div>
    </div>
</div>

<div class="panel-grid">
    @if($invoiceEditable)
        <section class="form-card">
            <h2 class="section-title">Add Line Item</h2>
            <form method="POST" action="/invoices/{{ $invoice->invoice_id }}/items" class="form-grid two" style="margin-top:18px;">
                @csrf
                <div>
                    <label for="product_id">Product / service</label>
                    <select id="product_id" name="product_id" required>
                        <option value="">Select item</option>
                        @foreach($products as $product)
                            <option value="{{ $product->product_id }}">{{ $product->name }} · {{ $currencySymbol }}{{ number_format($product->price, 2) }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="quantity">Quantity</label>
                    <input id="quantity" type="number" step="0.01" name="quantity" value="1" required>
                </div>
                <div class="toolbar-left span-full">
                    <button type="submit" class="btn-primary">Add item</button>
                </div>
            </form>
        </section>

        <section class="form-card">
            <h2 class="section-title">Update Status</h2>
            <form method="POST" action="/invoices/{{ $invoice->invoice_id }}/paid" class="form-grid" style="margin-top:18px;">
                @csrf
                <div>
                    <label for="status">Invoice status</label>
                    <select id="status" name="status">
                        @foreach(['draft', 'accepted', 'partial_paid', 'paid', 'finalised', 'cancelled'] as $status)
                            <option value="{{ $status }}" {{ strtolower((string) $invoice->status) === $status ? 'selected' : '' }}>{{ ucwords(str_replace('_', ' ', $status)) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="toolbar-left">
                    <button type="submit" class="btn-accent">Save status</button>
                </div>
            </form>
        </section>
    @else
        <section class="form-card">
            <h2 class="section-title">Invoice locked</h2>
            <p class="hero-copy">This invoice is paid, finalised, or cancelled and cannot accept additional line items.</p>
        </section>
    @endif
</div>

<section class="table-card">
    <h2 class="section-title">Invoice Lines</h2>
    @if($invoice->items->count())
        <div class="table-wrap" style="margin-top:18px;">
            <table>
                <thead>
                    <tr>
                        <th>Description</th>
                        <th>Qty</th>
                        <th>Unit Price</th>
                        <th>Line Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($invoice->items as $item)
                        <tr>
                            <td>{{ $item->description ?? $item->product?->name ?? 'Item' }}</td>
                            <td>{{ number_format($item->quantity, 2) }}</td>
                            <td>{{ $currencySymbol }}{{ number_format($item->unit_price, 2) }}</td>
                            <td>{{ $currencySymbol }}{{ number_format($item->line_total, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="empty-state" style="margin-top:18px;">No items added yet.</div>
    @endif
</section>

@include('partials.attachments', ['attachableType' => 'invoice', 'attachableId' => $invoice->invoice_id, 'attachments' => $attachments ?? []])
@endsection