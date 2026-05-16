<!doctype html>
<html lang="en">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Customer Statement</title>
<link rel="stylesheet" href="/assets/css/legacy-style.css"><link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>
<main class="card card-wide">
<h1>Customer Statement</h1>

<form method="get" action="/customer-statement" style="display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap;">
	<div>
		<label>Customer</label>
		<select name="customer_id">
			<option value="">Select customer...</option>
			<?php foreach (($customers ?? []) as $c): ?>
				<option value="<?= (int) $c['customer_id'] ?>"
					<?= ((string) ($selected_id ?? '') === (string) $c['customer_id']) ? 'selected' : '' ?>>
					<?= htmlspecialchars($c['customer_name'], ENT_QUOTES, 'UTF-8') ?>
				</option>
			<?php endforeach; ?>
		</select>
	</div>
	<button type="submit" class="btn btn-primary">Load Statement</button>
	<?php if (!empty($selected_id)): ?>
		<a class="btn btn-secondary" href="/customer-statement/export/pdf?customer_id=<?= (int) $selected_id ?>" target="_blank" rel="noopener">Export PDF</a>
	<?php endif; ?>
</form>

<?php if (!empty($selected_customer)): ?>
<h2>Statement for: <?= htmlspecialchars($selected_customer['customer_name'] ?? '', ENT_QUOTES, 'UTF-8') ?></h2>
<?php if (!empty($selected_customer['email'])): ?><p>Email: <?= htmlspecialchars($selected_customer['email'], ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>

<div class="module-grid" style="margin:16px 0;">
	<section class="module-card">
		<h3>Total Invoiced</h3>
		<p>N$ <?= number_format((float) ($totals['total'] ?? 0), 2) ?></p>
	</section>
	<section class="module-card" style="border-left:4px solid #27ae60;">
		<h3>Total Paid</h3>
		<p>N$ <?= number_format((float) ($totals['paid'] ?? 0), 2) ?></p>
	</section>
	<section class="module-card" style="border-left:4px solid #e74c3c;">
		<h3>Outstanding</h3>
		<p>N$ <?= number_format((float) ($totals['outstanding'] ?? 0), 2) ?></p>
	</section>
</div>
<?php endif; ?>

<table class="table">
	<thead>
		<tr><th>Invoice #</th><th>Invoice No</th><th>Amount</th><th>Status</th><th>Issue Date</th><th>Due Date</th></tr>
	</thead>
	<tbody>
	<?php if (($rows ?? []) === []): ?>
		<tr><td colspan="6"><?= empty($selected_id) ? 'Select a customer to view their statement.' : 'No invoices found for this customer.' ?></td></tr>
	<?php else: foreach ($rows as $r): ?>
		<tr>
			<td><?= (int) $r['invoice_id'] ?></td>
			<td><?= htmlspecialchars($r['invoice_no'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
			<td>N$ <?= number_format((float) $r['amount'], 2) ?></td>
			<td>
				<?php
					$st = $r['status'] ?? '';
					$stColor = match($st) {
						'paid' => '#27ae60',
						'overdue' => '#e74c3c',
						'issued' => '#2980b9',
						'cancelled' => '#95a5a6',
						default => '#7f8c8d',
					};
				?>
				<span style="background:<?= $stColor ?>;color:#fff;padding:2px 8px;border-radius:10px;font-size:0.85em;"><?= htmlspecialchars($st, ENT_QUOTES, 'UTF-8') ?></span>
			</td>
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
