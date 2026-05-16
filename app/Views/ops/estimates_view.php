<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Estimate View</title>
    <link rel="stylesheet" href="/assets/css/legacy-style.css">
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>
<main class="card">
    <h1>Estimate #<?= (int) $row['estimate_id'] ?></h1>
    <p><strong>Company:</strong> <?= htmlspecialchars($company['company_name'] ?? 'Unknown', ENT_QUOTES, 'UTF-8') ?></p>
    <p><strong>Client:</strong> <?= htmlspecialchars($row['client_name'], ENT_QUOTES, 'UTF-8') ?></p>
    <?php if (!empty($row['product_name'])): ?><p><strong>Product/Service:</strong> <?= htmlspecialchars((string) $row['product_name'], ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
    <?php if (!empty($row['line_description'])): ?><p><strong>Item Description:</strong><br><?= nl2br(htmlspecialchars((string) $row['line_description'], ENT_QUOTES, 'UTF-8')) ?></p><?php endif; ?>
    <?php if (($row['quantity'] ?? null) !== null): ?><p><strong>Quantity:</strong> <?= (int) $row['quantity'] ?></p><?php endif; ?>
    <?php if (($row['unit_price'] ?? null) !== null): ?><p><strong>Unit Price:</strong> N$ <?= number_format((float) $row['unit_price'], 2) ?></p><?php endif; ?>
    <p><strong>Amount:</strong> N$ <?= number_format((float) $row['amount'], 2) ?></p>
    <p><strong>Estimate Date:</strong> <?= htmlspecialchars($row['estimate_date'], ENT_QUOTES, 'UTF-8') ?></p>
    <p><strong>Expiry Date:</strong> <?= htmlspecialchars($row['expiry_date'], ENT_QUOTES, 'UTF-8') ?></p>
    <p><strong>Status:</strong> <?= htmlspecialchars($row['status'], ENT_QUOTES, 'UTF-8') ?></p>
    <?php if ((int) ($row['converted_invoice_id'] ?? 0) > 0): ?>
    <p><strong>Converted Invoice:</strong> <a href="/invoices/view?invoice_id=<?= (int) $row['converted_invoice_id'] ?>">Invoice #<?= (int) $row['converted_invoice_id'] ?></a></p>
    <?php endif; ?>
    <p>
        <a href="/estimates/print?estimate_id=<?= (int) $row['estimate_id'] ?>" target="_blank" rel="noopener">Print</a>
        |
        <a href="/estimates/edit?estimate_id=<?= (int) $row['estimate_id'] ?>">Edit</a>
        <?php if ((int) ($row['converted_invoice_id'] ?? 0) > 0): ?>
        |
        <a href="/invoices/view?invoice_id=<?= (int) $row['converted_invoice_id'] ?>">View Invoice</a>
        <?php elseif (($row['status'] ?? '') === 'accepted'): ?>
        |
        <form method="post" action="/estimates/convert-to-invoice" class="inline-form" style="display:inline;" onsubmit="return confirm('Convert this accepted estimate into an invoice?');">
            <input type="hidden" name="_token" value="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="estimate_id" value="<?= (int) $row['estimate_id'] ?>">
            <button type="submit">Convert to Invoice</button>
        </form>
        <?php endif; ?>
        |
        <a href="/estimates">Back</a>
    </p>
</main>
</body>
</html>
