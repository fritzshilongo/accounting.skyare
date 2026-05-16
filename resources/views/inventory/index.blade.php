@extends('layouts.app')

@section('title', 'Inventory')

@section('content')
<div class="hero-card">
    <h1 class="hero-title">Inventory Management</h1>
    <p class="hero-copy">Maintain stock levels and record movements with full traceability and export support.</p>
</div>

<div class="card">
    <div class="toolbar-row">
        <h3 class="section-title">Stock Levels</h3>
        <a href="/inventory/audit" class="btn btn-secondary btn-sm">View Audit Trail</a>
    </div>
    @if(!empty($inventory))
        <div class="table-wrap" style="margin-top:18px;">
            <table>
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>SKU</th>
                        <th>Quantity</th>
                        <th>Location</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($inventory as $item)
                        <tr>
                            <td>{{ $item['product_name'] ?? 'Unknown' }}</td>
                            <td>{{ $item['sku'] ?? '-' }}</td>
                            <td><span class="badge navy">{{ $item['quantity'] ?? 0 }}</span></td>
                            <td>{{ $item['location'] ?? '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="empty-state" style="margin-top:18px;">No inventory items.</div>
    @endif
</div>

<div class="card">
    <h3 class="section-title">Record Movement</h3>
    <form method="POST" action="/inventory/move">
        <input type="hidden" name="_token" value="{{ \App\Middleware\CsrfMiddleware::token() }}">
        <div class="form-grid three" style="margin-top:18px;">
            <div>
                <label>Product</label>
                <select name="product_id" required>
                    <option value="">Select Product</option>
                    @foreach($inventory as $item)
                        <option value="{{ $item['product_id'] }}">{{ $item['product_name'] }} ({{ $item['sku'] }})</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label>Quantity</label>
                <input type="number" name="quantity" step="0.01" min="0.01" placeholder="Quantity" required>
            </div>
            <div>
                <label>Movement Reason</label>
                <select name="movement_reason" required>
                    <option value="added">Added stock</option>
                    <option value="purchase">Purchase received</option>
                    <option value="returned">Customer return (in)</option>
                    <option value="adjust_in">Adjustment in</option>
                    <option value="sold">Sold (out)</option>
                    <option value="damaged">Damaged (out)</option>
                    <option value="return_to_supplier">Return to supplier (out)</option>
                    <option value="adjust_out">Adjustment out</option>
                </select>
            </div>
            <div class="span-full">
                <label>Description / Reference</label>
                <input type="text" name="description" maxlength="255" placeholder="e.g. Invoice INV-1042, damaged in transit, restock batch #21">
            </div>
            <div class="span-full">
                <button type="submit" class="btn btn-primary">Record</button>
            </div>
        </div>
    </form>
</div>

<div class="toolbar-row">
    <div class="inline-actions">
        <a href="/inventory/export/csv" class="btn btn-ghost btn-sm">Export CSV</a>
    </div>
</div>
@endsection
