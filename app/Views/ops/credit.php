<!doctype html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width,initial-scale=1">
	<title>Credit Management - Skyare Accounting</title>
	<link rel="stylesheet" href="/assets/css/legacy-style.css">
	<link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>
<div class="container">
	<a href="/dashboard" class="back-link">&larr; Back to Dashboard</a>
	<div class="module-header"><h2>💳 Credit Management</h2></div>
	<?php
	$exportQuery = http_build_query(array_filter([
		'q' => (string) ($search ?? ''),
		'status' => (string) ($status_filter ?? ''),
		'from' => (string) ($from_date ?? ''),
		'to' => (string) ($to_date ?? ''),
	], static fn($v): bool => $v !== ''));
	$exportSuffix = $exportQuery !== '' ? ('?' . $exportQuery) : '';
	?>

	<?php foreach (($errors ?? []) as $error): ?>
		<p class="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
	<?php endforeach; ?>
	<?php if (($payment_error ?? '') === '1'): ?>
		<p class="alert">Payment could not be recorded. Check that credit is active and payment amount does not exceed outstanding balance.</p>
	<?php endif; ?>

	<div class="credit-stats" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:15px;margin-bottom:25px;">
		<div class="stat-card issued" style="background:linear-gradient(135deg,#f093fb,#f5576c);color:#fff;padding:18px;border-radius:8px;text-align:center;">
			<div class="stat-label">Total Issued</div>
			<div class="stat-value">N$ <?= number_format((float) ($summary['issued'] ?? 0), 2) ?></div>
		</div>
		<div class="stat-card paid" style="background:linear-gradient(135deg,#4facfe,#00f2fe);color:#fff;padding:18px;border-radius:8px;text-align:center;">
			<div class="stat-label">Total Paid</div>
			<div class="stat-value">N$ <?= number_format((float) ($summary['paid'] ?? 0), 2) ?></div>
		</div>
		<div class="stat-card outstanding" style="background:linear-gradient(135deg,#43e97b,#38f9d7);color:#fff;padding:18px;border-radius:8px;text-align:center;">
			<div class="stat-label">Outstanding Balance</div>
			<div class="stat-value">N$ <?= number_format((float) ($summary['outstanding'] ?? 0), 2) ?></div>
		</div>
	</div>

	<div class="button-group" style="margin-bottom:10px;">
		<a class="btn btn-secondary" href="/credit-management/export/csv<?= htmlspecialchars($exportSuffix, ENT_QUOTES, 'UTF-8') ?>">Export CSV</a>
		<a class="btn btn-secondary" href="/credit-management/export/pdf<?= htmlspecialchars($exportSuffix, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener">Export PDF</a>
	</div>
	<form method="get" action="/credit-management" style="display:flex;gap:10px;align-items:flex-end;flex-wrap:wrap;margin:8px 0 14px;">
		<div>
			<label>Search Credits</label>
			<input type="text" name="q" value="<?= htmlspecialchars($search ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="Credit no, customer, status, due date">
		</div>
		<div>
			<label>Status</label>
			<select name="status">
				<option value="">All</option>
				<?php foreach (['ACTIVE', 'OVERDUE', 'PAID', 'BAD_DEBT'] as $statusOpt): ?>
					<option value="<?= $statusOpt ?>" <?= (mb_strtolower((string) ($status_filter ?? '')) === mb_strtolower($statusOpt)) ? 'selected' : '' ?>><?= htmlspecialchars($statusOpt, ENT_QUOTES, 'UTF-8') ?></option>
				<?php endforeach; ?>
			</select>
		</div>
		<div>
			<label>From</label>
			<input type="date" name="from" value="<?= htmlspecialchars($from_date ?? '', ENT_QUOTES, 'UTF-8') ?>">
		</div>
		<div>
			<label>To</label>
			<input type="date" name="to" value="<?= htmlspecialchars($to_date ?? '', ENT_QUOTES, 'UTF-8') ?>">
		</div>
		<button class="btn btn-primary" type="submit">Search</button>
		<?php if (!empty($search) || !empty($status_filter) || !empty($from_date) || !empty($to_date)): ?><a class="btn btn-secondary" href="/credit-management">Clear</a><?php endif; ?>
	</form>
	<h3>View Credits</h3>
	<table class="module-table" style="width:100%;border-collapse:collapse;">
		<thead>
		<tr>
			<th>Credit No.</th>
			<th>Customer</th>
			<th>Amount Issued</th>
			<th>Interest %</th>
			<th>Interest Amount</th>
			<th>Total Owed</th>
			<th>Amount Paid</th>
			<th>Outstanding</th>
			<th>Due Date</th>
			<th>Status</th>
			<th>Actions</th>
		</tr>
		</thead>
		<tbody>
		<?php if (($rows ?? []) === []): ?>
			<tr><td colspan="11">No credits issued yet.</td></tr>
		<?php else: foreach (($rows ?? []) as $r): ?>
			<tr>
				<td><?= htmlspecialchars($r['credit_no'], ENT_QUOTES, 'UTF-8') ?></td>
				<td><?= htmlspecialchars($r['customer_name'], ENT_QUOTES, 'UTF-8') ?></td>
				<td>N$ <?= number_format((float) $r['amount'], 2) ?></td>
				<td><?= number_format((float) $r['interest_percent'], 2) ?>%</td>
				<td>N$ <?= number_format((float) $r['interest_amount'], 2) ?></td>
				<td>N$ <?= number_format((float) $r['total_amount'], 2) ?></td>
				<td>N$ <?= number_format((float) $r['amount_paid'], 2) ?></td>
				<td>N$ <?= number_format((float) $r['outstanding'], 2) ?></td>
				<td><?= htmlspecialchars((string) ($r['due_date'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
				<td><?= htmlspecialchars((string) $r['status'], ENT_QUOTES, 'UTF-8') ?></td>
				<td>
					<a class="btn btn-secondary" style="display:inline-block;margin-bottom:4px;" href="/credit-management/view?credit_id=<?= (int) $r['credit_id'] ?>">View</a>
					<a class="btn btn-secondary" style="display:inline-block;margin-bottom:4px;" href="/credit-management/agreement?credit_id=<?= (int) $r['credit_id'] ?>" target="_blank" rel="noopener">Agreement</a>
					<a class="btn btn-primary" style="display:inline-block;margin-bottom:4px;" href="/credit-management?pay_credit_id=<?= (int) $r['credit_id'] ?>#record-payment">Pay</a>
					<?php if ((string) $r['status'] !== 'BAD_DEBT' && (float) $r['outstanding'] > 0): ?>
						<form method="post" action="/credit-management/write-off" class="inline-form" style="display:inline-block;">
							<input type="hidden" name="_token" value="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>">
							<input type="hidden" name="credit_id" value="<?= (int) $r['credit_id'] ?>">
							<input type="hidden" name="write_off_reason" value="Manual write-off">
							<button class="btn btn-danger" type="submit">Write Off</button>
						</form>
					<?php endif; ?>
					<?php if ((string) $r['status'] === 'BAD_DEBT'): ?>
						<form method="post" action="/credit-management/reopen" class="inline-form" style="display:inline-block;">
							<input type="hidden" name="_token" value="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>">
							<input type="hidden" name="credit_id" value="<?= (int) $r['credit_id'] ?>">
							<button class="btn btn-warning" type="submit">Reopen</button>
						</form>
					<?php endif; ?>
				</td>
			</tr>
		<?php endforeach; endif; ?>
		</tbody>
	</table>

	<h3 style="margin-top:24px;">Issue Credit</h3>
	<form method="post" action="/credit-management/issue">
		<input type="hidden" name="_token" value="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>">
		<label>Customer *</label>
		<select name="customer_id" required>
			<option value="">-- Select Customer --</option>
			<?php foreach (($customers ?? []) as $c): ?>
				<option value="<?= (int) $c['customer_id'] ?>"><?= htmlspecialchars($c['customer_name'], ENT_QUOTES, 'UTF-8') ?></option>
			<?php endforeach; ?>
		</select>
		<label>Amount *</label>
		<input type="number" name="amount" step="0.01" min="0.01" required>
		<label>Apply Credit Interest</label>
		<select name="apply_interest" id="apply_interest" onchange="toggleInterestFields()">
			<option value="1" selected>Yes</option>
			<option value="0">No</option>
		</select>
		<div id="interest_fields">
			<label>Interest Type</label>
			<select name="interest_type">
				<option value="flat">Flat (one-time)</option>
				<option value="monthly">Monthly</option>
				<option value="daily">Daily</option>
			</select>
			<label>Interest (%)</label>
			<input type="number" name="interest_percent" step="0.01" min="0" max="100" value="0">
		</div>
		<label>Due Date</label>
		<input type="date" name="due_date">
		<label>Reason *</label>
		<input type="text" name="reason" required>
		<button type="submit" class="btn btn-primary">Issue Credit</button>
	</form>

	<h3 id="record-payment" style="margin-top:24px;">Record Payment</h3>
	<form method="post" action="/credit-management/payment">
		<input type="hidden" name="_token" value="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>">
		<label>Credit</label>
		<select name="credit_id" required>
			<option value="">-- Select Credit --</option>
			<?php foreach (($rows ?? []) as $r): ?>
				<?php if ((float) $r['outstanding'] > 0): ?>
					<option value="<?= (int) $r['credit_id'] ?>" <?= ((int) ($pay_credit_id ?? 0) === (int) $r['credit_id']) ? 'selected' : '' ?>><?= htmlspecialchars($r['credit_no'] . ' - ' . $r['customer_name'] . ' (N$ ' . number_format((float) $r['outstanding'], 2) . ')', ENT_QUOTES, 'UTF-8') ?></option>
				<?php endif; ?>
			<?php endforeach; ?>
		</select>
		<label>Amount</label>
		<input type="number" name="amount" step="0.01" min="0.01" required>
		<label>Payment Date</label>
		<input type="date" name="payment_date" value="<?= date('Y-m-d') ?>" required>
		<label>Payment Method</label>
		<input type="text" name="payment_method" placeholder="Cash, EFT, Card">
		<label>Reference</label>
		<input type="text" name="reference" placeholder="Receipt or reference number">
		<button type="submit" class="btn btn-success">Record Payment</button>
	</form>

	<h3 style="margin-top:24px;">Payment History</h3>
	<table class="module-table" style="width:100%;border-collapse:collapse;">
		<thead>
		<tr>
			<th>Date</th>
			<th>Customer</th>
			<th>Credit ID</th>
			<th>Amount Paid</th>
			<th>Method</th>
			<th>Reference</th>
		</tr>
		</thead>
		<tbody>
		<?php if (($payments ?? []) === []): ?>
			<tr><td colspan="6">No payments recorded yet.</td></tr>
		<?php else: foreach (($payments ?? []) as $p): ?>
			<tr>
				<td><?= htmlspecialchars((string) $p['payment_date'], ENT_QUOTES, 'UTF-8') ?></td>
				<td><?= htmlspecialchars((string) $p['customer_name'], ENT_QUOTES, 'UTF-8') ?></td>
				<td><?= (int) $p['credit_id'] ?></td>
				<td>N$ <?= number_format((float) $p['amount'], 2) ?></td>
				<td><?= htmlspecialchars((string) ($p['payment_method'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
				<td><?= htmlspecialchars((string) ($p['reference'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
			</tr>
		<?php endforeach; endif; ?>
		</tbody>
	</table>
</div>
<script>
function toggleInterestFields() {
	var apply = document.getElementById('apply_interest');
	var fields = document.getElementById('interest_fields');
	if (!apply || !fields) return;
	fields.style.display = (apply.value === '1') ? 'block' : 'none';
}
toggleInterestFields();
</script>
</body>
</html>
