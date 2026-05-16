<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Estimate Print</title>
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
        .footer-grid {
            display: grid;
            gap: 20px;
        }

        .header,
        .party-grid,
        .footer-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .meta-grid {
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
        .panel h1 {
            margin: 0 0 8px;
        }

        .value {
            color: #0f4c81;
            font-size: 24px;
            font-weight: 700;
        }

        .grand-total {
            font-size: 28px;
            font-weight: 800;
            color: #0f4c81;
            text-align: right;
            margin: 24px 0 0;
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

$customerName = (string) (($customer['customer_name'] ?? '') !== '' ? $customer['customer_name'] : ($row['client_name'] ?? ''));
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
        <button class="btn btn-secondary" onclick="if(window.history.length > 1){ window.history.back(); } else { window.location.href='/estimates/view?estimate_id=<?= (int) $row['estimate_id'] ?>'; }">Back</button>
    </div>

    <section class="header" style="margin-top:20px;align-items:start;">
        <div>
            <?php if ($logoSrc !== ''): ?>
            <img src="<?= htmlspecialchars($logoSrc, ENT_QUOTES, 'UTF-8') ?>" alt="Company Logo" class="logo">
            <?php endif; ?>
            <h1 style="margin-top:16px;">Estimate</h1>
            <p class="muted">Reference: #<?= (int) $row['estimate_id'] ?></p>
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
            <p class="eyebrow">Estimate Date</p>
            <p><?= htmlspecialchars((string) $row['estimate_date'], ENT_QUOTES, 'UTF-8') ?></p>
        </div>
        <div class="panel">
            <p class="eyebrow">Expiry Date</p>
            <p><?= htmlspecialchars((string) $row['expiry_date'], ENT_QUOTES, 'UTF-8') ?></p>
        </div>
        <div class="panel">
            <p class="eyebrow">Status</p>
            <p><?= htmlspecialchars((string) $row['status'], ENT_QUOTES, 'UTF-8') ?></p>
        </div>
        <div class="panel">
            <p class="eyebrow">Quoted Amount</p>
            <p class="value">N$ <?= number_format((float) $row['amount'], 2) ?></p>
        </div>
    </section>

    <?php if (!empty($row['product_name']) || ($row['quantity'] ?? null) !== null || ($row['unit_price'] ?? null) !== null): ?>
    <section class="party-grid" style="margin-bottom:24px;grid-template-columns:1fr;">
        <div class="panel">
            <p class="eyebrow">Estimate Line</p>
            <?php if (!empty($row['product_name'])): ?><p><strong>Product/Service:</strong> <?= htmlspecialchars((string) $row['product_name'], ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
            <?php if (!empty($row['line_description'])): ?><p><strong>Item Description:</strong><br><?= nl2br(htmlspecialchars((string) $row['line_description'], ENT_QUOTES, 'UTF-8')) ?></p><?php endif; ?>
            <?php if (($row['quantity'] ?? null) !== null): ?><p><strong>Quantity:</strong> <?= (int) $row['quantity'] ?></p><?php endif; ?>
            <?php if (($row['unit_price'] ?? null) !== null): ?><p><strong>Unit Price:</strong> N$ <?= number_format((float) $row['unit_price'], 2) ?></p><?php endif; ?>
        </div>
    </section>
    <?php endif; ?>

    <section class="party-grid" style="margin-bottom:24px;">
        <div class="panel">
            <p class="eyebrow">Prepared For</p>
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

    <p class="grand-total">Total: N$ <?= number_format((float) $row['amount'], 2) ?></p>

    <section class="footer-grid" style="margin-top:28px;">
        <div class="panel">
            <p class="eyebrow">Prepared By</p>
            <p><?= htmlspecialchars((string) ($company['company_name'] ?? 'Skyare Trading CC'), ENT_QUOTES, 'UTF-8') ?></p>
            <?php if (!empty($company['email'])): ?><p><?= htmlspecialchars((string) $company['email'], ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
            <?php if (!empty($company['phone'])): ?><p><?= htmlspecialchars((string) $company['phone'], ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
        </div>
        <div class="panel">
            <p class="eyebrow">Customer Contact</p>
            <p><?= htmlspecialchars($customerName, ENT_QUOTES, 'UTF-8') ?></p>
            <?php if ($customerEmail !== ''): ?><p><?= htmlspecialchars($customerEmail, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
            <?php if ($customerPhone !== ''): ?><p><?= htmlspecialchars($customerPhone, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
        </div>
    </section>
</div>
</body>
</html>
