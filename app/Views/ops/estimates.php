<!doctype html>
<html lang="en">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Estimates</title>
<link rel="stylesheet" href="/assets/css/legacy-style.css"><link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>
<main class="card card-wide">
<h1>Estimates</h1>
<?php
$exportQuery = http_build_query(array_filter([
	'q' => (string) ($search ?? ''),
	'status' => (string) ($status_filter ?? ''),
	'from' => (string) ($from_date ?? ''),
	'to' => (string) ($to_date ?? ''),
], static fn($v): bool => $v !== ''));
$exportSuffix = $exportQuery !== '' ? ('?' . $exportQuery) : '';
?>
<div class="button-group">
	<a class="btn btn-secondary" href="/estimates/export/csv<?= htmlspecialchars($exportSuffix, ENT_QUOTES, 'UTF-8') ?>">Export CSV</a>
	<a class="btn btn-secondary" href="/estimates/export/pdf<?= htmlspecialchars($exportSuffix, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener">Export PDF</a>
</div>
<form method="get" action="/estimates" style="display:flex;gap:10px;align-items:end;flex-wrap:wrap;margin:10px 0 14px;">
	<div>
		<label>Search Estimates</label>
		<input type="text" name="q" value="<?= htmlspecialchars($search ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="Estimate ID, client, status, dates">
	</div>
	<div>
		<label>Status</label>
		<select name="status">
			<option value="">All</option>
			<?php foreach (['draft','sent','accepted','rejected','expired'] as $statusOpt): ?>
				<option value="<?= $statusOpt ?>" <?= (($status_filter ?? '') === $statusOpt) ? 'selected' : '' ?>><?= ucfirst($statusOpt) ?></option>
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
	<button type="submit" class="btn btn-primary">Search</button>
	<?php if (!empty($search) || !empty($status_filter) || !empty($from_date) || !empty($to_date)): ?><a class="btn btn-secondary" href="/estimates">Clear</a><?php endif; ?>
</form>
<?php foreach (($errors ?? []) as $error): ?><p class="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p><?php endforeach; ?>

<form method="post" action="/estimates">
	<input type="hidden" name="_token" value="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>">

	<label>Customer</label>
	<select name="customer_id" id="est_customer_id" onchange="fillEstimateClient(this)">
		<option value="">Select customer...</option>
		<?php foreach (($customers ?? []) as $c): ?>
			<option value="<?= (int) $c['customer_id'] ?>"
				data-name="<?= htmlspecialchars($c['customer_name'], ENT_QUOTES, 'UTF-8') ?>"
				<?= ((string) ($old['customer_id'] ?? '') === (string) $c['customer_id']) ? 'selected' : '' ?>>
				<?= htmlspecialchars($c['customer_name'], ENT_QUOTES, 'UTF-8') ?>
			</option>
		<?php endforeach; ?>
	</select>

	<label>Client Name <small>(auto-filled from customer above, or enter manually)</small></label>
	<input name="client_name" id="est_client_name" value="<?= htmlspecialchars($old['client_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>

	<label>Product/Service (optional)</label>
	<select name="product_id" id="est_product_id" onchange="fillEstimateAmountFromProduct()">
		<option value="">Select product/service...</option>
		<?php foreach (($products ?? []) as $p): ?>
			<option value="<?= (int) $p['product_id'] ?>" data-price="<?= htmlspecialchars((string) ($p['unit_price'] ?? '0'), ENT_QUOTES, 'UTF-8') ?>" data-description="<?= htmlspecialchars((string) ($p['description'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" <?= ((string) ($old['product_id'] ?? '') === (string) $p['product_id']) ? 'selected' : '' ?>>
				<?= htmlspecialchars($p['product_name'], ENT_QUOTES, 'UTF-8') ?>
			</option>
		<?php endforeach; ?>
	</select>
	<p class="hint" id="est_product_description_hint">Select a product or service to preview the description that will appear on the estimate and converted invoice.</p>

	<label>Quantity</label>
	<input name="quantity" id="est_quantity" type="number" min="1" step="1" value="<?= htmlspecialchars($old['quantity'] ?? '1', ENT_QUOTES, 'UTF-8') ?>">

	<label>Amount</label>
	<input name="amount" id="est_amount" type="number" min="0.01" step="0.01" value="<?= htmlspecialchars($old['amount'] ?? '0.00', ENT_QUOTES, 'UTF-8') ?>" required>

	<label>Estimate Date</label>
	<input name="estimate_date" type="date" value="<?= htmlspecialchars($old['estimate_date'] ?? date('Y-m-d'), ENT_QUOTES, 'UTF-8') ?>" required>

	<label>Expiry Date</label>
	<input name="expiry_date" type="date" value="<?= htmlspecialchars($old['expiry_date'] ?? date('Y-m-d'), ENT_QUOTES, 'UTF-8') ?>" required>

	<label>Status</label>
	<select name="status">
		<?php foreach (['draft','sent','accepted','rejected','expired'] as $s): ?>
			<option value="<?= $s ?>" <?= (($old['status'] ?? 'draft') === $s) ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
		<?php endforeach; ?>
	</select>

	<button type="submit">Add Estimate</button>
</form>

<table class="table">
	<thead>
		<tr><th>ID</th><th>Client</th><th>Product</th><th>Qty</th><th>Amount</th><th>Estimate Date</th><th>Expiry</th><th>Status</th><th>Actions</th></tr>
	</thead>
	<tbody>
	<?php if (($rows ?? []) === []): ?>
		<tr><td colspan="9">No estimates yet.</td></tr>
	<?php else: foreach ($rows as $r): ?>
		<tr>
			<td><?= (int) $r['estimate_id'] ?></td>
			<td><?= htmlspecialchars($r['client_name'], ENT_QUOTES, 'UTF-8') ?></td>
			<td><?= htmlspecialchars((string) ($r['product_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
			<td><?= ($r['quantity'] ?? null) !== null ? (int) $r['quantity'] : '' ?></td>
			<td><?= number_format((float) $r['amount'], 2) ?></td>
			<td><?= htmlspecialchars($r['estimate_date'], ENT_QUOTES, 'UTF-8') ?></td>
			<td><?= htmlspecialchars($r['expiry_date'], ENT_QUOTES, 'UTF-8') ?></td>
			<td><?= htmlspecialchars($r['status'], ENT_QUOTES, 'UTF-8') ?></td>
			<td>
				<a href="/estimates/view?estimate_id=<?= (int) $r['estimate_id'] ?>">View</a> |
				<a href="/estimates/print?estimate_id=<?= (int) $r['estimate_id'] ?>" target="_blank" rel="noopener">Print</a> |
				<a href="/estimates/edit?estimate_id=<?= (int) $r['estimate_id'] ?>">Edit</a> |
				<?php if ((int) ($r['converted_invoice_id'] ?? 0) > 0): ?>
					<a href="/invoices/view?invoice_id=<?= (int) $r['converted_invoice_id'] ?>">View Invoice</a> |
				<?php elseif (($r['status'] ?? '') === 'accepted'): ?>
					<form method="post" action="/estimates/convert-to-invoice" class="inline-form" onsubmit="return confirm('Convert this accepted estimate into an invoice?');">
						<input type="hidden" name="_token" value="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>">
						<input type="hidden" name="estimate_id" value="<?= (int) $r['estimate_id'] ?>">
						<button type="submit">Convert to Invoice</button>
					</form>
					|
				<?php endif; ?>
				<form method="post" action="/estimates/delete" class="inline-form" onsubmit="return confirm('Delete this estimate?');">
					<input type="hidden" name="_token" value="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>">
					<input type="hidden" name="estimate_id" value="<?= (int) $r['estimate_id'] ?>">
					<button type="submit">Delete</button>
				</form>
			</td>
		</tr>
	<?php endforeach; endif; ?>
	</tbody>
</table>
<p><a href="/dashboard">Back to Dashboard</a></p>
</main>
<script>
function fillEstimateClient(sel) {
	var opt = sel.options[sel.selectedIndex];
	var nameField = document.getElementById('est_client_name');
	if (opt && opt.dataset.name) {
		nameField.value = opt.dataset.name;
	}
}

function fillEstimateAmountFromProduct() {
	var select = document.getElementById('est_product_id');
	var qtyField = document.getElementById('est_quantity');
	var amountField = document.getElementById('est_amount');
	var descriptionHint = document.getElementById('est_product_description_hint');
	if (!select || !amountField) return;
	var opt = select.options[select.selectedIndex];
	if (opt && opt.dataset && opt.dataset.price && opt.value !== '') {
		var qty = parseInt((qtyField && qtyField.value) ? qtyField.value : '1', 10);
		if (!isFinite(qty) || qty <= 0) qty = 1;
		amountField.value = (parseFloat(opt.dataset.price || '0') * qty).toFixed(2);
		if (descriptionHint) {
			descriptionHint.textContent = opt.dataset.description && opt.dataset.description.trim() !== ''
				? opt.dataset.description
				: 'No item description saved for this product yet. Add one in Products to show scope/details on printed estimates.';
		}
		return;
	}
	if (select.value === '') {
		if (descriptionHint) {
			descriptionHint.textContent = 'Select a product or service to preview the description that will appear on the estimate and converted invoice.';
		}
	}
}
var estQty = document.getElementById('est_quantity');
if (estQty) {
	estQty.addEventListener('input', fillEstimateAmountFromProduct);
}
fillEstimateAmountFromProduct();
</script>
</body>
</html>
