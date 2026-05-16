@extends('layouts.app')

@section('title', 'Inventory Audit')

@section('content')
<div class="hero-card">
    <h1 class="hero-title">Inventory Movements</h1>
    <p class="hero-copy">Chronological history of all stock-in and stock-out actions for compliance and reconciliation.</p>
</div>

<div class="card">
    @if(!empty($movements))
        <div class="table-wrap" style="margin-top:18px;">
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Product</th>
                        <th>Type</th>
                        <th>Quantity</th>
                        <th>Reference</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($movements as $move)
                        @php
                            $movementType = (string) ($move['movement_type'] ?? '-');
                            $outTypes = ['out', 'sold', 'sale_out', 'damaged', 'return_to_supplier', 'adjust_out'];
                            $inTypes = ['in', 'added', 'purchase', 'returned', 'adjust_in'];
                            $badge = in_array($movementType, $outTypes, true) ? 'rose' : (in_array($movementType, $inTypes, true) ? 'teal' : 'navy');
                            $movementLabel = ucwords(str_replace('_', ' ', $movementType));
                        @endphp
                        <tr>
                            <td>{{ $move['created_at'] ?? '-' }}</td>
                            <td>{{ $move['product_name'] ?? ('Product #' . ($move['product_id'] ?? '-')) }}</td>
                            <td>
                                <span class="badge {{ $badge }}">{{ $movementLabel }}</span>
                            </td>
                            <td>{{ $move['quantity'] ?? 0 }}</td>
                            <td>{{ $move['note'] ?? ($move['actor_name'] ?? '-') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="empty-state" style="margin-top:18px;">No movements recorded.</div>
    @endif
</div>

<div class="toolbar-row">
    <div class="inline-actions">
        <a href="/inventory/audit/export/csv" class="btn btn-ghost btn-sm">Export CSV</a>
        <a href="/inventory/audit/export/pdf" class="btn btn-ghost btn-sm">Export PDF</a>
    </div>
</div>
@endsection
