@extends('layouts.app')

@section('title', 'New Recurring Invoice - ' . ($company['company_name'] ?? 'Skyare'))

@section('content')
<div class="hero-card">
    <div class="toolbar">
        <div>
            <h1 class="hero-title">New Recurring Invoice</h1>
            <p class="hero-copy">Set up automated billing for a client on a schedule.</p>
        </div>
        <a href="/recurring-invoices" class="btn btn-secondary"><i class="fas fa-arrow-left" style="margin-right:6px;"></i>Back</a>
    </div>
</div>

<div class="form-card">
    <form method="POST" action="/recurring-invoices" id="recurringForm">
        <input type="hidden" name="_token" value="{{ \App\Middleware\CsrfMiddleware::token() }}">

        <div class="form-grid two" style="margin-bottom:24px;">
            <div>
                <label for="client_id">Client</label>
                <select id="client_id" name="client_id" required>
                    <option value="">Select a client</option>
                    @foreach($clients as $client)
                        <option value="{{ $client['client_id'] }}" {{ old('client_id') == $client['client_id'] ? 'selected' : '' }}>{{ $client['client_name'] }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="frequency">Frequency</label>
                <select id="frequency" name="frequency" required>
                    <option value="weekly" {{ old('frequency') === 'weekly' ? 'selected' : '' }}>Weekly</option>
                    <option value="biweekly" {{ old('frequency') === 'biweekly' ? 'selected' : '' }}>Bi-weekly</option>
                    <option value="monthly" {{ old('frequency', 'monthly') === 'monthly' ? 'selected' : '' }}>Monthly</option>
                    <option value="quarterly" {{ old('frequency') === 'quarterly' ? 'selected' : '' }}>Quarterly</option>
                    <option value="yearly" {{ old('frequency') === 'yearly' ? 'selected' : '' }}>Yearly</option>
                </select>
            </div>
            <div>
                <label for="start_date">Start Date</label>
                <input type="date" id="start_date" name="start_date" required value="{{ old('start_date', date('Y-m-d')) }}">
            </div>
            <div>
                <label for="end_date">End Date (optional)</label>
                <input type="date" id="end_date" name="end_date" value="{{ old('end_date') }}">
            </div>
            <div>
                <label for="tax_rate">Tax Rate (%)</label>
                <input type="number" id="tax_rate" name="tax_rate" step="0.01" min="0" max="100" value="{{ old('tax_rate', 0) }}">
            </div>
            <div>
                <label for="max_occurrences">Max Occurrences (optional)</label>
                <input type="number" id="max_occurrences" name="max_occurrences" min="1" value="{{ old('max_occurrences') }}" placeholder="Unlimited if blank">
            </div>
            <div class="span-full">
                <label for="description">Description / Notes</label>
                <textarea id="description" name="description" rows="2" placeholder="Optional description">{{ old('description') }}</textarea>
            </div>
        </div>

        <h3 class="section-title" style="margin-bottom:16px;"><i class="fas fa-list" style="color:var(--teal);margin-right:8px;"></i>Line Items</h3>
        <div id="itemsContainer">
            <div class="item-row" style="display:grid;grid-template-columns:220px 1fr 100px 120px 40px;gap:12px;margin-bottom:12px;align-items:end;">
                <div>
                    <label>Product / Service</label>
                    <select name="items[0][product_id]" class="js-item-product">
                        <option value="">Custom line item</option>
                        @foreach($products as $product)
                            <option value="{{ $product['product_id'] }}" data-name="{{ $product['product_name'] }}" data-price="{{ number_format((float) ($product['sell_price'] ?? 0), 2, '.', '') }}">
                                {{ $product['product_name'] }} · ${{ number_format((float) ($product['sell_price'] ?? 0), 2) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label>Description</label>
                    <input type="text" name="items[0][description]" required placeholder="Service or product description" class="js-item-description">
                </div>
                <div>
                    <label>Qty</label>
                    <input type="number" name="items[0][quantity]" required min="0.01" step="0.01" value="1">
                </div>
                <div>
                    <label>Unit Price</label>
                    <input type="number" name="items[0][unit_price]" required min="0" step="0.01" value="0" class="js-item-unit-price">
                </div>
                <div></div>
            </div>
        </div>
        <button type="button" id="addItemBtn" class="btn btn-ghost btn-sm" style="margin-bottom:24px;">
            <i class="fas fa-plus" style="margin-right:4px;"></i>Add Line
        </button>

        <div style="display:flex;gap:12px;">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save" style="margin-right:6px;"></i>Create Recurring Invoice</button>
            <a href="/recurring-invoices" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>

<script>
(function() {
    const productOptionsHtml = `
        <option value="">Custom line item</option>
        @foreach($products as $product)
            <option value="{{ $product['product_id'] }}" data-name="{{ $product['product_name'] }}" data-price="{{ number_format((float) ($product['sell_price'] ?? 0), 2, '.', '') }}">
                {{ $product['product_name'] }} · ${{ number_format((float) ($product['sell_price'] ?? 0), 2) }}
            </option>
        @endforeach
    `;

    function bindProductAutoFill(row) {
        const select = row.querySelector('.js-item-product');
        const description = row.querySelector('.js-item-description');
        const unitPrice = row.querySelector('.js-item-unit-price');

        if (!select || !description || !unitPrice) {
            return;
        }

        select.addEventListener('change', function() {
            const option = select.options[select.selectedIndex];
            const name = option ? (option.getAttribute('data-name') || '') : '';
            const price = option ? (option.getAttribute('data-price') || '') : '';

            if (name !== '') {
                description.value = name;
            }
            if (price !== '') {
                unitPrice.value = price;
            }
        });
    }

    bindProductAutoFill(document.querySelector('.item-row'));

    let itemIndex = 1;
    document.getElementById('addItemBtn').addEventListener('click', function() {
        const container = document.getElementById('itemsContainer');
        const row = document.createElement('div');
        row.className = 'item-row';
        row.style.cssText = 'display:grid;grid-template-columns:220px 1fr 100px 120px 40px;gap:12px;margin-bottom:12px;align-items:end;';
        row.innerHTML = '<div><select name="items[' + itemIndex + '][product_id]" class="js-item-product">' + productOptionsHtml + '</select></div>'
            + '<div><input type="text" name="items[' + itemIndex + '][description]" required placeholder="Description" class="js-item-description"></div>'
            + '<div><input type="number" name="items[' + itemIndex + '][quantity]" required min="0.01" step="0.01" value="1"></div>'
            + '<div><input type="number" name="items[' + itemIndex + '][unit_price]" required min="0" step="0.01" value="0" class="js-item-unit-price"></div>'
            + '<div><button type="button" onclick="this.closest(\'.item-row\').remove()" class="btn btn-danger btn-sm" style="padding:10px 12px;"><i class="fas fa-times"></i></button></div>';
        container.appendChild(row);
        bindProductAutoFill(row);
        itemIndex++;
    });
})();
</script>
@endsection
