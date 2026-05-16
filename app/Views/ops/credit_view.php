<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Credit View</title>
    <link rel="stylesheet" href="/assets/css/legacy-style.css">
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>
<main class="card card-wide">
    <div class="button-group" style="justify-content:flex-end;">
        <a class="btn btn-secondary" href="/credit-management">Back to Credit Management</a>
        <a class="btn btn-secondary" href="/credit-management/agreement?credit_id=<?= (int) ($credit['credit_id'] ?? 0) ?>" target="_blank" rel="noopener">Agreement</a>
        <a class="btn btn-primary" href="/credit-management?pay_credit_id=<?= (int) ($credit['credit_id'] ?? 0) ?>#record-payment">Pay</a>
    </div>

    <h1>Credit Details</h1>
    <p><strong>Company:</strong> <?= htmlspecialchars((string) ($company['company_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>

    <div class="module-grid" style="margin-bottom:16px;">
        <section class="module-card">
            <h3>Credit Number</h3>
            <p><?= htmlspecialchars((string) ($credit['credit_no'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
        </section>
        <section class="module-card">
            <h3>Status</h3>
            <p><?= htmlspecialchars((string) ($credit['status'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
        </section>
        <section class="module-card">
            <h3>Customer</h3>
            <p><?= htmlspecialchars((string) ($credit['customer_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
        </section>
        <section class="module-card">
            <h3>Outstanding</h3>
            <p>N$ <?= number_format((float) ($credit['outstanding'] ?? 0), 2) ?></p>
        </section>
    </div>

    <table class="table">
        <tbody>
            <tr><th>Amount Issued</th><td>N$ <?= number_format((float) ($credit['amount'] ?? 0), 2) ?></td></tr>
            <tr><th>Interest Type</th><td><?= htmlspecialchars((string) ($credit['interest_type'] ?? 'flat'), ENT_QUOTES, 'UTF-8') ?></td></tr>
            <tr><th>Interest %</th><td><?= number_format((float) ($credit['interest_percent'] ?? 0), 2) ?>%</td></tr>
            <tr><th>Interest Amount</th><td>N$ <?= number_format((float) ($credit['interest_amount'] ?? 0), 2) ?></td></tr>
            <tr><th>Total Owed</th><td>N$ <?= number_format((float) ($credit['total_amount'] ?? 0), 2) ?></td></tr>
            <tr><th>Amount Paid</th><td>N$ <?= number_format((float) ($credit['amount_paid'] ?? 0), 2) ?></td></tr>
            <tr><th>Remaining Balance</th><td>N$ <?= number_format((float) ($credit['outstanding'] ?? 0), 2) ?></td></tr>
            <tr><th>Issue Date</th><td><?= htmlspecialchars((string) ($credit['issue_date'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td></tr>
            <tr><th>Due Date</th><td><?= htmlspecialchars((string) ($credit['due_date'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td></tr>
            <tr><th>Last Payment Date</th><td><?= htmlspecialchars((string) ($credit['last_payment_date'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td></tr>
            <tr><th>Reason</th><td><?= htmlspecialchars((string) ($credit['reason'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td></tr>
        </tbody>
    </table>

    <h2 style="margin-top:20px;">Payment Timeline &amp; Running Balance</h2>
    <table class="table">
        <thead>
            <tr>
                <th>#</th>
                <th>Payment Date</th>
                <th>Amount Paid</th>
                <th>Method</th>
                <th>Reference</th>
                <th>Running Balance</th>
            </tr>
        </thead>
        <tbody>
        <?php if (($payments ?? []) === []): ?>
            <tr><td colspan="6">No payments recorded yet.</td></tr>
        <?php else: foreach (($payments ?? []) as $i => $p): ?>
            <tr>
                <td><?= (int) $i + 1 ?></td>
                <td><?= htmlspecialchars((string) ($p['payment_date'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                <td>N$ <?= number_format((float) ($p['amount'] ?? 0), 2) ?></td>
                <td><?= htmlspecialchars((string) ($p['payment_method'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars((string) ($p['reference'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                <td>N$ <?= number_format((float) ($p['running_balance'] ?? 0), 2) ?></td>
            </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>
</main>
</body>
</html>
