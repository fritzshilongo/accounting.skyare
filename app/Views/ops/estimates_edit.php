<!doctype html>
<html lang="en">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Edit Estimate</title>
<link rel="stylesheet" href="/assets/css/legacy-style.css"><link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>
<main class="card">
<h1>Edit Estimate</h1>
<?php foreach (($errors ?? []) as $error): ?><p class="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p><?php endforeach; ?>

<form method="post" action="/estimates/update">
	<input type="hidden" name="_token" value="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>">
	<input type="hidden" name="estimate_id" value="<?= (int) $row['estimate_id'] ?>">

	<label>Customer</label>
	<select name="customer_id" id="edit_customer_id" onchange="fillEditClient(this)">
		<option value="">Select customer...</option>
		<?php foreach (($customers ?? []) as $c): ?>
			<option value="<?= (int) $c['customer_id'] ?>"
				data-name="<?= htmlspecialchars($c['customer_name'], ENT_QUOTES, 'UTF-8') ?>"
				<?= ((string) ($row['customer_id'] ?? '') === (string) $c['customer_id']) ? 'selected' : '' ?>>
				<?= htmlspecialchars($c['customer_name'], ENT_QUOTES, 'UTF-8') ?>
			</option>
		<?php endforeach; ?>
	</select>

	<label>Client Name <small>(auto-filled from customer above, or enter manually)</small></label>
	<input name="client_name" id="edit_client_name" value="<?= htmlspecialchars($row['client_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>

	<label>Product/Service (optional)</label>
	<select name="product_id" id="edit_product_id" onchange="fillEditAmountFromProduct()">
		<option value="">Select product/service...</option>
		<?php foreach (($products ?? []) as $p): ?>
			<option value="<?= (int) $p['product_id'] ?>" data-price="<?= htmlspecialchars((string) ($p['unit_price'] ?? '0'), ENT_QUOTES, 'UTF-8') ?>" data-description="<?= htmlspecialchars((string) ($p['description'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" <?= ((string) ($row['product_id'] ?? '') === (string) $p['product_id']) ? 'selected' : '' ?>>
				<?= htmlspecialchars($p['product_name'], ENT_QUOTES, 'UTF-8') ?>
			</option>
		<?php endforeach; ?>
	</select>
	<p class="hint" id="edit_product_description_hint" style="white-space:pre-line;"><?= htmlspecialchars(!empty($row['line_description']) ? (string) $row['line_description'] : 'Select a product or service to preview the description that will appear on the estimate and converted invoice.', ENT_QUOTES, 'UTF-8') ?></p>

	<label>Quantity</label>
	<input name="quantity" id="edit_quantity" type="number" min="1" step="1" value="<?= htmlspecialchars((string) ($row['quantity'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
	<?php if (($row['product_id'] ?? null) === null && ($row['quantity'] ?? null) === null): ?>
	<p class="hint">Legacy estimate line details were not previously stored. Confirm the product, quantity, and amount before saving.</p>
	<?php endif; ?>

	<label>Amount</label>
	<input name="amount" id="edit_amount" type="number" min="0.01" step="0.01" value="<?= htmlspecialchars((string) ($row['amount'] ?? '0.00'), ENT_QUOTES, 'UTF-8') ?>" required>

	<label>Estimate Date</label>
	<input name="estimate_date" type="date" value="<?= htmlspecialchars($row['estimate_date'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>

	<label>Expiry Date</label>
	<input name="expiry_date" type="date" value="<?= htmlspecialchars($row['expiry_date'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>

	<label>Status</label>
	<select name="status">
		<?php foreach (['draft','sent','accepted','rejected','expired'] as $s): ?>
			<option value="<?= $s ?>" <?= (($row['status'] ?? 'draft') === $s) ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
		<?php endforeach; ?>
	</select>

	<button type="submit">Update Estimate</button>
</form>
<p><a href="/estimates">Back to Estimates</a></p>
</main>
<script>
function fillEditClient(sel) {
	var opt = sel.options[sel.selectedIndex];
	var nameField = document.getElementById('edit_client_name');
	if (opt && opt.dataset.name) {
		nameField.value = opt.dataset.name;
	}
}

function fillEditAmountFromProduct() {
	var select = document.getElementById('edit_product_id');
	var qtyField = document.getElementById('edit_quantity');
	var amountField = document.getElementById('edit_amount');
	var descriptionHint = document.getElementById('edit_product_description_hint');
	if (!select || !amountField) return;
	var opt = select.options[select.selectedIndex];
	if (opt && opt.dataset && opt.dataset.price && opt.value !== '') {
		var qty = parseInt((qtyField && qtyField.value) ? qtyField.value : '', 10);
		if (isFinite(qty) && qty > 0) {
			amountField.value = (parseFloat(opt.dataset.price || '0') * qty).toFixed(2);
		}
		if (descriptionHint) {
			descriptionHint.textContent = opt.dataset.description && opt.dataset.description.trim() !== ''
				? opt.dataset.description
				: 'No item description saved for this product yet. Add one in Products to show scope/details on printed estimates.';
		}
		return;
	}
	if (descriptionHint) {
		descriptionHint.textContent = 'Select a product or service to preview the description that will appear on the estimate and converted invoice.';
	}
}
var editQty = document.getElementById('edit_quantity');
if (editQty) {
	editQty.addEventListener('input', fillEditAmountFromProduct);
}
if (editQty && editQty.value !== '') {
	fillEditAmountFromProduct();
}
</script>
</body>
</html>
