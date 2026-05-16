<!doctype html>
<html lang="en">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Products</title><link rel="stylesheet" href="/assets/css/legacy-style.css"><link rel="stylesheet" href="/assets/css/app.css"></head>
<body>
<main class="card card-wide">
<h1>Products</h1>
<div class="button-group"><a class="btn btn-secondary" href="/products/export/csv">Export CSV</a><a class="btn btn-secondary" href="/products/export/pdf" target="_blank" rel="noopener">Export PDF</a></div>
<p>Company: <?= htmlspecialchars($company['company_name'] ?? 'Unknown', ENT_QUOTES, 'UTF-8') ?></p>
<p class="hint">Stock adjustments are managed in <a href="/inventory">Inventory</a> only. Product form does not change stock balances.</p>
<?php foreach (($errors ?? []) as $error): ?><p class="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p><?php endforeach; ?>

<form method="post" action="/products">
	<input type="hidden" name="_token" value="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>">
	<label>SKU</label><input name="sku" value="<?= htmlspecialchars($old['sku'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
	<label>Product/Service Name</label><input name="product_name" value="<?= htmlspecialchars($old['product_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
	<label>Description / Scope</label><textarea name="description" rows="4" placeholder="Example: Web layout, 5 pages, contact form, mobile responsive."><?= htmlspecialchars($old['description'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
	<label>Unit Price</label><input name="unit_price" type="number" min="0" step="0.01" value="<?= htmlspecialchars($old['unit_price'] ?? '0.00', ENT_QUOTES, 'UTF-8') ?>" required>
	<label>Stock Control</label>
	<select name="stock_control_type">
		<option value="STOCK_CONTROLLED" <?= (($old['stock_control_type'] ?? 'STOCK_CONTROLLED') === 'STOCK_CONTROLLED') ? 'selected' : '' ?>>Stock Controlled (physical goods)</option>
		<option value="STOCK_NOT_CONTROLLED" <?= (($old['stock_control_type'] ?? 'STOCK_CONTROLLED') === 'STOCK_NOT_CONTROLLED') ? 'selected' : '' ?>>Non Stock Controlled (services)</option>
	</select>
	<label>Status</label><select name="is_active"><option value="1" <?= (($old['is_active'] ?? '1') === '1') ? 'selected' : '' ?>>Active</option><option value="0" <?= (($old['is_active'] ?? '1') === '0') ? 'selected' : '' ?>>Inactive</option></select>
	<button type="submit">Add Product</button>
</form>

<table class="table">
	<thead><tr><th>ID</th><th>SKU</th><th>Name</th><th>Description</th><th>Type</th><th>Price</th><th>Stock</th><th>Status</th><th>Actions</th></tr></thead>
	<tbody>
	<?php if (($rows ?? []) === []): ?>
		<tr><td colspan="9">No products yet.</td></tr>
	<?php else: foreach ($rows as $row): ?>
		<tr>
			<td><?= (int) $row['product_id'] ?></td>
			<td><?= htmlspecialchars((string) ($row['sku'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
			<td><?= htmlspecialchars($row['product_name'], ENT_QUOTES, 'UTF-8') ?></td>
			<td><?= nl2br(htmlspecialchars((string) ($row['description'] ?? ''), ENT_QUOTES, 'UTF-8')) ?></td>
			<td><?= ((string) ($row['stock_control_type'] ?? 'STOCK_CONTROLLED') === 'STOCK_NOT_CONTROLLED') ? 'Service' : 'Stock Item' ?></td>
			<td><?= number_format((float) $row['unit_price'], 2) ?></td>
			<td>
				<?php if ((string) ($row['stock_control_type'] ?? 'STOCK_CONTROLLED') === 'STOCK_NOT_CONTROLLED'): ?>
					N/A
				<?php else: ?>
					<?= number_format((float) $row['stock_qty'], 2) ?>
				<?php endif; ?>
			</td>
			<td><?= ((int) $row['is_active'] === 1) ? 'Active' : 'Inactive' ?></td>
			<td><a href="/products/edit?product_id=<?= (int) $row['product_id'] ?>">Edit</a> | <form method="post" action="/products/delete" class="inline-form" onsubmit="return confirm('Delete this product?');"><input type="hidden" name="_token" value="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>"><input type="hidden" name="product_id" value="<?= (int) $row['product_id'] ?>"><button type="submit">Delete</button></form></td>
		</tr>
	<?php endforeach; endif; ?>
	</tbody>
</table>
<p><a href="/dashboard">Back to Dashboard</a></p>
</main>
</body>
</html>
