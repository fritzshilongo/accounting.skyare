@extends('layouts.app')

@section('title', 'Invoices')

@section('content')
@php
    $currencySymbol = $_SESSION['user']['currency_symbol'] ?? 'N$';
@endphp
<section class="hero-card">
    <div class="toolbar">
        <div>
            <h1 class="hero-title">Invoice Workspace</h1>
            <p class="hero-copy">Monitor billing, search invoice history, and act on outstanding receivables from one modern ledger view.</p>
        </div>
        <a href="/invoices/create" class="btn btn-primary">Create Invoice</a>
    </div>
</section>

<section class="table-card">
    <form method="GET" action="/invoices" class="filter-bar" style="margin-bottom:18px;">
        <div>
            <label for="search">Search</label>
            <input id="search" name="search" value="{{ $search }}" placeholder="Invoice no or client">
        </div>
        <div style="display:flex; gap:10px; align-items:end;">
            <button type="submit" class="btn-primary">Apply</button>
            <a href="/invoices" class="btn btn-ghost">Reset</a>
        </div>
    </form>

    @if($invoices->count())
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Invoice</th>
                        <th>Client</th>
                        <th>Issue Date</th>
                        <th>Due Date</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($invoices as $invoice)
                        <tr>
                            <td>
                                <div class="row-title">{{ $invoice->invoice_no }}</div>
                                <div class="row-subtitle">#{{ $invoice->invoice_id }}</div>
                            </td>
                            <td>{{ $invoice->client_name ?? 'Unknown client' }}</td>
                            <td>{{ $invoice->issue_date }}</td>
                            <td>{{ $invoice->due_date }}</td>
                            <td>{{ $currencySymbol }}{{ number_format($invoice->total ?: $invoice->amount, 2) }}</td>
                            <td>
                                @php
                                    // Use raw status to avoid invoking accessor-side DB queries on fragile schemas.
                                    $invStatus = strtolower((string) ($invoice->getRawOriginal('status') ?? 'draft'));
                                    if ($invStatus === 'partial') {
                                        $invStatus = 'partial_paid';
                                    }
                                    if ($invStatus === 'finalized') {
                                        $invStatus = 'finalised';
                                    }
                                    $invoiceLocked = in_array($invStatus, ['paid', 'finalised', 'cancelled'], true);
                                @endphp
                                <span class="badge {{ $invoiceLocked ? 'teal' : (in_array($invStatus, ['partial_paid'], true) ? 'amber' : 'navy') }}">
                                    {{ ucwords(str_replace('_', ' ', $invStatus)) }}
                                </span>
                                @if($invoiceLocked)
                                    <span class="badge navy" style="margin-left:4px;">Locked</span>
                                @endif
                            </td>
                            <td>
                                <div class="inline-actions">
                                    <a href="/invoices/{{ $invoice->invoice_id }}" class="btn btn-sm btn-secondary">Open</a>
                                    <a href="/invoices/{{ $invoice->invoice_id }}/pdf" class="btn btn-sm btn-ghost">PDF</a>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="pagination-wrap">{{ $invoices->links() }}</div>
    @else
        <div class="empty-state">No invoices found.</div>
    @endif
</section>
@endsection