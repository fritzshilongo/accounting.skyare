@extends('layouts.app')

@section('title', 'Create Product')

@section('content')
<section class="hero-card">
    <h1 class="hero-title">Create Product or Service</h1>
    <p class="hero-copy">Add a billable item with pricing, SKU, and stock behavior.</p>
</section>

<section class="form-card">
    <form method="POST" action="/products" class="form-grid two">
        @csrf
        <div>
            <label for="name">Name</label>
            <input id="name" name="name" value="{{ old('name') }}" required>
        </div>
        <div>
            <label for="sku">SKU</label>
            <input id="sku" name="sku" value="{{ old('sku') }}">
        </div>
        <div>
            <label for="type">Type</label>
            <select id="type" name="type" required>
                <option value="product">Product</option>
                <option value="service">Service</option>
            </select>
        </div>
        <div>
            <label for="price">Price</label>
            <input id="price" type="number" step="0.01" name="price" value="{{ old('price') }}" required>
        </div>
        <div>
            <label for="stock_qty">Opening stock</label>
            <input id="stock_qty" type="number" step="0.01" min="0" name="stock_qty" value="{{ old('stock_qty', 0) }}">
        </div>
        <div class="span-full">
            <label for="description">Description</label>
            <textarea id="description" name="description">{{ old('description') }}</textarea>
        </div>
        <div class="toolbar-left span-full">
            <button type="submit" class="btn-primary">Create product</button>
            <a href="/products" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</section>
@endsection