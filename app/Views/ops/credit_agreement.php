<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Credit Agreement</title>
    <link rel="stylesheet" href="/assets/css/legacy-style.css">
    <style>
        .agreement { max-width: 900px; margin: 20px auto; background:#fff; padding: 24px; border:1px solid #ddd; }
        .section { margin-top: 18px; }
        .section h2 { margin:0 0 8px; font-size: 1.05rem; border-bottom:1px solid #ddd; padding-bottom: 4px; }
        .grid { display:grid; grid-template-columns:1fr 1fr; gap:12px 24px; }
        .line { margin: 6px 0; }
        .sign-row { display:grid; grid-template-columns:1fr 1fr; gap:32px; margin-top:30px; }
        .sign-box { min-height: 110px; }
        .sign-line { margin-top:45px; border-top:1px solid #222; padding-top: 6px; }
        @media print {
            .no-print { display:none; }
            body { background:#fff; }
            .agreement { border:0; margin:0; max-width:none; }
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
<div class="agreement">
    <div class="no-print" style="display:flex;justify-content:flex-end;gap:10px;">
        <button class="btn btn-primary" onclick="window.print()">Print / Save PDF</button>
        <button class="btn btn-secondary" onclick="if (window.history.length > 1) { window.history.back(); } else { window.location.href = '/credit-management'; }">Back</button>
    </div>

    <h1 style="margin-bottom:4px;">CREDIT AGREEMENT</h1>
    <p style="margin-top:0;color:#666;">Agreement date: <?= htmlspecialchars(date('Y-m-d'), ENT_QUOTES, 'UTF-8') ?></p>

    <?php if ($logoSrc !== ''): ?>
    <div style="margin:10px 0 16px;">
        <img src="<?= htmlspecialchars($logoSrc, ENT_QUOTES, 'UTF-8') ?>" alt="Company Logo" style="max-height:90px;max-width:180px;object-fit:contain;">
    </div>
    <?php endif; ?>

    <div class="section">
        <h2>CREDITOR DETAILS</h2>
        <div class="line"><strong>Company:</strong> <?= htmlspecialchars((string) ($company['company_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
        <div class="line"><strong>Telephone:</strong> <?= htmlspecialchars((string) ($company['phone'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
        <div class="line"><strong>Email:</strong> <?= htmlspecialchars((string) ($company['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
        <div class="line"><strong>Address:</strong> <?= htmlspecialchars((string) ($company['address'] ?? ''), ENT_QUOTES, 'UTF-8') ?><?= !empty($company['city']) ? ', ' . htmlspecialchars((string) $company['city'], ENT_QUOTES, 'UTF-8') : '' ?></div>
    </div>

    <div class="section">
        <h2>COMPANY BANKING DETAILS</h2>
        <?php
        $hasBankDetails = !empty($company['bank_name'])
            || !empty($company['bank_account_holder'])
            || !empty($company['bank_account_number'])
            || !empty($company['bank_routing_number'])
            || !empty($company['bank_swift_code'])
            || !empty($company['bank_iban']);
        ?>
        <?php if ($hasBankDetails): ?>
            <?php if (!empty($company['bank_name'])): ?>
                <div class="line"><strong>Bank Name:</strong> <?= htmlspecialchars((string) $company['bank_name'], ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>
            <?php if (!empty($company['bank_account_holder'])): ?>
                <div class="line"><strong>Account Holder:</strong> <?= htmlspecialchars((string) $company['bank_account_holder'], ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>
            <?php if (!empty($company['bank_account_number'])): ?>
                <div class="line"><strong>Account Number:</strong> <?= htmlspecialchars((string) $company['bank_account_number'], ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>
            <?php if (!empty($company['bank_routing_number'])): ?>
                <div class="line"><strong>Routing Number:</strong> <?= htmlspecialchars((string) $company['bank_routing_number'], ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>
            <?php if (!empty($company['bank_swift_code'])): ?>
                <div class="line"><strong>SWIFT Code:</strong> <?= htmlspecialchars((string) $company['bank_swift_code'], ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>
            <?php if (!empty($company['bank_iban'])): ?>
                <div class="line"><strong>IBAN:</strong> <?= htmlspecialchars((string) $company['bank_iban'], ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>
        <?php else: ?>
            <div class="line">No banking details saved. Update Company Details to display payment account information here.</div>
        <?php endif; ?>
    </div>

    <div class="section">
        <h2>DEBTOR (CUSTOMER) DETAILS</h2>
        <div class="line"><strong>Full Name:</strong> <?= htmlspecialchars((string) ($credit['customer_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
        <div class="line"><strong>Telephone:</strong> <?= htmlspecialchars((string) ($credit['customer_phone'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
        <div class="line"><strong>Address:</strong> <?= htmlspecialchars((string) ($credit['customer_address'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
        <div class="line"><strong>Email:</strong> <?= htmlspecialchars((string) ($credit['customer_email'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
        <?php if (!empty($credit['id_number'])): ?>
            <div class="line"><strong>ID Number:</strong> <?= htmlspecialchars((string) $credit['id_number'], ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
    </div>

    <div class="section">
        <h2>CREDIT AGREEMENT DETAILS</h2>
        <div class="grid">
            <div><strong>Credit Number:</strong> <?= htmlspecialchars((string) ($credit['credit_no'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
            <div><strong>Credit Status:</strong> <?= htmlspecialchars((string) ($credit['status'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
            <div><strong>Credit Amount:</strong> N$ <?= number_format((float) ($credit['amount'] ?? 0), 2) ?></div>
            <div><strong>Interest Type:</strong> <?= htmlspecialchars((string) ($credit['interest_type'] ?? 'flat'), ENT_QUOTES, 'UTF-8') ?></div>
            <div><strong>Interest Percentage:</strong> <?= number_format((float) ($credit['interest_percent'] ?? 0), 2) ?>%</div>
            <div><strong>Interest Amount:</strong> N$ <?= number_format((float) ($credit['interest_amount'] ?? 0), 2) ?></div>
            <div><strong>Total Owed:</strong> N$ <?= number_format((float) ($credit['total_amount'] ?? 0), 2) ?></div>
            <div><strong>Amount Paid:</strong> N$ <?= number_format((float) ($credit['amount_paid'] ?? 0), 2) ?></div>
            <div><strong>Outstanding Amount:</strong> N$ <?= number_format((float) ($credit['outstanding'] ?? 0), 2) ?></div>
            <div><strong>Due Date:</strong> <?= htmlspecialchars((string) ($credit['due_date'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
            <div><strong>Issued Date:</strong> <?= htmlspecialchars((string) ($credit['issue_date'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
            <div><strong>Last Payment Date:</strong> <?= htmlspecialchars((string) ($credit['last_payment_date'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></div>
            <div><strong>Reason:</strong> <?= htmlspecialchars((string) ($credit['reason'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
        </div>
    </div>

    <div class="section">
        <h2>TERMS &amp; CONDITIONS</h2>
        <ol>
            <li>This credit is issued to the above-named customer as stated in this agreement.</li>
            <li>The customer agrees to repay the outstanding amount by the due date.</li>
            <li>All payments should be made to the creditor using the banking details provided by the company.</li>
            <li>Any disputes should be resolved through mutual agreement between the parties.</li>
            <li>This agreement is binding on both the creditor and debtor.</li>
            <li>Payment history updates this agreement's outstanding and paid totals.</li>
        </ol>
    </div>

    <div class="section">
        <h2>SIGNATURES</h2>
        <div class="sign-row">
            <div class="sign-box">
                <strong>FOR THE CREDITOR (Company Representative):</strong>
                <div class="sign-line">Signature</div>
                <div class="sign-line">Printed Name &amp; Date</div>
            </div>
            <div class="sign-box">
                <strong>FOR THE DEBTOR (Customer):</strong>
                <div class="sign-line">Signature</div>
                <div class="sign-line">Printed Name &amp; Date</div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
