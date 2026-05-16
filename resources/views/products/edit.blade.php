@extends('layouts.app')

@section('title', 'Edit Product')

@section('content')
<section class="hero-card">
    <h1 class="hero-title">Edit Product</h1>
    <p class="hero-copy">Maintain pricing, availability, and catalog status.</p>
</section>

<section class="form-card">
    <form method="POST" action="/products/{{ $product->product_id }}" class="form-grid two">
        @csrf
        @method('PUT')
        <div>
            <label for="name">Name</label>
            <input id="name" name="name" value="{{ old('name', $product->name) }}" required>
        </div>
        <div>
            <label for="sku">SKU</label>
            <input id="sku" name="sku" value="{{ old('sku', $product->sku) }}">
        </div>
        <div>
            <label for="type">Type</label>
            <select id="type" name="type" required>
                <option value="product" {{ $product->type === 'product' ? 'selected' : '' }}>Product</option>
                <option value="service" {{ $product->type === 'service' ? 'selected' : '' }}>Service</option>
            </select>
        </div>
        <div>
            <label for="status">Status</label>
            <select id="status" name="status" required>
                <option value="active" {{ $product->is_active ? 'selected' : '' }}>Active</option>
                <option value="inactive" {{ !$product->is_active ? 'selected' : '' }}>Inactive</option>
            </select>
        </div>
        <div>
            <label for="price">Price</label>
            <input id="price" type="number" step="0.01" name="price" value="{{ old('price', $product->price) }}" required>
        </div>
        <div>
            <label for="stock_qty">Current stock</label>
            <input id="stock_qty" type="number" step="0.01" min="0" name="stock_qty" value="{{ old('stock_qty', $product->stock_qty ?? 0) }}">
        </div>
        <div class="span-full">
            <label for="description">Description</label>
            <textarea id="description" name="description">{{ old('description', $product->description) }}</textarea>
        </div>
        <div class="toolbar-left span-full">
            <button type="submit" class="btn-primary">Save changes</button>
            <a href="/products" class="btn btn-secondary">Back</a>
        </div>
    </form>
</section>
@endsection