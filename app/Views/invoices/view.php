<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Invoice Details</title>
    <link rel="stylesheet" href="/assets/css/legacy-style.css">
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>
<main class="card">
    <h1>Invoice #<?= (int) $invoice['invoice_id'] ?></h1>
    <p>Company: <?= htmlspecialchars($company['company_name'] ?? 'Unknown', ENT_QUOTES, 'UTF-8') ?></p>

    <p><strong>Client:</strong> <?= htmlspecialchars($invoice['client_name'], ENT_QUOTES, 'UTF-8') ?></p>
    <?php if (!empty($invoice['invoice_no'])): ?>
    <p><strong>Invoice No:</strong> <?= htmlspecialchars($invoice['invoice_no'], ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>
    <p><strong>Amount:</strong> <?= number_format((float) $invoice['amount'], 2) ?></p>
    <p><strong>Issue Date:</strong> <?= htmlspecialchars($invoice['issue_date'], ENT_QUOTES, 'UTF-8') ?></p>
    <p><strong>Due Date:</strong> <?= htmlspecialchars($invoice['due_date'], ENT_QUOTES, 'UTF-8') ?></p>
    <p><strong>Status:</strong> <?= htmlspecialchars($invoice['status'], ENT_QUOTES, 'UTF-8') ?></p>

    <?php if (!empty($items)): ?>
    <h2 style="margin-top:20px;">Line Items</h2>
    <table style="width:100%;border-collapse:collapse;margin-bottom:16px;">
        <thead>
            <tr style="background:#f4f6fa;">
                <th style="padding:8px;border:1px solid #ddd;text-align:left;">Product / Service</th>
                <th style="padding:8px;border:1px solid #ddd;text-align:left;">Item Details</th>
                <th style="padding:8px;border:1px solid #ddd;text-align:right;">Qty</th>
                <th style="padding:8px;border:1px solid #ddd;text-align:right;">Unit Price</th>
                <th style="padding:8px;border:1px solid #ddd;text-align:right;">Line Total</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($items as $item): ?>
            <tr>
                <?php
                $itemName = trim((string) ($item['product_name'] ?? ''));
                $itemDetails = trim((string) ($item['description'] ?? ''));
                if ($itemName === '') {
                    $itemName = $itemDetails !== '' ? $itemDetails : 'Item';
                }
                ?>
                <td style="padding:8px;border:1px solid #ddd;"><?= htmlspecialchars($itemName, ENT_QUOTES, 'UTF-8') ?></td>
                <td style="padding:8px;border:1px solid #ddd;"><?= $itemDetails !== '' ? nl2br(htmlspecialchars($itemDetails, ENT_QUOTES, 'UTF-8')) : '<span style="color:#6b7280;">No item description</span>' ?></td>
                <td style="padding:8px;border:1px solid #ddd;text-align:right;"><?= number_format((float) $item['quantity'], 2) ?></td>
                <td style="padding:8px;border:1px solid #ddd;text-align:right;">N$ <?= number_format((float) $item['unit_price'], 2) ?></td>
                <td style="padding:8px;border:1px solid #ddd;text-align:right;">N$ <?= number_format((float) $item['line_total'], 2) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>

    <p>
        <a href="/invoices/print?invoice_id=<?= (int) $invoice['invoice_id'] ?>" target="_blank" rel="noopener">Print</a>
        |
        <a href="/invoices/edit?invoice_id=<?= (int) $invoice['invoice_id'] ?>">Edit</a>
        |
        <a href="/invoices">Back to invoices</a>
    </p>

    <?php if (($invoice['status'] ?? '') !== 'cancelled'): ?>
    <form method="post" action="/invoices/disable" onsubmit="return confirm('Disable this invoice? It will remain in the financial records and be marked as cancelled.');">
        <input type="hidden" name="_token" value="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>">
        <input type="hidden" name="invoice_id" value="<?= (int) $invoice['invoice_id'] ?>">
        <button type="submit">Disable Invoice</button>
    </form>
    <?php else: ?>
    <p><strong>Invoice state:</strong> Disabled (retained for audit and financial statements).</p>
    <?php endif; ?>
</main>
</body>
</html>

