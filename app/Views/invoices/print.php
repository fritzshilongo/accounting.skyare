<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Invoice Print</title>
    <link rel="stylesheet" href="/assets/css/legacy-style.css">
    <style>
        @media print {
            .no-print { display: none; }
            body { margin: 0; background: #fff; }
            .container { box-shadow: none; }
        }

        body {
            background: #eef2f7;
            color: #16324f;
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 24px;
        }

        .container {
            background: #fff;
            margin: 0 auto;
            max-width: 980px;
            padding: 32px;
            border-radius: 18px;
            box-shadow: 0 18px 50px rgba(22, 50, 79, 0.12);
        }

        .header,
        .meta-grid,
        .party-grid,
        .summary-grid,
        .footer-grid {
            display: grid;
            gap: 20px;
        }

        .header,
        .party-grid,
        .footer-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .meta-grid,
        .summary-grid {
            grid-template-columns: repeat(4, minmax(0, 1fr));
        }

        .logo {
            max-width: 180px;
            max-height: 120px;
            object-fit: contain;
        }

        .panel {
            border: 1px solid #d8e0ea;
            border-radius: 14px;
            padding: 16px 18px;
            background: #fbfdff;
        }

        .eyebrow {
            color: #6b7c93;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.08em;
            margin: 0 0 10px;
            text-transform: uppercase;
        }

        .panel p,
        .panel h1,
        .panel h2 {
            margin: 0 0 8px;
        }

        .value {
            color: #0f4c81;
            font-size: 24px;
            font-weight: 700;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 24px 0;
        }

        th,
        td {
            border: 1px solid #d8e0ea;
            padding: 10px 12px;
            text-align: left;
        }

        th {
            background: #eaf2fb;
        }

        .text-right {
            text-align: right;
        }

        .grand-total {
            font-size: 28px;
            font-weight: 800;
            color: #0f4c81;
            text-align: right;
        }

        .muted {
            color: #6b7c93;
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

$customerName = (string) (($customer['customer_name'] ?? '') !== '' ? $customer['customer_name'] : ($invoice['client_name'] ?? ''));
$customerCompanyName = (string) ($customer['company_name'] ?? '');
$customerType = (string) ($customer['customer_type'] ?? '');
$customerRegistrationNumber = (string) ($customer['registration_number'] ?? '');
$customerAddress = (string) ($customer['address'] ?? '');
$customerEmail = (string) ($customer['email'] ?? '');
$customerPhone = (string) ($customer['phone'] ?? '');
$customerTaxNumber = (string) ($customer['tax_number'] ?? '');
$customerIdNumber = (string) ($customer['id_number'] ?? '');
$customerNotes = (string) ($customer['notes'] ?? '');
$companyAddressParts = array_values(array_filter([
    (string) ($company['address'] ?? ''),
    (string) ($company['city'] ?? ''),
    (string) ($company['province'] ?? ''),
    (string) ($company['postal_code'] ?? ''),
    (string) ($company['country'] ?? ''),
]));
$companyAddress = implode(', ', $companyAddressParts);
$companyBankParts = array_values(array_filter([
    (string) ($company['bank_name'] ?? ''),
    (string) ($company['bank_account_holder'] ?? ''),
    (string) ($company['bank_account_number'] ?? ''),
    (string) ($company['bank_swift_code'] ?? ''),
    (string) ($company['bank_iban'] ?? ''),
]));
?>
<div class="container">
    <div class="no-print" style="display:flex;gap:10px;justify-content:flex-end;">
        <button class="btn btn-primary" onclick="window.print()">Print / Save as PDF</button>
        <button class="btn btn-secondary" onclick="if(window.history.length > 1){ window.history.back(); } else { window.location.href='/invoices/view?invoice_id=<?= (int) $invoice['invoice_id'] ?>'; }">Back</button>
    </div>

    <section class="header" style="margin-top:20px;align-items:start;">
        <div>
            <?php if ($logoSrc !== ''): ?>
            <img src="<?= htmlspecialchars($logoSrc, ENT_QUOTES, 'UTF-8') ?>" alt="Company Logo" class="logo">
            <?php endif; ?>
            <h1 style="margin-top:16px;">Invoice</h1>
            <?php if (!empty($invoice['invoice_no'])): ?>
            <p class="muted">Reference: <?= htmlspecialchars((string) $invoice['invoice_no'], ENT_QUOTES, 'UTF-8') ?></p>
            <?php else: ?>
            <p class="muted">Reference: #<?= (int) $invoice['invoice_id'] ?></p>
            <?php endif; ?>
        </div>
        <div class="panel">
            <p class="eyebrow">From</p>
            <p><strong><?= htmlspecialchars((string) ($company['company_name'] ?? 'Skyare Trading CC'), ENT_QUOTES, 'UTF-8') ?></strong></p>
            <?php if ($companyAddress !== ''): ?><p><?= htmlspecialchars($companyAddress, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
            <?php if (!empty($company['phone'])): ?><p>Phone: <?= htmlspecialchars((string) $company['phone'], ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
            <?php if (!empty($company['email'])): ?><p>Email: <?= htmlspecialchars((string) $company['email'], ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
            <?php if (!empty($company['registration_number'])): ?><p>Registration No: <?= htmlspecialchars((string) $company['registration_number'], ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
            <?php if (!empty($company['city'])): ?><p>City: <?= htmlspecialchars((string) $company['city'], ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
            <?php if (!empty($company['province'])): ?><p>Province/Region: <?= htmlspecialchars((string) $company['province'], ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
            <?php if (!empty($company['postal_code'])): ?><p>Postal Code: <?= htmlspecialchars((string) $company['postal_code'], ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
            <?php if (!empty($company['country'])): ?><p>Country: <?= htmlspecialchars((string) $company['country'], ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
            <?php if (!empty($company['tax_number'])): ?><p>Tax No: <?= htmlspecialchars((string) $company['tax_number'], ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
            <?php if (!empty($company['vat_number'])): ?><p>VAT No: <?= htmlspecialchars((string) $company['vat_number'], ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
        </div>
    </section>

    <section class="meta-grid" style="margin:24px 0;">
        <div class="panel">
            <p class="eyebrow">Issue Date</p>
            <p><?= htmlspecialchars((string) $invoice['issue_date'], ENT_QUOTES, 'UTF-8') ?></p>
        </div>
        <div class="panel">
            <p class="eyebrow">Due Date</p>
            <p><?= htmlspecialchars((string) $invoice['due_date'], ENT_QUOTES, 'UTF-8') ?></p>
        </div>
        <div class="panel">
            <p class="eyebrow">Status</p>
            <p><?= htmlspecialchars((string) $invoice['status'], ENT_QUOTES, 'UTF-8') ?></p>
        </div>
        <div class="panel">
            <p class="eyebrow">Amount</p>
            <p class="value">N$ <?= number_format((float) $invoice['amount'], 2) ?></p>
        </div>
    </section>

    <section class="party-grid" style="margin-bottom:24px;">
        <div class="panel">
            <p class="eyebrow">Bill To</p>
            <p><strong><?= htmlspecialchars($customerName, ENT_QUOTES, 'UTF-8') ?></strong></p>
            <?php if ($customerType !== ''): ?><p>Type: <?= htmlspecialchars($customerType, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
            <?php if ($customerCompanyName !== ''): ?><p>Company: <?= htmlspecialchars($customerCompanyName, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
            <?php if ($customerRegistrationNumber !== ''): ?><p>Registration No: <?= htmlspecialchars($customerRegistrationNumber, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
            <?php if ($customerAddress !== ''): ?><p>Address: <?= htmlspecialchars($customerAddress, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
            <?php if ($customerPhone !== ''): ?><p>Phone: <?= htmlspecialchars($customerPhone, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
            <?php if ($customerEmail !== ''): ?><p>Email: <?= htmlspecialchars($customerEmail, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
            <?php if ($customerTaxNumber !== ''): ?><p>Tax No: <?= htmlspecialchars($customerTaxNumber, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
            <?php if ($customerIdNumber !== ''): ?><p>ID No: <?= htmlspecialchars($customerIdNumber, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
            <?php if ($customerNotes !== ''): ?><p>Notes: <?= nl2br(htmlspecialchars($customerNotes, ENT_QUOTES, 'UTF-8')) ?></p><?php endif; ?>
        </div>
        <div class="panel">
            <p class="eyebrow">Company Banking</p>
            <?php if ($companyBankParts !== []): ?>
            <?php foreach ($companyBankParts as $bankLine): ?>
            <p><?= htmlspecialchars($bankLine, ENT_QUOTES, 'UTF-8') ?></p>
            <?php endforeach; ?>
            <?php else: ?>
            <p class="muted">No bank details saved.</p>
            <?php endif; ?>
            <?php if (!empty($company['bank_routing_number'])): ?><p>Routing: <?= htmlspecialchars((string) $company['bank_routing_number'], ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
            <?php if (!empty($company['bank_swift_code'])): ?><p>SWIFT: <?= htmlspecialchars((string) $company['bank_swift_code'], ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
            <?php if (!empty($company['bank_iban'])): ?><p>IBAN: <?= htmlspecialchars((string) $company['bank_iban'], ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
        </div>
    </section>

    <?php if (!empty($items)): ?>
    <table>
        <thead>
            <tr>
                <th>Product / Service</th>
                <th>Item Details</th>
                <th class="text-right">Qty</th>
                <th class="text-right">Unit Price</th>
                <th class="text-right">Line Total</th>
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
                <td><?= htmlspecialchars($itemName, ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= $itemDetails !== '' ? nl2br(htmlspecialchars($itemDetails, ENT_QUOTES, 'UTF-8')) : '<span class="muted">No item description</span>' ?></td>
                <td class="text-right"><?= number_format((float) $item['quantity'], 2) ?></td>
                <td class="text-right">N$ <?= number_format((float) $item['unit_price'], 2) ?></td>
                <td class="text-right">N$ <?= number_format((float) $item['line_total'], 2) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>

    <?php if (!empty($invoice['notes'])): ?>
    <div class="panel" style="margin-bottom:20px;">
        <p class="eyebrow">Notes</p>
        <p><?= nl2br(htmlspecialchars((string) $invoice['notes'], ENT_QUOTES, 'UTF-8')) ?></p>
    </div>
    <?php endif; ?>

    <p class="grand-total">Total: N$ <?= number_format((float) $invoice['amount'], 2) ?></p>

    <section class="footer-grid" style="margin-top:28px;">
        <div class="panel">
            <p class="eyebrow">Prepared By</p>
            <p><?= htmlspecialchars((string) ($company['company_name'] ?? 'Skyare Trading CC'), ENT_QUOTES, 'UTF-8') ?></p>
            <?php if (!empty($company['email'])): ?><p><?= htmlspecialchars((string) $company['email'], ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
            <?php if (!empty($company['phone'])): ?><p><?= htmlspecialchars((string) $company['phone'], ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
        </div>
        <div class="panel">
            <p class="eyebrow">Customer Reference</p>
            <p><?= htmlspecialchars($customerName, ENT_QUOTES, 'UTF-8') ?></p>
            <?php if ($customerEmail !== ''): ?><p><?= htmlspecialchars($customerEmail, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
            <?php if ($customerPhone !== ''): ?><p><?= htmlspecialchars($customerPhone, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
        </div>
    </section>
</div>
</body>
</html>
