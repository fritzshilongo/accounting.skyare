@extends('layouts.app')

@section('title', $product->name)

@section('content')
<section class="hero-card">
    <div class="toolbar">
        <div>
            <h1 class="hero-title">{{ $product->name }}</h1>
            <p class="hero-copy">{{ $product->description ?? 'No description provided.' }}</p>
        </div>
        <div class="toolbar-right">
            <span class="badge {{ $product->is_active ? 'teal' : 'rose' }}">
                {{ $product->is_active ? 'Active' : 'Inactive' }}
            </span>
            <a href="/products/{{ $product->product_id }}/edit" class="btn btn-primary">Edit product</a>
        </div>
    </div>
</section>

<div class="metric-grid">
    <div class="metric-card teal">
        <div class="metric-label">Price</div>
        <div class="metric-value">${{ number_format((float) $product->price, 2) }}</div>
        <div class="metric-meta">Unit price</div>
    </div>
    <div class="metric-card amber">
        <div class="metric-label">Stock on hand</div>
        <div class="metric-value">{{ number_format((float) ($product->stock_qty ?? 0), 2) }}</div>
        <div class="metric-meta">{{ $product->stock_control_type === 'NON_STOCK' ? 'Service — not tracked' : 'Units in stock' }}</div>
    </div>
    <div class="metric-card navy">
        <div class="metric-label">Type</div>
        <div class="metric-value">{{ ucfirst($product->type ?? 'product') }}</div>
        <div class="metric-meta">{{ $product->stock_control_type === 'NON_STOCK' ? 'Non-stock' : 'Stock controlled' }}</div>
    </div>
</div>

<section class="form-card">
    <h2 class="section-title">Product Details</h2>
    <dl class="detail-list" style="margin-top:18px;">
        <dt>SKU</dt>
        <dd>{{ $product->sku ?? '—' }}</dd>
        <dt>Type</dt>
        <dd>{{ ucfirst($product->type ?? '—') }}</dd>
        <dt>Stock control</dt>
        <dd>{{ $product->stock_control_type ?? '—' }}</dd>
        <dt>Price</dt>
        <dd>${{ number_format((float) $product->price, 2) }}</dd>
        <dt>Stock qty</dt>
        <dd>{{ number_format((float) ($product->stock_qty ?? 0), 2) }}</dd>
        <dt>Status</dt>
        <dd>
            <span class="badge {{ $product->is_active ? 'teal' : 'rose' }}">
                {{ $product->is_active ? 'Active' : 'Inactive' }}
            </span>
        </dd>
    </dl>

    <div class="toolbar-left" style="margin-top:24px;">
        <a href="/products/{{ $product->product_id }}/edit" class="btn btn-primary">Edit</a>
        <a href="/products" class="btn btn-secondary">Back to products</a>
        <form method="POST" action="/products/{{ $product->product_id }}" style="display:inline;"
              onsubmit="return confirm('Disable this product?')">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-ghost" style="color:var(--rose);">Disable</button>
        </form>
    </div>
</section>
@endsection
