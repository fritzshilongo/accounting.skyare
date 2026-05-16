@extends('layouts.app')

@section('title', 'Products')

@section('content')
<section class="hero-card">
    <div class="toolbar">
        <div>
            <h1 class="hero-title">Products & Services</h1>
            <p class="hero-copy">Keep your catalog clean, searchable, and finance-ready with stock and pricing visibility.</p>
        </div>
        <a href="/products/create" class="btn btn-primary">New Product</a>
    </div>
</section>

<section class="table-card">
    <form method="GET" action="/products" class="filter-bar" style="margin-bottom:18px;">
        <div>
            <label for="search">Search</label>
            <input id="search" name="search" value="{{ request('search') }}" placeholder="Name, SKU, description">
        </div>
        <div>
            <label for="status">Status</label>
            <select id="status" name="status">
                <option value="">All statuses</option>
                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
            </select>
        </div>
        <div style="display:flex; gap:10px; align-items:end;">
            <button type="submit" class="btn-primary">Apply</button>
            <a href="/products" class="btn btn-ghost">Reset</a>
        </div>
    </form>

    @if($products->count())
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>SKU</th>
                        <th>Type</th>
                        <th>Price</th>
                        <th>Stock</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($products as $product)
                        <tr>
                            <td>
                                <a href="/products/{{ $product->product_id }}" class="row-link">
                                    <div class="row-title">{{ $product->name }}</div>
                                    <div class="row-subtitle">{{ $product->description ?: 'No description' }}</div>
                                </a>
                            </td>
                            <td>{{ $product->sku ?: '—' }}</td>
                            <td><span class="badge {{ $product->type === 'service' ? 'amber' : 'navy' }}">{{ ucfirst($product->type) }}</span></td>
                            <td>${{ number_format($product->price, 2) }}</td>
                            <td>{{ number_format($product->stock_qty ?? 0, 2) }}</td>
                            <td><span class="badge {{ $product->is_active ? 'teal' : 'rose' }}">{{ $product->is_active ? 'Active' : 'Inactive' }}</span></td>
                            <td><a href="/products/{{ $product->product_id }}/edit" class="btn btn-sm btn-secondary">Edit</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="pagination-wrap">{{ $products->links() }}</div>
    @else
        <div class="empty-state">No products found.</div>
    @endif
</section>
@endsection