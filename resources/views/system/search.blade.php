@extends('layouts.app')

@section('title', 'Global Search')

@section('content')
<section class="hero-card">
    <h1 class="hero-title">Global Search</h1>
    <p class="hero-copy">Find clients, invoices, and products from one unified search.</p>
</section>

<section class="table-card">
    <form method="GET" action="/search" class="filter-bar" style="margin-bottom:18px;">
        <div class="span-full">
            <label for="q">Search term</label>
            <input id="q" name="q" value="{{ $query }}" placeholder="Client name, invoice number, SKU, product name">
        </div>
        <div style="display:flex; gap:10px; align-items:end;">
            <button type="submit" class="btn-primary">Search</button>
            <a href="/search" class="btn btn-ghost">Clear</a>
        </div>
    </form>
</section>

@if($query !== '')
    <div class="panel-grid">
        <section class="table-card">
            <h2 class="section-title">Clients</h2>
            @if($clients->count())
                <div class="table-wrap" style="margin-top:14px;">
                    <table>
                        <thead>
                            <tr><th>Name</th><th>Email</th><th>Status</th><th>Action</th></tr>
                        </thead>
                        <tbody>
                            @foreach($clients as $client)
                                <tr>
                                    <td>{{ $client->name }}</td>
                                    <td>{{ $client->email ?: '-' }}</td>
                                    <td><span class="badge {{ $client->status === 'active' ? 'teal' : 'amber' }}">{{ ucfirst($client->status) }}</span></td>
                                    <td><a href="/clients/{{ $client->client_id }}" class="btn btn-sm btn-secondary">Open</a></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="empty-state" style="margin-top:14px;">No clients found.</div>
            @endif
        </section>

        <section class="table-card">
            <h2 class="section-title">Invoices</h2>
            @if($invoices->count())
                <div class="table-wrap" style="margin-top:14px;">
                    <table>
                        <thead>
                            <tr><th>Invoice</th><th>Client</th><th>Total</th><th>Action</th></tr>
                        </thead>
                        <tbody>
                            @foreach($invoices as $invoice)
                                <tr>
                                    <td>{{ $invoice->invoice_no }}</td>
                                    <td>{{ $invoice->client_name ?: '-' }}</td>
                                    <td>${{ number_format($invoice->total ?: $invoice->amount, 2) }}</td>
                                    <td><a href="/invoices/{{ $invoice->invoice_id }}" class="btn btn-sm btn-secondary">Open</a></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="empty-state" style="margin-top:14px;">No invoices found.</div>
            @endif
        </section>

        <section class="table-card">
            <h2 class="section-title">Products</h2>
            @if($products->count())
                <div class="table-wrap" style="margin-top:14px;">
                    <table>
                        <thead>
                            <tr><th>Product</th><th>SKU</th><th>Price</th><th>Action</th></tr>
                        </thead>
                        <tbody>
                            @foreach($products as $product)
                                <tr>
                                    <td>{{ $product->name }}</td>
                                    <td>{{ $product->sku ?: '-' }}</td>
                                    <td>${{ number_format($product->price, 2) }}</td>
                                    <td><a href="/products/{{ $product->product_id }}/edit" class="btn btn-sm btn-secondary">Open</a></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="empty-state" style="margin-top:14px;">No products found.</div>
            @endif
        </section>
    </div>
@endif
@endsection
