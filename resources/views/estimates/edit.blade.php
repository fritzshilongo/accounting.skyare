@extends('layouts.app')

@section('title', 'Edit Estimate #' . $estimate->estimate_id)

@section('content')
<section class="hero-card">
    <h1 class="hero-title">Edit Estimate #{{ $estimate->estimate_id }}</h1>
    <p class="hero-copy">Adjust client, product, quantities, dates, and status.</p>
</section>

<section class="form-card">
    @if($errors->any())
        <div class="alert alert-error" style="margin-bottom:18px;">
            <ul style="margin:0;padding-left:20px;">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form id="estimate-form" method="POST" action="/estimates/{{ $estimate->estimate_id }}" class="form-grid two">
        @csrf
        @method('PUT')

        <div>
            <label for="client_id">Client</label>
            <select id="client_id" name="client_id" required>
                <option value="">Select client</option>
                @foreach($clients as $client)
                    <option value="{{ $client->client_id }}" {{ (int) old('client_id', $estimate->client_id) === (int) $client->client_id ? 'selected' : '' }}>
                        {{ $client->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <label for="estimate_date">Issue date</label>
            <input id="estimate_date" type="date" name="estimate_date"
                   value="{{ old('estimate_date', $estimate->estimate_date) }}" required>
        </div>

        <div>
            <label for="expiry_date">Expiry date</label>
            <input id="expiry_date" type="date" name="expiry_date"
                   value="{{ old('expiry_date', $estimate->expiry_date) }}" required>
        </div>

        <div>
            <label for="status">Status</label>
            <select id="status" name="status" required>
                @foreach(['draft', 'sent', 'accepted', 'declined'] as $s)
                    <option value="{{ $s }}" {{ old('status', $estimate->status) === $s ? 'selected' : '' }}>
                        {{ ucfirst($s) }}
                    </option>
                @endforeach
            </select>
        </div>
    </div>

    <section class="table-card" style="margin-top:24px;">
        <div style="display:flex;justify-content:space-between;align-items:center;">
            <h2 class="section-title">Line Items</h2>
            <button type="button" class="btn btn-secondary" id="add-line-item">Add line item</button>
        </div>
        <p class="hero-copy" style="margin-top:8px;">Add or update product/service rows for this estimate. Total will recalculate on save.</p>

        <div class="table-wrap" style="margin-top:18px;">
            <div id="estimate-line-item-error" class="alert alert-error" style="display:none;margin-bottom:16px;"></div>
            <table class="table" id="estimate-items-table">
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
                    @php
                        $estimateItems = old('item_product_id') ? [] : $estimate->items;
                    @endphp

                    @if(old('item_product_id'))
                        @foreach(old('item_product_id', []) as $index => $productId)
                            <tr>
                                <input type="hidden" name="item_id[]" value="{{ old('item_id.' . $index, 0) }}">
                                <input type="hidden" name="item_deleted[]" value="{{ old('item_deleted.' . $index, 0) }}">
                                <td>
                                    <select name="item_product_id[]" class="item-product-select" required>
                                        <option value="">Select item</option>
                                        @foreach($products as $product)
                                            <option value="{{ $product->product_id }}"
                                                    data-price="{{ number_format($product->price, 2, '.', '') }}"
                                                    data-description="{{ htmlspecialchars($product->description ?? '', ENT_QUOTES) }}"
                                                    {{ (int) old('item_product_id.' . $index, 0) === (int) $product->product_id ? 'selected' : '' }}>
                                                {{ $product->name }} · {{ number_format($product->price, 2) }}
                                            </option>
                                        @endforeach
                                    </select>
                                </td>
                                <td>
                                    <input type="text" name="item_description[]" value="{{ old('item_description.' . $index, '') }}" maxlength="2000" placeholder="Item description">
                                </td>
                                <td>
                                    <input type="number" step="0.01" min="0.01" name="item_quantity[]" class="item-quantity" value="{{ old('item_quantity.' . $index, 1) }}" required>
                                </td>
                                <td>
                                    <input type="number" step="0.01" min="0" name="item_price[]" class="item-price" value="{{ old('item_price.' . $index, 0) }}" required>
                                </td>
                                <td class="line-total-cell">{{ $_SESSION['user']['currency_symbol'] ?? 'N$' }}{{ number_format(((float) old('item_quantity.' . $index, 1)) * ((float) old('item_price.' . $index, 0)), 2) }}</td>
                                <td><button type="button" class="btn btn-ghost btn-sm" onclick="markRowDeleted(this)">Remove</button></td>
                            </tr>
                        @endforeach
                    @elseif($estimate->items->isNotEmpty())
                        @foreach($estimate->items as $item)
                            <tr>
                                <input type="hidden" name="item_id[]" value="{{ $item->estimate_item_id }}">
                                <input type="hidden" name="item_deleted[]" value="0">
                                <td>
                                    <select name="item_product_id[]" class="item-product-select" required>
                                        <option value="">Select item</option>
                                        @foreach($products as $product)
                                            <option value="{{ $product->product_id }}"
                                                    data-price="{{ number_format($product->price, 2, '.', '') }}"
                                                    data-description="{{ htmlspecialchars($product->description ?? '', ENT_QUOTES) }}"
                                                    {{ (int) old('item_product_id.' . $loop->index, $item->product_id) === (int) $product->product_id ? 'selected' : '' }}>
                                                {{ $product->name }} · {{ number_format($product->price, 2) }}
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
                                    <input type="number" step="0.01" min="0" name="item_price[]" class="item-price" value="{{ old('item_price.' . $loop->index, $item->price) }}" required>
                                </td>
                                <td class="line-total-cell">{{ $_SESSION['user']['currency_symbol'] ?? 'N$' }}{{ number_format((float) $item->quantity * (float) $item->price, 2) }}</td>
                                <td><button type="button" class="btn btn-ghost btn-sm" onclick="markRowDeleted(this)">Remove</button></td>
                            </tr>
                        @endforeach
                    @else
                        <tr>
                            <input type="hidden" name="item_id[]" value="0">
                            <input type="hidden" name="item_deleted[]" value="0">
                            <td>
                                <select name="item_product_id[]" class="item-product-select" required>
                                    <option value="">Select item</option>
                                    @foreach($products as $product)
                                        <option value="{{ $product->product_id }}"
                                                data-price="{{ number_format($product->price, 2, '.', '') }}"
                                                data-description="{{ htmlspecialchars($product->description ?? '', ENT_QUOTES) }}"
                                                {{ (int) old('product_id', $estimate->product_id) === (int) $product->product_id ? 'selected' : '' }}>
                                            {{ $product->name }} · {{ number_format($product->price, 2) }}
                                        </option>
                                    @endforeach
                                </select>
                            </td>
                            <td>
                                <input type="text" name="item_description[]" value="{{ old('item_description.0', '') }}" maxlength="2000" placeholder="Item description">
                            </td>
                            <td>
                                <input type="number" step="0.01" min="0.01" name="item_quantity[]" class="item-quantity" value="{{ old('quantity', $estimate->quantity ?? 1) }}" required>
                            </td>
                            <td>
                                <input type="number" step="0.01" min="0" name="item_price[]" class="item-price" value="{{ old('unit_price', $estimate->unit_price ?? $estimate->amount) }}" required>
                            </td>
                            <td class="line-total-cell">{{ $_SESSION['user']['currency_symbol'] ?? 'N$' }}{{ number_format((float) old('quantity', $estimate->quantity ?? 1) * (float) old('unit_price', $estimate->unit_price ?? $estimate->amount), 2) }}</td>
                            <td><button type="button" class="btn btn-ghost btn-sm" onclick="markRowDeleted(this)">Remove</button></td>
                        </tr>
                    @endif
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="4" style="text-align:right;padding:12px;font-weight:700;">Estimate Total</td>
                        <td class="line-total-cell" id="estimate-grand-total">{{ $_SESSION['user']['currency_symbol'] ?? 'N$' }}{{ number_format($estimate->total ?: $estimate->amount, 2) }}</td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </section>

    <div class="toolbar-left span-full">
        <button type="submit" class="btn-primary">Save changes</button>
        <a href="/estimates/{{ $estimate->estimate_id }}" class="btn btn-secondary">Cancel</a>
    </div>
    </form>
</section>

@php
    $estimateProducts = $products->map(function ($product) {
        return [
            'id' => $product->product_id,
            'name' => $product->name,
            'price' => (float) ($product->price ?? 0),
            'description' => $product->description ?? '',
        ];
    })->toArray();
@endphp

<script>
    const currencySymbol = @json($_SESSION['user']['currency_symbol'] ?? 'N$');
    const estimateProducts = @json($estimateProducts);

    function formatCurrency(value) {
        return currencySymbol + Number(value).toFixed(2);
    }

    function recalcEstimateRow(row) {
        const qty = parseFloat(row.querySelector('.item-quantity')?.value || '0') || 0;
        const price = parseFloat(row.querySelector('.item-price')?.value || '0') || 0;
        const total = qty * price;
        const totalCell = row.querySelector('.line-total-cell');
        if (totalCell) {
            totalCell.textContent = formatCurrency(total);
        }
        recalcEstimateTotal();
    }

    function recalcEstimateTotal() {
        let grandTotal = 0;
        document.querySelectorAll('#estimate-items-table tbody tr').forEach(function(row) {
            if (row.style.display === 'none') {
                return;
            }
            const qty = parseFloat(row.querySelector('.item-quantity')?.value || '0') || 0;
            const price = parseFloat(row.querySelector('.item-price')?.value || '0') || 0;
            grandTotal += qty * price;
        });
        document.getElementById('estimate-grand-total').textContent = formatCurrency(grandTotal);
    }

    function addLineItemRow(data = {}) {
        const tbody = document.querySelector('#estimate-items-table tbody');
        const row = document.createElement('tr');
        row.innerHTML = `
            <input type="hidden" name="item_id[]" value="0">
            <input type="hidden" name="item_deleted[]" value="0">
            <td>
                <select name="item_product_id[]" class="item-product-select" required>
                    <option value="">Select item</option>
                    ${estimateProducts.map(product => `
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
                <input type="number" step="0.01" min="0" name="item_price[]" class="item-price" value="${data.price ?? 0}" required>
            </td>
            <td class="line-total-cell">${formatCurrency((data.quantity ?? 1) * (data.price ?? 0))}</td>
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

    function applyProductDefaultsToRow(row) {
        const productSelect = row.querySelector('.item-product-select');
        const priceField = row.querySelector('.item-price');
        const descriptionField = row.querySelector('[name="item_description[]"]');

        if (!productSelect) {
            return;
        }

        const selected = estimateProducts.find(p => p.id === Number(productSelect.value));
        if (!selected) {
            return;
        }

        if (priceField && (priceField.value === '' || Number(priceField.value) === 0)) {
            priceField.value = Number(selected.price).toFixed(2);
        }

        if (descriptionField && !descriptionField.value) {
            descriptionField.value = selected.description || selected.name;
        }
    }

    function bindRowEvents(row) {
        const productSelect = row.querySelector('.item-product-select');
        const qtyField = row.querySelector('.item-quantity');
        const priceField = row.querySelector('.item-price');

        if (productSelect) {
            productSelect.addEventListener('change', function () {
                const selected = estimateProducts.find(p => p.id === Number(this.value));
                if (selected) {
                    if (priceField) {
                        priceField.value = Number(selected.price).toFixed(2);
                    }
                    const descriptionField = row.querySelector('[name="item_description[]"]');
                    if (descriptionField && !descriptionField.value) {
                        descriptionField.value = selected.description || selected.name;
                    }
                }
                recalcEstimateRow(row);
            });
        }

        if (qtyField) {
            qtyField.addEventListener('input', function () {
                recalcEstimateRow(row);
            });
        }

        if (priceField) {
            priceField.addEventListener('input', function () {
                recalcEstimateRow(row);
            });
        }

        applyProductDefaultsToRow(row);
    }

    function validateEstimateRows() {
        const errorBlock = document.getElementById('estimate-line-item-error');
        if (!errorBlock) {
            return true;
        }

        let valid = true;
        const messages = [];

        document.querySelectorAll('#estimate-items-table tbody tr').forEach(function (row) {
            if (row.style.display === 'none') {
                return;
            }
            const productSelect = row.querySelector('.item-product-select');
            const priceField = row.querySelector('.item-price');
            if (productSelect && productSelect.value && priceField) {
                const price = Number(priceField.value || '0');
                if (price <= 0) {
                    valid = false;
                    const itemName = productSelect.options[productSelect.selectedIndex]?.text || 'selected item';
                    messages.push(`${itemName} must have a unit price greater than zero.`);
                }
            }
        });

        if (!valid) {
            errorBlock.innerHTML = [...new Set(messages)].join('<br>');
            errorBlock.style.display = 'block';
            errorBlock.scrollIntoView({ behavior: 'smooth', block: 'center' });
        } else {
            errorBlock.style.display = 'none';
            errorBlock.innerHTML = '';
        }

        return valid;
    }

    const estimateForm = document.getElementById('estimate-form');
    if (estimateForm) {
        estimateForm.addEventListener('submit', function (event) {
            if (!validateEstimateRows()) {
                event.preventDefault();
            }
        });
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
        recalcEstimateTotal();
    }

    document.getElementById('add-line-item').addEventListener('click', function () {
        addLineItemRow({ quantity: 1, price: 0, description: '' });
    });

    document.querySelectorAll('#estimate-items-table tbody tr').forEach(function (row) {
        bindRowEvents(row);
    });

    recalcEstimateTotal();
</script>
@endsection
