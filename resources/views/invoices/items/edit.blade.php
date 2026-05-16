@extends('layouts.app')

@section('title', 'Edit Invoice Item')

@section('content')
<section class="hero-card">
    <h1 class="hero-title">Edit Invoice Item</h1>
    <p class="hero-copy">Update the quantity or description for this line item.</p>
</section>

<section class="form-card">
    <form method="POST" action="/invoices/{{ $invoice->invoice_id }}/items/{{ $item->invoice_item_id }}">
        @csrf
        @method('PATCH')

        <div class="form-grid two" style="margin-top:18px;">
            <div>
                <label>Invoice</label>
                <input type="text" value="{{ $invoice->invoice_no }}" disabled />
            </div>
            <div>
                <label>Product / Service</label>
                <input type="text" value="{{ $item->product?->name ?? 'Item' }}" disabled />
            </div>
            <div class="span-full">
                <label for="description">Description</label>
                <input id="description" name="description" type="text" value="{{ old('description', $item->description ?? $item->product?->name ?? '') }}" />
            </div>
            <div>
                <label for="quantity">Quantity</label>
                <input id="quantity" name="quantity" type="number" step="0.01" min="0.01" value="{{ old('quantity', number_format($item->quantity, 2, '.', '')) }}" required />
            </div>
            <div class="span-full" style="display:flex;gap:10px;align-items:center;">
                <button type="submit" class="btn-primary">Save line item</button>
                <a href="/invoices/{{ $invoice->invoice_id }}" class="btn btn-secondary">Back to invoice</a>
            </div>
        </div>
    </form>
</section>
@endsection
