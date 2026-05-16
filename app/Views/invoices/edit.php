<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Edit Invoice</title>
    <link rel="stylesheet" href="/assets/css/legacy-style.css">
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>
<main class="card">
    <h1>Edit Invoice #<?= (int) $invoice['invoice_id'] ?></h1>
    <p>Company: <?= htmlspecialchars($company['company_name'] ?? 'Unknown', ENT_QUOTES, 'UTF-8') ?></p>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Edit Invoice #<?= (int) $invoice['invoice_id'] ?></title>
    <link rel="stylesheet" href="/assets/css/legacy-style.css">
    <link rel="stylesheet" href="/assets/css/app.css">
    <style>
        .items-table { width:100%; border-collapse:collapse; margin-top:.5rem; }
        .items-table th, .items-table td { padding:6px 8px; border:1px solid #e5e7eb; font-size:.875rem; }
        .items-table th { background:#f3f4f6; font-weight:600; }
        .items-table input, .items-table select { width:100%; box-sizing:border-box; padding:4px 6px; border:1px solid #d1d5db; border-radius:4px; font-size:.875rem; }
        .section-heading { margin:1.5rem 0 .5rem; font-size:1rem; font-weight:600; color:#374151; border-bottom:1px solid #e5e7eb; padding-bottom:.25rem; }
        .hint-text { font-size:.8rem; color:#6b7280; margin-bottom:.5rem; }
        .line-total-cell { text-align:right; white-space:nowrap; font-weight:500; }
    </style>
</head>
<body>
<main class="card">
    <h1>Edit Invoice #<?= (int) $invoice['invoice_id'] ?></h1>
    <p>Company: <?= htmlspecialchars($company['company_name'] ?? 'Unknown', ENT_QUOTES, 'UTF-8') ?></p>

    <?php foreach (($errors ?? []) as $error): ?>
        <p class="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endforeach; ?>

    <form method="post" action="/invoices/update">
        <input type="hidden" name="_token" value="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>">
        <input type="hidden" name="invoice_id" value="<?= (int) $invoice['invoice_id'] ?>">

        <!-- ── Invoice header ─────────────────────────────────── -->
        <p class="section-heading">Invoice Details</p>

        <label>Client Name</label>
        <input name="client_name" value="<?= htmlspecialchars($invoice['client_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>

        <label>Issue Date</label>
        <input name="issue_date" type="date" value="<?= htmlspecialchars($invoice['issue_date'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>

        <label>Due Date</label>
        <input name="due_date" type="date" value="<?= htmlspecialchars($invoice['due_date'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>

        <label>Status</label>
        <select name="status">
            <?php foreach (['draft', 'issued', 'paid', 'overdue', 'cancelled'] as $s): ?>
                <option value="<?= $s ?>" <?= (($invoice['status'] ?? 'issued') === $s) ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
            <?php endforeach; ?>
        </select>

        <?php if (empty($items)): ?>
        <label>Amount</label>
        <input name="amount" type="number" min="0.01" step="0.01"
               value="<?= htmlspecialchars((string) ($invoice['amount'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required>
        <?php else: ?>
        <!-- Amount is recalculated from line items on save — show read-only -->
        <input type="hidden" name="amount" value="<?= htmlspecialchars((string) ($invoice['amount'] ?? '0'), ENT_QUOTES, 'UTF-8') ?>">
        <?php endif; ?>

        <!-- ── Line items ────────────────────────────────────── -->
        <?php if (!empty($items)): ?>
        <p class="section-heading">Line Items</p>
        <p class="hint-text">Correct the product, description, quantity, or unit price for each line item. The invoice total will be recalculated automatically on save.</p>

        <table class="items-table">
            <thead>
                <tr>
                    <th style="width:20%">Product</th>
                    <th>Description</th>
                    <th style="width:8%">Qty</th>
                    <th style="width:12%">Unit Price</th>
                    <th style="width:12%">Line Total</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($items as $item): ?>
                <?php
                $itemQty   = (float) ($item['quantity']   ?? 1);
                $itemPrice = (float) ($item['unit_price'] ?? 0);
                ?>
                <tr>
                    <input type="hidden" name="item_id[]" value="<?= (int) $item['item_id'] ?>">
                    <td>
                        <select name="item_product_id[]">
                            <option value="0">— None —</option>
                            <?php foreach (($products ?? []) as $p): ?>
                            <option value="<?= (int) $p['product_id'] ?>"
                                <?= ((int) ($item['product_id'] ?? 0) === (int) $p['product_id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($p['product_name'], ENT_QUOTES, 'UTF-8') ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                    <td>
                        <input type="text" name="item_description[]"
                               value="<?= htmlspecialchars($item['description'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                               placeholder="Item description">
                    </td>
                    <td>
                        <input type="number" name="item_quantity[]" min="0.01" step="0.01"
                               value="<?= $itemQty ?>" required
                               onchange="recalcRow(this)">
                    </td>
                    <td>
                        <input type="number" name="item_unit_price[]" min="0" step="0.01"
                               value="<?= $itemPrice ?>" required
                               onchange="recalcRow(this)">
                    </td>
                    <td class="line-total-cell" data-linetotal>
                        N$ <?= number_format($itemQty * $itemPrice, 2) ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="4" style="text-align:right;padding:6px 8px;font-weight:600;">Total</td>
                    <td class="line-total-cell" id="grand-total">
                        N$ <?= number_format(array_sum(array_map(static fn($i) => (float)($i['line_total'] ?? 0), $items)), 2) ?>
                    </td>
                </tr>
            </tfoot>
        </table>

        <script>
        function recalcRow(el) {
            var row   = el.closest('tr');
            var qty   = parseFloat(row.querySelector('[name="item_quantity[]"]').value) || 0;
            var price = parseFloat(row.querySelector('[name="item_unit_price[]"]').value) || 0;
            var total = qty * price;
            row.querySelector('[data-linetotal]').textContent = 'N$ ' + total.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
            var grandTotal = 0;
            document.querySelectorAll('[data-linetotal]').forEach(function(td) {
                grandTotal += parseFloat(td.textContent.replace(/[^0-9.]/g, '')) || 0;
            });
            document.getElementById('grand-total').textContent = 'N$ ' + grandTotal.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
        }
        </script>
        <?php endif; ?>

        <br>
        <button type="submit" class="btn-primary">Save Changes</button>
    </form>

    <p><a href="/invoices/view?invoice_id=<?= (int) $invoice['invoice_id'] ?>">Cancel</a></p>
</main>
</body>
</html>

