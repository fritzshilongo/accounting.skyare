@extends('layouts.app')

@section('title', 'Edit Invoice ' . $invoice->invoice_no)

@section('content')
@php
    $currencySymbol = $_SESSION['user']['currency_symbol'] ?? 'N$';
@endphp
<section class="hero-card">
    <div class="toolbar">
        <div>
            <h1 class="hero-title">Edit {{ $invoice->invoice_no }}</h1>
            <p class="hero-copy">Update invoice details and line items before the invoice is paid.</p>
        </div>
        <a href="/invoices/{{ $invoice->invoice_id }}" class="btn btn-secondary">Back to invoice</a>
    </div>
</section>

<div class="card">
    @if(isset($invoiceDescriptionColumnExists) && !$invoiceDescriptionColumnExists)
        <div class="alert alert-warning" style="margin-bottom:18px; padding:16px; border:1px solid #f1c40f; background:#fef7dd; color:#864d05;">
            <strong>Database schema update needed:</strong> the invoice item description field is missing from the database schema.
            Until migrations are applied, line item descriptions may not persist correctly.
        </div>
    @endif
    @if($errors->any())
        <div class="alert alert-error" style="margin-bottom:18px;">
            <ul style="margin:0;padding-left:20px;">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="/invoices/{{ $invoice->invoice_id }}" style="margin-top:18px;">
        @csrf
        @method('PUT')

        <div class="form-grid two">
            <div>
                <label for="client_id">Client</label>
                <select id="client_id" name="client_id" required>
                    <option value="">Select Client</option>
                    @foreach($clients as $client)
                        <option value="{{ $client->client_id }}" {{ (int) old('client_id', $invoice->client_id) === (int) $client->client_id ? 'selected' : '' }}>{{ $client->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="status">Status</label>
                <select id="status" name="status" required>
                    @foreach(['draft', 'accepted', 'partial_paid', 'paid', 'finalised', 'cancelled'] as $status)
                        <option value="{{ $status }}" {{ strtolower((string) old('status', $invoice->status)) === $status ? 'selected' : '' }}>{{ ucwords(str_replace('_', ' ', $status)) }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="issue_date">Issue Date</label>
                <input type="date" id="issue_date" name="issue_date" value="{{ old('issue_date', $invoice->issue_date) }}" required>
            </div>

            <div>
                <label for="due_date">Due Date</label>
                <input type="date" id="due_date" name="due_date" value="{{ old('due_date', $invoice->due_date) }}" required>
            </div>
        </div>

        <section class="table-card" style="margin-top:24px;">
            <div style="display:flex;justify-content:space-between;align-items:center;">
                <h2 class="section-title">Line Items</h2>
                <button type="button" class="btn btn-secondary" id="add-line-item">Add line item</button>
            </div>
            <p class="hero-copy" style="margin-top:8px;">Add or update product/service rows for this invoice. Total will recalculate on save.</p>

            <div class="table-wrap" style="margin-top:18px;">
                <table class="table" id="invoice-items-table">
                    <thead>
                        <tr>
                            <th>Product / service</th>
                            <th>Description</th>
                            <th style="width:10%;">Qty</th>
                            <th style="width:15%;">Unit Price</th>
                            <th style="width:15%;">Line Total</th>
                            <th style="width:8%;">Remove</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($invoice->items as $item)
                            <tr>
                                <input type="hidden" name="item_id[]" value="{{ $item->invoice_item_id }}">
                                <input type="hidden" name="item_deleted[]" value="0">
                                <td>
                                    <select name="item_product_id[]" class="item-product-select" required>
                                        <option value="">Select item</option>
                                        @foreach($products as $product)
                                            <option value="{{ $product->product_id }}"
                                                    data-price="{{ number_format($product->price, 2, '.', '') }}"
                                                    data-description="{{ htmlspecialchars($product->description ?? '', ENT_QUOTES) }}"
                                                    {{ (int) old('item_product_id.' . $loop->index, $item->product_id) === (int) $product->product_id ? 'selected' : '' }}>
                                                {{ $product->name }} · {{ $currencySymbol }}{{ number_format($product->price, 2) }}
                                            </option>
                                        @endforeach
                                    </select>
                                </td>
                                <td>
                                    <input type="text" name="item_description[]" value="{{ old('item_description.' . $loop->index, $item->description) }}" maxlength="2000" placeholder="Item description">
                                </td>
                                <td>
                                    <input type="number" step="0.01" min="0.01" name="item_quantity[]" class="item-quantity" value="{{ old('item_quantity.' . $loop->index, $item->quantity) }}" required>
                                </td>
                                <td>
                                    <input type="number" step="0.01" min="0" name="item_unit_price[]" class="item-unit-price" value="{{ old('item_unit_price.' . $loop->index, $item->unit_price) }}" required>
                                </td>
                                <td class="line-total-cell">{{ $currencySymbol }}{{ number_format($item->line_total, 2) }}</td>
                                <td><button type="button" class="btn btn-ghost btn-sm" onclick="markRowDeleted(this)">Remove</button></td>
                            </tr>
                        @empty
                            <tr class="empty-row"><td colspan="6">No line items yet. Add one above.</td></tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="4" style="text-align:right;padding:12px;font-weight:700;">Invoice Total</td>
                            <td class="line-total-cell" id="invoice-grand-total">{{ $currencySymbol }}{{ number_format($invoice->total ?: $invoice->amount, 2) }}</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </section>

        <div class="toolbar-left" style="margin-top:24px;display:flex;gap:10px;flex-wrap:wrap;">
            <button type="submit" class="btn btn-primary">Save changes</button>
            <a href="/invoices/{{ $invoice->invoice_id }}" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>

@php
    $invoiceProducts = $products->map(function ($product) {
        return [
            'id' => $product->product_id,
            'name' => $product->name,
            'price' => (float) ($product->price ?? 0),
            'description' => $product->description ?? '',
        ];
    })->toArray();
@endphp

<script>
    const currencySymbol = @json($currencySymbol);
    const invoiceProducts = @json($invoiceProducts);

    function formatCurrency(value) {
        return currencySymbol + Number(value).toFixed(2);
    }

    function recalcInvoiceRow(row) {
        const qty = parseFloat(row.querySelector('.item-quantity')?.value || '0') || 0;
        const price = parseFloat(row.querySelector('.item-unit-price')?.value || '0') || 0;
        const total = qty * price;
        const totalCell = row.querySelector('.line-total-cell');
        if (totalCell) {
            totalCell.textContent = formatCurrency(total);
        }
        recalcGrandTotal();
    }

    function recalcGrandTotal() {
        let grandTotal = 0;
        document.querySelectorAll('#invoice-items-table tbody tr').forEach(function(row) {
            if (row.style.display === 'none') {
                return;
            }
            const qty = parseFloat(row.querySelector('.item-quantity')?.value || '0') || 0;
            const price = parseFloat(row.querySelector('.item-unit-price')?.value || '0') || 0;
            grandTotal += qty * price;
        });
        document.getElementById('invoice-grand-total').textContent = formatCurrency(grandTotal);
    }

    function addLineItemRow(data = {}) {
        const tbody = document.querySelector('#invoice-items-table tbody');
        const row = document.createElement('tr');
        row.innerHTML = `
            <input type="hidden" name="item_id[]" value="0">
            <input type="hidden" name="item_deleted[]" value="0">
            <td>
                <select name="item_product_id[]" class="item-product-select" required>
                    <option value="">Select item</option>
                    ${invoiceProducts.map(product => `
                        <option value="${product.id}" data-price="${product.price}" data-description="${product.description}">${product.name} · ${product.price.toFixed(2)}</option>
                    `).join('')}
                </select>
            </td>
            <td>
                <input type="text" name="item_description[]" value="${data.description ?? ''}" maxlength="2000" placeholder="Item description">
            </td>
            <td>
                <input type="number" step="0.01" min="0.01" name="item_quantity[]" class="item-quantity" value="${data.quantity ?? 1}" required>
            </td>
            <td>
                <input type="number" step="0.01" min="0" name="item_unit_price[]" class="item-unit-price" value="${data.unit_price ?? 0}" required>
            </td>
            <td class="line-total-cell">${formatCurrency((data.quantity ?? 1) * (data.unit_price ?? 0))}</td>
            <td><button type="button" class="btn btn-ghost btn-sm" onclick="markRowDeleted(this)">Remove</button></td>
        `;
        tbody.appendChild(row);
        bindRowEvents(row);
        const emptyRow = tbody.querySelector('.empty-row');
        if (emptyRow) {
            emptyRow.remove();
        }
        return row;
    }

    function bindRowEvents(row) {
        const productSelect = row.querySelector('.item-product-select');
        const qtyField = row.querySelector('.item-quantity');
        const priceField = row.querySelector('.item-unit-price');

        if (productSelect) {
            productSelect.addEventListener('change', function () {
                const selected = invoiceProducts.find(p => p.id === Number(this.value));
                if (selected) {
                    priceField.value = Number(selected.price).toFixed(2);
                    const descriptionField = row.querySelector('[name="item_description[]"]');
                    if (descriptionField && !descriptionField.value) {
                        descriptionField.value = selected.description || selected.name;
                    }
                }
                recalcInvoiceRow(row);
            });
        }

        if (qtyField) {
            qtyField.addEventListener('input', function () {
                recalcInvoiceRow(row);
            });
        }

        if (priceField) {
            priceField.addEventListener('input', function () {
                recalcInvoiceRow(row);
            });
        }
    }

    function markRowDeleted(button) {
        const row = button.closest('tr');
        if (!row) {
            return;
        }
        const deletedInput = row.querySelector('input[name="item_deleted[]"]');
        if (deletedInput) {
            deletedInput.value = '1';
        }
        row.style.display = 'none';
        recalcGrandTotal();
    }

    document.getElementById('add-line-item').addEventListener('click', function () {
        addLineItemRow({ quantity: 1, unit_price: 0, description: '' });
    });

    document.querySelectorAll('#invoice-items-table tbody tr').forEach(function (row) {
        bindRowEvents(row);
    });

    recalcGrandTotal();
</script>
@endsection
