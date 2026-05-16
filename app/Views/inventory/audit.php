<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Stock Audit &amp; History</title>
    <link rel="stylesheet" href="/assets/css/legacy-style.css">
    <link rel="stylesheet" href="/assets/css/app.css">
    <style>
        .movement-badge { display:inline-block; padding:2px 10px; border-radius:12px; font-size:.8rem; font-weight:600; white-space:nowrap; }
        .badge-in         { background:#e8f5e9; color:#2e7d32; }
        .badge-out        { background:#fce4ec; color:#c62828; }
        .badge-sold       { background:#fff3e0; color:#e65100; }
        .badge-returned   { background:#e3f2fd; color:#1565c0; }
        .badge-destroyed  { background:#f3e5f5; color:#6a1b9a; }
        .badge-adjustment { background:#f5f5f5; color:#424242; }

        .stock-ok   { color:#2e7d32; font-weight:600; }
        .stock-low  { color:#e65100; font-weight:600; }
        .stock-out  { color:#c62828; font-weight:600; }
        .stock-nc   { color:#757575; }

        .qty-up   { color:#2e7d32; }
        .qty-down { color:#c62828; }

        .section-title {
            font-size:1.05rem; font-weight:700; margin:22px 0 8px;
            border-left:4px solid #667eea; padding-left:10px;
        }
    </style>
</head>
<body>
<main class="card card-wide">
    <h1>Stock Audit &amp; History</h1>
    <p>Company: <?= htmlspecialchars($company['company_name'] ?? 'Unknown', ENT_QUOTES, 'UTF-8') ?></p>
    <p class="hint">
        A real-time view of every stock event — goods received, sold, returned, destroyed, adjusted — across all
        products. Use the filters to isolate exactly what you need to investigate.
    </p>

    <div class="button-group" style="margin-bottom:12px;">
        <a class="btn btn-secondary" href="/inventory">&#8592; Record Movement</a>
        <?php
        // Build query string from current filters for export links
        $qs = http_build_query(array_filter([
            'product_id'    => $f_product_id    ?? 0,
            'movement_type' => $f_movement_type ?? '',
            'from'          => $f_from          ?? '',
            'to'            => $f_to            ?? '',
            'q'             => $f_search        ?? '',
        ]));
        ?>
        <a class="btn btn-secondary" href="/inventory/audit/export/csv<?= $qs !== '' ? '?' . htmlspecialchars($qs, ENT_QUOTES, 'UTF-8') : '' ?>">Export CSV</a>
        <a class="btn btn-secondary" href="/inventory/audit/export/pdf<?= $qs !== '' ? '?' . htmlspecialchars($qs, ENT_QUOTES, 'UTF-8') : '' ?>" target="_blank" rel="noopener">Export PDF</a>
    </div>

    <!-- ═══════════════════ STOCK SNAPSHOT SUMMARY ═══════════════════ -->
    <p class="section-title">Current Stock Levels</p>

    <div class="module-grid" style="margin-bottom:14px;">
        <section class="module-card">
            <h2>Total Products</h2>
            <p><?= (int) ($stock_summary['total_products'] ?? 0) ?></p>
        </section>
        <section class="module-card">
            <h2>Stock-Controlled</h2>
            <p><?= (int) ($stock_summary['stock_controlled'] ?? 0) ?></p>
        </section>
        <section class="module-card">
            <h2>Total Stock Value</h2>
            <p>N$ <?= number_format((float) ($stock_summary['total_value'] ?? 0), 2) ?></p>
        </section>
        <section class="module-card">
            <h2>Low Stock (&le;5)</h2>
            <p style="color:<?= (int) ($stock_summary['low_stock'] ?? 0) > 0 ? '#e65100' : '#2e7d32' ?>">
                <?= (int) ($stock_summary['low_stock'] ?? 0) ?>
            </p>
        </section>
        <section class="module-card">
            <h2>Out of Stock</h2>
            <p style="color:<?= (int) ($stock_summary['out_of_stock'] ?? 0) > 0 ? '#c62828' : '#2e7d32' ?>">
                <?= (int) ($stock_summary['out_of_stock'] ?? 0) ?>
            </p>
        </section>
        <section class="module-card">
            <h2>Movements Shown</h2>
            <p><?= number_format((int) ($stock_summary['movement_count'] ?? 0)) ?></p>
        </section>
    </div>

    <!-- Product stock table -->
    <table class="table" style="margin-bottom:24px;">
        <thead>
            <tr>
                <th>SKU</th>
                <th>Product</th>
                <th>Control Type</th>
                <th>Current Stock</th>
                <th>Unit Price</th>
                <th>Stock Value</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($all_products)): ?>
            <tr><td colspan="7">No products found.</td></tr>
        <?php else: ?>
            <?php foreach ($all_products as $p):
                $qty          = (float) ($p['stock_qty']  ?? 0);
                $price        = (float) ($p['unit_price'] ?? 0);
                $isControlled = ((string) ($p['stock_control_type'] ?? '')) === 'STOCK_CONTROLLED';
                $value        = $isControlled ? $qty * $price : 0.0;

                if (!$isControlled) {
                    $statusLabel = 'Not Tracked';
                    $statusClass = 'stock-nc';
                } elseif ($qty <= 0) {
                    $statusLabel = 'Out of Stock';
                    $statusClass = 'stock-out';
                } elseif ($qty <= 5) {
                    $statusLabel = 'Low Stock';
                    $statusClass = 'stock-low';
                } else {
                    $statusLabel = 'In Stock';
                    $statusClass = 'stock-ok';
                }
            ?>
            <tr>
                <td><?= htmlspecialchars((string) ($p['sku'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars((string) $p['product_name'], ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= $isControlled ? 'Controlled' : 'Not Controlled' ?></td>
                <td><?= $isControlled ? number_format($qty, 2) : '—' ?></td>
                <td>N$ <?= number_format($price, 2) ?></td>
                <td><?= $isControlled ? 'N$ ' . number_format($value, 2) : '—' ?></td>
                <td><span class="<?= $statusClass ?>"><?= $statusLabel ?></span></td>
            </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>

    <!-- ═══════════════════ MOVEMENT HISTORY FILTERS ═══════════════════ -->
    <p class="section-title">Movement History</p>

    <form method="get" action="/inventory/audit" style="display:flex;gap:10px;align-items:end;flex-wrap:wrap;margin-bottom:14px;">
        <div>
            <label>Search</label>
            <input type="text" name="q"
                   value="<?= htmlspecialchars($f_search ?? '', ENT_QUOTES, 'UTF-8') ?>"
                   placeholder="product name, SKU, note, user">
        </div>
        <div>
            <label>Product</label>
            <select name="product_id">
                <option value="0">All products</option>
                <?php foreach (($all_products ?? []) as $p): ?>
                    <option value="<?= (int) $p['product_id'] ?>"
                        <?= (int) ($f_product_id ?? 0) === (int) $p['product_id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars((string) $p['product_name'], ENT_QUOTES, 'UTF-8') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label>Movement Type</label>
            <select name="movement_type">
                <option value="">All types</option>
                <?php
                $typeLabels = [
                    'in'         => 'Stock In (Goods Received)',
                    'out'        => 'Stock Out (Manual Removal)',
                    'sold'       => 'Sold',
                    'returned'   => 'Returned by Customer',
                    'destroyed'  => 'Destroyed / Written Off',
                    'adjustment' => 'Adjustment',
                ];
                foreach ($typeLabels as $key => $label):
                ?>
                    <option value="<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?>"
                        <?= ($f_movement_type ?? '') === $key ? 'selected' : '' ?>>
                        <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label>From Date</label>
            <input type="date" name="from" value="<?= htmlspecialchars($f_from ?? '', ENT_QUOTES, 'UTF-8') ?>">
        </div>
        <div>
            <label>To Date</label>
            <input type="date" name="to" value="<?= htmlspecialchars($f_to ?? '', ENT_QUOTES, 'UTF-8') ?>">
        </div>
        <button type="submit" class="btn btn-primary">Filter</button>
        <?php if (!empty($f_search) || !empty($f_movement_type) || ($f_product_id ?? 0) > 0 || !empty($f_from) || !empty($f_to)): ?>
            <a class="btn btn-secondary" href="/inventory/audit">Clear</a>
        <?php endif; ?>
    </form>

    <!-- Movement type breakdown chips -->
    <?php if (!empty($type_breakdown)): ?>
    <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:12px;">
        <?php foreach ($type_breakdown as $type => $count): ?>
            <a href="/inventory/audit?movement_type=<?= urlencode($type) ?>"
               style="text-decoration:none;">
                <span class="movement-badge badge-<?= htmlspecialchars($type, ENT_QUOTES, 'UTF-8') ?>">
                    <?= htmlspecialchars($typeLabels[$type] ?? ucfirst($type), ENT_QUOTES, 'UTF-8') ?>:
                    <?= (int) $count ?>
                </span>
            </a>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Movement history table -->
    <table class="table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Date &amp; Time</th>
                <th>Product</th>
                <th>SKU</th>
                <th>Type</th>
                <th>Qty Changed</th>
                <th>Stock Before</th>
                <th>Stock After</th>
                <th>Note</th>
                <th>By</th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($movements)): ?>
            <tr><td colspan="10">No movements match the current filters.</td></tr>
        <?php else: ?>
            <?php foreach ($movements as $row):
                $mType   = (string) ($row['movement_type'] ?? '');
                $isReduce = in_array($mType, ['out', 'sold', 'destroyed'], true);
                $qtyClass = $isReduce ? 'qty-down' : 'qty-up';
                $qtySign  = $isReduce ? '−' : '+';
            ?>
            <tr>
                <td><?= (int) $row['movement_id'] ?></td>
                <td><?= htmlspecialchars((string) $row['created_at'], ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars((string) $row['product_name'], ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars((string) ($row['sku'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></td>
                <td>
                    <span class="movement-badge badge-<?= htmlspecialchars($mType, ENT_QUOTES, 'UTF-8') ?>">
                        <?= htmlspecialchars($typeLabels[$mType] ?? ucfirst($mType), ENT_QUOTES, 'UTF-8') ?>
                    </span>
                </td>
                <td class="<?= $qtyClass ?>">
                    <?= $qtySign ?><?= number_format((float) $row['quantity'], 2) ?>
                </td>
                <td><?= number_format((float) ($row['qty_before'] ?? 0), 2) ?></td>
                <td><?= number_format((float) ($row['qty_after']  ?? 0), 2) ?></td>
                <td><?= htmlspecialchars((string) ($row['note'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars((string) ($row['actor_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
            </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>

    <p style="margin-top:14px;">
        <a href="/inventory">&#8592; Back to Record Movement</a>
        &nbsp;|&nbsp;
        <a href="/dashboard">Dashboard</a>
    </p>
</main>
</body>
</html>
