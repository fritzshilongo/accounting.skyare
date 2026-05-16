@extends('layouts.app')

@section('title', 'Create Invoice')

@section('content')
@php
    $currencySymbol = $_SESSION['user']['currency_symbol'] ?? 'N$';
@endphp
<section class="hero-card">
    <h1 class="hero-title">Create Invoice</h1>
    <p class="hero-copy">Start a new billing document and then add invoice lines on the detail screen.</p>
</section>

<section class="form-card">
    @if(isset($invoiceDescriptionColumnExists) && !$invoiceDescriptionColumnExists)
        <div class="alert alert-warning" style="margin-bottom:18px;">
            <strong>Schema mismatch detected:</strong> the invoice line item description field is not available in the database.
            Invoice creation will still work, but line item descriptions may not be stored correctly until database migrations are applied.
        </div>
    @endif
    <form method="POST" action="/invoices" class="form-grid two">
        @csrf
        <div>
            <label for="client_id">Client</label>
            <select id="client_id" name="client_id" required>
                <option value="">Select client</option>
                @foreach($clients as $client)
                    <option value="{{ $client->client_id }}">{{ $client->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="issue_date">Issue date</label>
            <input id="issue_date" type="date" name="issue_date" value="{{ old('issue_date', now()->toDateString()) }}">
        </div>
        <div>
            <label for="due_date">Due date</label>
            <input id="due_date" type="date" name="due_date" value="{{ old('due_date', now()->addDays($defaultTerms ?? 7)->toDateString()) }}">
        </div>
        <div>
            <label for="product_id">Product / service (optional)</label>
            <select id="product_id" name="product_id">
                <option value="">Select item</option>
                @foreach($products as $product)
                    <option value="{{ $product->product_id }}" {{ (int) old('product_id', 0) === (int) $product->product_id ? 'selected' : '' }}>
                        {{ $product->name }} · {{ $currencySymbol }}{{ number_format((float) $product->price, 2) }}
                    </option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="quantity">Quantity (when item selected)</label>
            <input id="quantity" type="number" step="0.01" min="0.01" name="quantity" value="{{ old('quantity', 1) }}">
        </div>
        <div>
            <label>Default tax rate</label>
            <input value="{{ number_format((float) ($defaultTaxRate ?? 0), 2) }}% from settings" disabled>
        </div>
        <div>
            <label>Available catalog</label>
            <input value="{{ $products->count() }} products/services available" disabled>
        </div>
        <div class="toolbar-left span-full">
            <button type="submit" class="btn-primary">Create draft invoice</button>
            <a href="/invoices" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</section>
@endsection