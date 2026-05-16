<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Financial Statement</title>
    <link rel="stylesheet" href="/assets/css/legacy-style.css">
    <link rel="stylesheet" href="/assets/css/app.css">
    <style>
        .fs-section { margin: 24px 0; }
        .fs-section h2 { border-bottom: 2px solid #dee2e6; padding-bottom: 6px; }
        .fs-summary { display: flex; gap: 16px; flex-wrap: wrap; margin: 16px 0; }
        .fs-card { background: #f8f9fa; border-radius: 8px; padding: 16px 24px; min-width: 160px; }
        .fs-card.income { border-left: 4px solid #27ae60; }
        .fs-card.expense { border-left: 4px solid #e74c3c; }
        .fs-card.net-pos { border-left: 4px solid #2980b9; }
        .fs-card.net-neg { border-left: 4px solid #e67e22; }
        .fs-card h3 { margin: 0 0 6px; font-size: 0.9em; color: #6c757d; }
        .fs-card p { margin: 0; font-size: 1.3em; font-weight: 600; }
        @media print {
            .no-print { display: none; }
            body { background: #fff; }
        }
    </style>
</head>
<body>
<?php
$logoData = trim((string) ($company['logo_data'] ?? ''));
$logoSrc = '';
if ($logoData !== '') {
    $logoSrc = str_starts_with($logoData, 'data:image') ? $logoData : 'data:image/png;base64,' . $logoData;
}
?>
<main class="card card-wide">
    <div class="no-print" style="display:flex;gap:10px;justify-content:flex-end;margin-bottom:16px;">
        <button class="btn btn-primary" onclick="window.print()">Print / Save as PDF</button>
        <a class="btn btn-secondary" href="/sales/financial-statement/export/csv?from=<?= urlencode((string) ($from_date ?? '')) ?>&amp;to=<?= urlencode((string) ($to_date ?? '')) ?>">FS CSV</a>
        <a class="btn btn-secondary" href="/sales/financial-statement/export/pdf?from=<?= urlencode((string) ($from_date ?? '')) ?>&amp;to=<?= urlencode((string) ($to_date ?? '')) ?>" target="_blank" rel="noopener">FS PDF</a>
        <a class="btn btn-secondary" href="/sales/general-ledger/export/csv?from=<?= urlencode((string) ($from_date ?? '')) ?>&amp;to=<?= urlencode((string) ($to_date ?? '')) ?>">Ledger CSV</a>
        <a class="btn btn-secondary" href="/sales/general-ledger/export/pdf?from=<?= urlencode((string) ($from_date ?? '')) ?>&amp;to=<?= urlencode((string) ($to_date ?? '')) ?>" target="_blank" rel="noopener">Ledger PDF</a>
        <button class="btn btn-secondary" onclick="if (window.history.length > 1) { window.history.back(); } else { window.location.href = '/sales'; }">Back to Sales</button>
    </div>

    <h1>Financial Statement &amp; General Ledger</h1>
    <?php if ($logoSrc !== ''): ?>
    <p style="margin-bottom:8px;"><img src="<?= htmlspecialchars($logoSrc, ENT_QUOTES, 'UTF-8') ?>" alt="Company Logo" style="max-height:90px;max-width:180px;object-fit:contain;"></p>
    <?php endif; ?>
    <p><strong><?= htmlspecialchars($company['company_name'] ?? '', ENT_QUOTES, 'UTF-8') ?></strong></p>

    <form method="get" action="/sales/financial-statement" style="display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap;" class="no-print">
        <div>
            <label>From</label>
            <input type="date" name="from" value="<?= htmlspecialchars($from_date ?? '', ENT_QUOTES, 'UTF-8') ?>">
        </div>
        <div>
            <label>To</label>
            <input type="date" name="to" value="<?= htmlspecialchars($to_date ?? '', ENT_QUOTES, 'UTF-8') ?>">
        </div>
        <button type="submit" class="btn btn-primary">Apply</button>
    </form>

    <p style="color:#6c757d;margin-top:8px;">Period: <?= htmlspecialchars($from_date ?? '', ENT_QUOTES, 'UTF-8') ?> &mdash; <?= htmlspecialchars($to_date ?? '', ENT_QUOTES, 'UTF-8') ?></p>

    <!-- Summary cards -->
    <div class="fs-summary">
        <div class="fs-card income">
            <h3>Total Invoiced</h3>
            <p>N$ <?= number_format((float) ($total_income ?? 0), 2) ?></p>
        </div>
        <div class="fs-card income">
            <h3>Total Collected (Paid)</h3>
            <p>N$ <?= number_format((float) ($total_paid ?? 0), 2) ?></p>
        </div>
        <div class="fs-card expense">
            <h3>Total Expenses</h3>
            <p>N$ <?= number_format((float) ($total_expenses ?? 0), 2) ?></p>
        </div>
        <div class="fs-card <?= ((float) ($net_income ?? 0) >= 0) ? 'net-pos' : 'net-neg' ?>">
            <h3>Net Income</h3>
            <p>N$ <?= number_format((float) ($net_income ?? 0), 2) ?></p>
        </div>
    </div>

    <!-- Income ledger -->
    <div class="fs-section">
        <h2>Income Ledger (Invoices)</h2>
        <table class="table">
            <thead>
                <tr>
                    <th>Invoice ID</th>
                    <th>Invoice No</th>
                    <th>Client</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>Issue Date</th>
                    <th>Due Date</th>
                </tr>
            </thead>
            <tbody>
            <?php if (($income_rows ?? []) === []): ?>
                <tr><td colspan="7">No income records for this period.</td></tr>
            <?php else: foreach ($income_rows as $r): ?>
                <tr>
                    <td><?= (int) $r['invoice_id'] ?></td>
                    <td><?= htmlspecialchars($r['invoice_no'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($r['client_name'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td>N$ <?= number_format((float) $r['amount'], 2) ?></td>
                    <td><?= htmlspecialchars($r['status'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($r['issue_date'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($r['due_date'], ENT_QUOTES, 'UTF-8') ?></td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
            <?php if (!empty($income_rows)): ?>
            <tfoot>
                <tr>
                    <td colspan="3"><strong>Total</strong></td>
                    <td><strong>N$ <?= number_format((float) ($total_income ?? 0), 2) ?></strong></td>
                    <td colspan="3"></td>
                </tr>
            </tfoot>
            <?php endif; ?>
        </table>
    </div>

    <!-- Expense ledger -->
    <div class="fs-section">
        <h2>Expense Ledger</h2>
        <table class="table">
            <thead>
                <tr>
                    <th>Expense ID</th>
                    <th>Category</th>
                    <th>Description</th>
                    <th>Amount</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
            <?php if (($expense_rows ?? []) === []): ?>
                <tr><td colspan="5">No expense records for this period.</td></tr>
            <?php else: foreach ($expense_rows as $r): ?>
                <tr>
                    <td><?= (int) $r['expense_id'] ?></td>
                    <td><?= htmlspecialchars($r['category'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($r['description'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                    <td>N$ <?= number_format((float) $r['amount'], 2) ?></td>
                    <td><?= htmlspecialchars($r['expense_date'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
            <?php if (!empty($expense_rows)): ?>
            <tfoot>
                <tr>
                    <td colspan="3"><strong>Total</strong></td>
                    <td><strong>N$ <?= number_format((float) ($total_expenses ?? 0), 2) ?></strong></td>
                    <td></td>
                </tr>
            </tfoot>
            <?php endif; ?>
        </table>
    </div>

    <div style="border-top:2px solid #dee2e6;padding-top:16px;text-align:right;">
        <strong>Net Income (Collected &minus; Expenses): N$ <?= number_format((float) ($net_income ?? 0), 2) ?></strong>
    </div>

    <p class="no-print"><a href="/dashboard">Back to Dashboard</a></p>
</main>
</body>
</html>
