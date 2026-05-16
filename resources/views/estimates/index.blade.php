@extends('layouts.app')

@section('title', 'Estimates')

@section('content')
@php($currencySymbol = $_SESSION['user']['currency_symbol'] ?? 'N$')
<section class="hero-card">
    <div class="toolbar">
        <div>
            <h1 class="hero-title">Estimates</h1>
            <p class="hero-copy">Prepare quotations quickly and convert approved work into invoices.</p>
        </div>
        <a href="/estimates/create" class="btn btn-primary">New Estimate</a>
    </div>
</section>

<div class="card">
    <form method="GET" action="/estimates" class="form-grid three" style="margin-top:12px;">
        <div>
            <input type="text" name="search" value="{{ $search ?? '' }}" placeholder="Search by client name…">
        </div>
        <div>
            <select name="status">
                <option value="">All Statuses</option>
                @foreach(['draft', 'sent', 'accepted', 'declined'] as $s)
                    <option value="{{ $s }}" {{ ($status ?? '') === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                @endforeach
            </select>
        </div>
        <div style="display:flex;gap:8px;align-items:flex-end;">
            <button type="submit" class="btn btn-primary btn-sm">Apply</button>
            <a href="/estimates" class="btn btn-ghost btn-sm">Reset</a>
        </div>
    </form>
</div>

<section class="table-card">
    @if($estimates->count())
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Client</th>
                        <th>Product</th>
                        <th>Estimate Date</th>
                        <th>Expiry</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($estimates as $estimate)
                        <tr>
                            <td><a href="/estimates/{{ $estimate->estimate_id }}" class="row-link">{{ $estimate->client?->name ?? $estimate->client_name ?? 'Client #' . $estimate->customer_id }}</a></td>
                            <td>{{ $estimate->product->name ?? 'Product #' . $estimate->product_id }}</td>
                            <td>{{ $estimate->estimate_date }}</td>
                            <td>{{ $estimate->expiry_date }}</td>
                            <td>{{ $currencySymbol }}{{ number_format($estimate->amount, 2) }}</td>
                            <td><span class="badge {{ $estimate->status === 'accepted' ? 'teal' : ($estimate->status === 'declined' ? 'rose' : 'amber') }}">{{ ucfirst($estimate->status) }}</span></td>
                            <td>
                                @if($estimate->status !== 'accepted')
                                    <a href="/estimates/{{ $estimate->estimate_id }}/convert" class="btn btn-sm btn-accent">Convert</a>
                                    <a href="/estimates/{{ $estimate->estimate_id }}/edit" class="btn btn-sm btn-ghost">Edit</a>
                                    <a href="/estimates/{{ $estimate->estimate_id }}/pdf" class="btn btn-sm btn-ghost">PDF</a>
                                @else
                                    <span class="badge navy">Converted</span>
                                    <a href="/estimates/{{ $estimate->estimate_id }}/pdf" class="btn btn-sm btn-ghost">PDF</a>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div style="margin-top:16px;">{{ $estimates->links() }}</div>
    @else
        <div class="empty-state">No estimates yet.</div>
    @endif
</section>
@endsection