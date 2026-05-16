<!doctype html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width,initial-scale=1">
	<title>Sales</title>
	<link rel="stylesheet" href="/assets/css/legacy-style.css">
	<link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>
<main class="card card-wide">
	<h1>Sales</h1>
	<div class="button-group">
		<a class="btn btn-secondary" href="/sales/export/csv">Export CSV</a>
		<a class="btn btn-secondary" href="/sales/export/pdf" target="_blank" rel="noopener">Export PDF</a>
		<a class="btn btn-primary" href="/sales/financial-statement">Financial Statement &amp; General Ledger</a>
		<a class="btn btn-secondary" href="/sales/financial-statement/export/csv?from=<?= date('Y-01-01') ?>&amp;to=<?= date('Y-m-d') ?>">FS CSV</a>
		<a class="btn btn-secondary" href="/sales/financial-statement/export/pdf?from=<?= date('Y-01-01') ?>&amp;to=<?= date('Y-m-d') ?>" target="_blank" rel="noopener">FS PDF</a>
		<a class="btn btn-secondary" href="/sales/general-ledger/export/csv?from=<?= date('Y-01-01') ?>&amp;to=<?= date('Y-m-d') ?>">Ledger CSV</a>
		<a class="btn btn-secondary" href="/sales/general-ledger/export/pdf?from=<?= date('Y-01-01') ?>&amp;to=<?= date('Y-m-d') ?>" target="_blank" rel="noopener">Ledger PDF</a>
	</div>

	<p>Company: <?= htmlspecialchars($company['company_name'] ?? 'Unknown', ENT_QUOTES, 'UTF-8') ?></p>

	<div class="module-grid">
		<section class="module-card"><h2>Total Sales</h2><p>N$ <?= number_format((float) ($summary['total_sales'] ?? 0), 2) ?></p></section>
		<section class="module-card"><h2>Paid Sales</h2><p>N$ <?= number_format((float) ($summary['paid_sales'] ?? 0), 2) ?></p></section>
		<section class="module-card"><h2>Outstanding</h2><p>N$ <?= number_format((float) ($summary['outstanding_sales'] ?? 0), 2) ?></p></section>
		<section class="module-card"><h2>Total Invoices</h2><p><?= (int) ($summary['total_invoices'] ?? 0) ?></p></section>
	</div>

	<table class="table">
		<thead><tr><th>ID</th><th>Client</th><th>Amount</th><th>Status</th><th>Issue</th><th>Due</th></tr></thead>
		<tbody>
		<?php if (($rows ?? []) === []): ?>
			<tr><td colspan="6">No sales records.</td></tr>
		<?php else: foreach ($rows as $r): ?>
			<tr>
				<td><?= (int) $r['invoice_id'] ?></td>
				<td><?= htmlspecialchars($r['client_name'], ENT_QUOTES, 'UTF-8') ?></td>
				<td><?= number_format((float) $r['amount'], 2) ?></td>
				<td><?= htmlspecialchars($r['status'], ENT_QUOTES, 'UTF-8') ?></td>
				<td><?= htmlspecialchars($r['issue_date'], ENT_QUOTES, 'UTF-8') ?></td>
				<td><?= htmlspecialchars($r['due_date'], ENT_QUOTES, 'UTF-8') ?></td>
			</tr>
		<?php endforeach; endif; ?>
		</tbody>
	</table>

	<p><a href="/dashboard">Back to Dashboard</a></p>
</main>
</body>
</html>
