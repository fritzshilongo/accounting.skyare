<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Invoices</title>
    <link rel="stylesheet" href="/assets/css/legacy-style.css">
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>
<main class="card card-wide">
    <h1>Invoices</h1>
    <?php
    $exportQuery = http_build_query(array_filter([
        'q' => (string) ($search ?? ''),
        'status' => (string) ($status_filter ?? ''),
        'from' => (string) ($from_date ?? ''),
        'to' => (string) ($to_date ?? ''),
    ], static fn($v): bool => $v !== ''));
    $exportSuffix = $exportQuery !== '' ? ('?' . $exportQuery) : '';
    ?>
    <div class="button-group">
        <a class="btn btn-secondary" href="/invoices/export/csv<?= htmlspecialchars($exportSuffix, ENT_QUOTES, 'UTF-8') ?>">Export CSV</a>
        <a class="btn btn-secondary" href="/invoices/export/pdf<?= htmlspecialchars($exportSuffix, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener">Export PDF</a>
    </div>
        <a class="btn" style="background:#e74c3c;color:#fff;" href="/invoices/export/overdue<?= htmlspecialchars($exportSuffix, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener">Export Overdue</a>
    <p>Company: <?= htmlspecialchars($company['company_name'] ?? 'Unknown', ENT_QUOTES, 'UTF-8') ?></p>
    <form method="get" action="/invoices" style="display:flex;gap:10px;align-items:end;flex-wrap:wrap;margin:8px 0 14px;">
        <div>
            <label>Search Invoices</label>
            <input type="text" name="q" value="<?= htmlspecialchars($search ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="Invoice no, client, status, dates">
        </div>
        <div>
            <label>Status</label>
            <select name="status">
                <option value="">All</option>
                <?php foreach (['draft', 'issued', 'paid', 'overdue', 'cancelled'] as $statusOpt): ?>
                    <option value="<?= $statusOpt ?>" <?= (($status_filter ?? '') === $statusOpt) ? 'selected' : '' ?>><?= ucfirst($statusOpt) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label>From</label>
            <input type="date" name="from" value="<?= htmlspecialchars($from_date ?? '', ENT_QUOTES, 'UTF-8') ?>">
        </div>
        <div>
            <label>To</label>
            <input type="date" name="to" value="<?= htmlspecialchars($to_date ?? '', ENT_QUOTES, 'UTF-8') ?>">
        </div>
        <button type="submit" class="btn btn-primary">Search</button>
        <?php if (!empty($search) || !empty($status_filter) || !empty($from_date) || !empty($to_date)): ?><a class="btn btn-secondary" href="/invoices">Clear</a><?php endif; ?>
    </form>

    <?php foreach (($errors ?? []) as $error): ?>
        <p class="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endforeach; ?>

    <form method="post" action="/invoices" id="invoice_create_form">
        <input type="hidden" name="_token" value="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>">

        <label>Customer</label>
        <select name="customer_id" id="invoice_customer_id" onchange="fillInvoiceClient(this)" required>
            <option value="">Select customer</option>
            <?php foreach (($customers ?? []) as $c): ?>
                <option value="<?= (int) $c['customer_id'] ?>" data-name="<?= htmlspecialchars($c['customer_name'], ENT_QUOTES, 'UTF-8') ?>" <?= ((string) ($old['customer_id'] ?? '') === (string) $c['customer_id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($c['customer_name'], ENT_QUOTES, 'UTF-8') ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label>Client Name</label>
        <input name="client_name" id="invoice_client_name" value="<?= htmlspecialchars($old['client_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="Auto-filled from customer if left blank">

        <label>Product/Service (optional)</label>
        <select name="product_id" id="invoice_product_id" onchange="fillInvoiceAmountFromProduct()">
            <option value="">No product line item</option>
            <?php foreach (($products ?? []) as $p): ?>
                <option value="<?= (int) $p['product_id'] ?>"
                        data-price="<?= htmlspecialchars((string) ($p['unit_price'] ?? '0'), ENT_QUOTES, 'UTF-8') ?>"
                        data-description="<?= htmlspecialchars((string) ($p['description'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                        data-stock-qty="<?= htmlspecialchars((string) ($p['stock_qty'] ?? '0'), ENT_QUOTES, 'UTF-8') ?>"
                        data-stock-control-type="<?= htmlspecialchars((string) ($p['stock_control_type'] ?? 'STOCK_CONTROLLED'), ENT_QUOTES, 'UTF-8') ?>"
                        <?= ((string) ($old['product_id'] ?? '') === (string) $p['product_id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($p['product_name'], ENT_QUOTES, 'UTF-8') ?>
                </option>
            <?php endforeach; ?>
        </select>
        <p class="hint" id="invoice_product_description_hint">Select a product or service to preview the item description that will print on the invoice.</p>

        <label>Quantity (used when product selected)</label>
        <input name="quantity" id="invoice_quantity" type="number" min="1" step="1" value="<?= htmlspecialchars($old['quantity'] ?? '1', ENT_QUOTES, 'UTF-8') ?>">
        <p class="hint" id="invoice_inventory_hint">Select a product or service to view stock availability.</p>

        <label>Amount</label>
        <input name="amount" id="invoice_amount" type="number" min="0.01" step="0.01" value="<?= htmlspecialchars($old['amount'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
        <p class="hint">If a product is selected, amount is calculated from product unit price x quantity.</p>

        <label>Issue Date</label>
        <input name="issue_date" type="date" value="<?= htmlspecialchars($old['issue_date'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>

        <label>Due Date</label>
        <input name="due_date" type="date" value="<?= htmlspecialchars($old['due_date'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>

        <label>Status</label>
        <select name="status">
            <?php foreach (['draft', 'issued', 'paid', 'overdue', 'cancelled'] as $status): ?>
                <option value="<?= $status ?>" <?= (($old['status'] ?? 'issued') === $status) ? 'selected' : '' ?>><?= ucfirst($status) ?></option>
            <?php endforeach; ?>
        </select>

        <button type="submit">Create Invoice</button>
    </form>

    <table class="table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Client</th>
                <th>Amount</th>
                <th>Issue Date</th>
                <th>Due Date</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (($rows ?? []) === []): ?>
                <tr><td colspan="7">No invoices yet.</td></tr>
            <?php else: ?>
                <?php foreach ($rows as $row): ?>
                    <tr>
                        <td><?= (int) $row['invoice_id'] ?></td>
                        <td><?= htmlspecialchars($row['client_name'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= number_format((float) $row['amount'], 2) ?></td>
                        <td><?= htmlspecialchars($row['issue_date'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($row['due_date'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td>
                                <?= htmlspecialchars($row['status'], ENT_QUOTES, 'UTF-8') ?>
                                <?php if (!in_array($row['status'], ['paid','cancelled'], true) && !empty($row['due_date']) && $row['due_date'] < date('Y-m-d')): ?>
                                    <span style="background:#e74c3c;color:#fff;padding:2px 6px;border-radius:10px;font-size:0.75em;margin-left:4px;">OVERDUE</span>
                                <?php endif; ?>
                            </td>
                        <td>
                            <a href="/invoices/view?invoice_id=<?= (int) $row['invoice_id'] ?>">View</a>
                            |
                            <a href="/invoices/print?invoice_id=<?= (int) $row['invoice_id'] ?>" target="_blank" rel="noopener">Print</a>
                            |
                            <a href="/invoices/edit?invoice_id=<?= (int) $row['invoice_id'] ?>">Edit</a>
                            <?php if (!in_array((string) ($row['status'] ?? ''), ['paid', 'cancelled'], true)): ?>
                                |
                                <form method="post" action="/invoices/paid" class="inline-form" onsubmit="return confirm('Mark this invoice as paid?');">
                                    <input type="hidden" name="_token" value="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>">
                                    <input type="hidden" name="invoice_id" value="<?= (int) $row['invoice_id'] ?>">
                                    <button type="submit">Paid</button>
                                </form>
                            <?php elseif ((string) ($row['status'] ?? '') === 'paid'): ?>
                                |
                                <span style="color:#16a34a;">Paid</span>
                            <?php endif; ?>
                            <?php if (($row['status'] ?? '') !== 'cancelled'): ?>
                                |
                                <form method="post" action="/invoices/disable" class="inline-form" onsubmit="return confirm('Disable this invoice? It will remain in the financial records and be marked as cancelled.');">
                                    <input type="hidden" name="_token" value="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>">
                                    <input type="hidden" name="invoice_id" value="<?= (int) $row['invoice_id'] ?>">
                                    <button type="submit">Disable</button>
                                </form>
                            <?php else: ?>
                                |
                                <span style="color:#6b7280;">Disabled</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <p><a href="/dashboard">Back to dashboard</a></p>
</main>
<script>
function fillInvoiceClient(sel) {
    var opt = sel.options[sel.selectedIndex];
    var nameField = document.getElementById('invoice_client_name');
    if (opt && opt.dataset.name && nameField && nameField.value.trim() === '') {
        nameField.value = opt.dataset.name;
    }
}

function updateInvoiceInventoryHint() {
    var select = document.getElementById('invoice_product_id');
    var qtyField = document.getElementById('invoice_quantity');
    var hint = document.getElementById('invoice_inventory_hint');
    var submitButton = document.querySelector('#invoice_create_form button[type="submit"]');
    if (!select || !hint) return true;

    var opt = select.options[select.selectedIndex];
    if (!opt || opt.value === '') {
        hint.textContent = 'Select a product or service to view stock availability.';
        hint.style.color = '';
        if (submitButton) submitButton.disabled = false;
        return true;
    }

    var stockControlType = String(opt.dataset.stockControlType || 'STOCK_CONTROLLED');
    var availableQty = parseFloat(opt.dataset.stockQty || '0');
    var orderedQty = parseInt((qtyField && qtyField.value) ? qtyField.value : '0', 10);
    if (!isFinite(orderedQty) || orderedQty < 0) {
        orderedQty = 0;
    }

    if (stockControlType === 'STOCK_NOT_CONTROLLED') {
        hint.textContent = 'Service selected. No stock limit applies to the ordered quantity.';
        hint.style.color = '#1f6f43';
        if (submitButton) submitButton.disabled = false;
        return true;
    }

    var remainingQty = availableQty - orderedQty;
    if (orderedQty > availableQty) {
        hint.textContent = 'Available: ' + availableQty.toFixed(2) + ' | Ordered: ' + orderedQty.toFixed(2) + ' | Shortfall: ' + Math.abs(remainingQty).toFixed(2);
        hint.style.color = '#b42318';
        if (submitButton) submitButton.disabled = true;
        return false;
    }

    hint.textContent = 'Available: ' + availableQty.toFixed(2) + ' | Ordered: ' + orderedQty.toFixed(2) + ' | Remaining after invoice: ' + remainingQty.toFixed(2);
    hint.style.color = '#1f6f43';
    if (submitButton) submitButton.disabled = false;
    return true;
}

function fillInvoiceAmountFromProduct() {
    var select = document.getElementById('invoice_product_id');
    var amount = document.getElementById('invoice_amount');
    var qtyField = document.getElementById('invoice_quantity');
    var descriptionHint = document.getElementById('invoice_product_description_hint');
    if (!select || !amount) return;
    var opt = select.options[select.selectedIndex];
    if (opt && opt.dataset && opt.dataset.price && opt.value !== '') {
        var qty = parseInt((qtyField && qtyField.value) ? qtyField.value : '1', 10);
        if (!isFinite(qty) || qty <= 0) qty = 1;
        var unitPrice = parseFloat(opt.dataset.price || '0');
        amount.value = (unitPrice * qty).toFixed(2);
        if (descriptionHint) {
            descriptionHint.textContent = opt.dataset.description && opt.dataset.description.trim() !== ''
                ? opt.dataset.description
                : 'No item description saved for this product yet. Add one in Products to show scope/details on printed invoices.';
        }
        return;
    }

    if (select.value === '') {
        amount.value = '';
        if (descriptionHint) {
            descriptionHint.textContent = 'Select a product or service to preview the item description that will print on the invoice.';
        }
    }

    updateInvoiceInventoryHint();
}
var invoiceQtyField = document.getElementById('invoice_quantity');
if (invoiceQtyField) {
    invoiceQtyField.addEventListener('input', fillInvoiceAmountFromProduct);
}
var invoiceProductField = document.getElementById('invoice_product_id');
if (invoiceProductField) {
    invoiceProductField.addEventListener('change', fillInvoiceAmountFromProduct);
}
var invoiceCustomerField = document.getElementById('invoice_customer_id');
if (invoiceCustomerField) {
    invoiceCustomerField.addEventListener('change', function () {
        fillInvoiceClient(invoiceCustomerField);
    });
}
if (invoiceCustomerField && document.getElementById('invoice_client_name') && document.getElementById('invoice_client_name').value.trim() === '') {
    fillInvoiceClient(invoiceCustomerField);
}
var invoiceForm = document.getElementById('invoice_create_form');
if (invoiceForm) {
    invoiceForm.addEventListener('submit', function (event) {
        if (!updateInvoiceInventoryHint()) {
            event.preventDefault();
        }
    });
}
fillInvoiceAmountFromProduct();
</script>
</body>
</html>

