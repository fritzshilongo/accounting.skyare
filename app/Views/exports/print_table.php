<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title><?= htmlspecialchars($title ?? 'Export', ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="stylesheet" href="/css/legacy-style.css">
    <style>
        @media print {
            .no-print { display: none; }
            body { margin: 0; background: #fff; }
            .container { box-shadow: none; }
        }
    </style>
</head>
<body>
<div class="container">
    <?php $backHref = (string) ($back_href ?? '/dashboard'); ?>
    <div class="no-print" style="display:flex;gap:10px;justify-content:flex-end;">
        <button class="btn btn-primary" onclick="window.print()">Print / Save as PDF</button>
        <button class="btn btn-secondary" onclick="if (window.history.length > 1) { window.history.back(); } else { window.location.href = '<?= htmlspecialchars($backHref, ENT_QUOTES, 'UTF-8') ?>'; }">Back</button>
    </div>

    <?php
    $logoData = trim((string) ($company['logo_data'] ?? ''));
    $logoSrc = '';
    if ($logoData !== '') {
        $logoSrc = str_starts_with($logoData, 'data:image') ? $logoData : 'data:image/png;base64,' . $logoData;
    }
    ?>

    <h1><?= htmlspecialchars($title ?? 'Export', ENT_QUOTES, 'UTF-8') ?></h1>
        <div style="display:flex;align-items:flex-start;gap:24px;margin-bottom:16px;">
            <?php if ($logoSrc !== ''): ?>
            <img src="<?= htmlspecialchars($logoSrc, ENT_QUOTES, 'UTF-8') ?>"
                 alt="Logo" style="max-height:80px;max-width:160px;object-fit:contain;">
            <?php endif; ?>
            <div>
                <strong style="font-size:1.1em;"><?= htmlspecialchars($company['company_name'] ?? '', ENT_QUOTES, 'UTF-8') ?></strong><br>
                <?php if (!empty($company['address'])): ?><?= htmlspecialchars($company['address'], ENT_QUOTES, 'UTF-8') ?><?= !empty($company['city']) ? ', ' . htmlspecialchars($company['city'], ENT_QUOTES, 'UTF-8') : '' ?><?= !empty($company['province']) ? ', ' . htmlspecialchars($company['province'], ENT_QUOTES, 'UTF-8') : '' ?><?= !empty($company['postal_code']) ? ' ' . htmlspecialchars($company['postal_code'], ENT_QUOTES, 'UTF-8') : '' ?><br><?php endif; ?>
                <?php if (!empty($company['country'])): ?><?= htmlspecialchars($company['country'], ENT_QUOTES, 'UTF-8') ?><br><?php endif; ?>
                <?php if (!empty($company['phone'])): ?>Tel: <?= htmlspecialchars($company['phone'], ENT_QUOTES, 'UTF-8') ?><br><?php endif; ?>
                <?php if (!empty($company['email'])): ?>Email: <?= htmlspecialchars($company['email'], ENT_QUOTES, 'UTF-8') ?><br><?php endif; ?>
                <?php if (!empty($company['tax_number'])): ?>Tax No: <?= htmlspecialchars($company['tax_number'], ENT_QUOTES, 'UTF-8') ?><br><?php endif; ?>
                <?php if (!empty($company['vat_number'])): ?>VAT No: <?= htmlspecialchars($company['vat_number'], ENT_QUOTES, 'UTF-8') ?><br><?php endif; ?>
                <?php if (!empty($company['bank_name'])): ?>
                    Bank: <?= htmlspecialchars($company['bank_name'], ENT_QUOTES, 'UTF-8') ?>
                    <?= !empty($company['bank_account_number']) ? ' | Acc: ' . htmlspecialchars($company['bank_account_number'], ENT_QUOTES, 'UTF-8') : '' ?><br>
                <?php endif; ?>
            </div>
        </div>

    <table class="module-table" style="width:100%;border-collapse:collapse;">
        <thead>
        <tr>
            <?php foreach (($columns ?? []) as $col): ?>
                <th><?= htmlspecialchars((string) $col, ENT_QUOTES, 'UTF-8') ?></th>
            <?php endforeach; ?>
        </tr>
        </thead>
        <tbody>
        <?php if (($rows ?? []) === []): ?>
            <tr><td colspan="<?= count($columns ?? []) ?>">No data.</td></tr>
        <?php else: foreach (($rows ?? []) as $r): ?>
            <tr>
                <?php foreach ($r as $cell): ?>
                    <td><?= htmlspecialchars((string) $cell, ENT_QUOTES, 'UTF-8') ?></td>
                <?php endforeach; ?>
            </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>
</body>
</html>
