<!doctype html>
<html lang="en">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Inventory</title>
<link rel="stylesheet" href="/assets/css/legacy-style.css"><link rel="stylesheet" href="/assets/css/app.css">
<style>
.movement-badge { display: inline-block; padding: 2px 8px; border-radius: 12px; font-size: .8rem; font-weight: 600; }
.badge-in        { background: #e8f5e9; color: #2e7d32; }
.badge-out       { background: #fce4ec; color: #c62828; }
.badge-sold      { background: #fff3e0; color: #e65100; }
.badge-returned  { background: #e3f2fd; color: #1565c0; }
.badge-destroyed { background: #f3e5f5; color: #6a1b9a; }
.badge-adjustment{ background: #f5f5f5; color: #424242; }
</style>
</head>
<body>
<main class="card card-wide">
<h1>Inventory Movements</h1>
<div class="button-group">
	<a class="btn btn-primary" href="/inventory/audit">&#128202; Stock Audit &amp; History</a>
	<a class="btn btn-secondary" href="/inventory/export/csv">Export CSV</a>
	<a class="btn btn-secondary" href="/inventory/export/pdf" target="_blank" rel="noopener">Export PDF</a>
</div>
<p>Company: <?= htmlspecialchars($company['company_name'] ?? 'Unknown', ENT_QUOTES, 'UTF-8') ?></p>
<?php foreach (($errors ?? []) as $error): ?><p class="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p><?php endforeach; ?>

<form method="post" action="/inventory/move">
	<input type="hidden" name="_token" value="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>">
	<label>Product</label>
	<select name="product_id" required>
		<option value="">Select product...</option>
		<?php foreach (($products ?? []) as $p): ?>
			<option value="<?= (int) $p['product_id'] ?>"><?= htmlspecialchars($p['product_name'], ENT_QUOTES, 'UTF-8') ?> (Stock: <?= number_format((float) $p['stock_qty'], 2) ?>)</option>
		<?php endforeach; ?>
	</select>
	<label>Movement Type</label>
	<select name="movement_type" required>
		<option value="in">Stock In (Goods Received)</option>
		<option value="out">Stock Out (Manual Removal)</option>
		<option value="sold">Sold</option>
		<option value="returned">Returned by Customer</option>
		<option value="destroyed">Destroyed / Written Off</option>
		<option value="adjustment">Adjustment</option>
	</select>
	<label>Quantity</label>
	<input name="quantity" type="number" min="0.01" step="0.01" required>
	<label>Note</label>
	<input name="note">
	<button type="submit">Save Movement</button>
</form>

<table class="table">
	<thead>
		<tr>
			<th>ID</th><th>Product</th><th>Type</th><th>Qty Changed</th>
			<th>Before</th><th>After</th><th>Note</th><th>By</th><th>Date</th>
		</tr>
	</thead>
	<tbody>
	<?php if (($rows ?? []) === []): ?>
		<tr><td colspan="9">No movements yet.</td></tr>
	<?php else: foreach ($rows as $row):
		$typeLabels = [
			'in' => 'Stock In', 'out' => 'Stock Out', 'sold' => 'Sold',
			'returned' => 'Returned', 'destroyed' => 'Destroyed', 'adjustment' => 'Adjustment',
		];
		$badgeClass = 'badge-' . htmlspecialchars($row['movement_type'], ENT_QUOTES, 'UTF-8');
	?>
		<tr>
			<td><?= (int) $row['movement_id'] ?></td>
			<td><?= htmlspecialchars($row['product_name'], ENT_QUOTES, 'UTF-8') ?></td>
			<td><span class="movement-badge <?= $badgeClass ?>"><?= htmlspecialchars($typeLabels[$row['movement_type']] ?? $row['movement_type'], ENT_QUOTES, 'UTF-8') ?></span></td>
			<td><?= number_format((float) $row['quantity'], 2) ?></td>
			<td><?= number_format((float) ($row['qty_before'] ?? 0), 2) ?></td>
			<td><?= number_format((float) ($row['qty_after'] ?? 0), 2) ?></td>
			<td><?= htmlspecialchars((string) ($row['note'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
			<td><?= htmlspecialchars((string) ($row['actor_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
			<td><?= htmlspecialchars($row['created_at'], ENT_QUOTES, 'UTF-8') ?></td>
		</tr>
	<?php endforeach; endif; ?>
	</tbody>
</table>
<p><a href="/dashboard">Back to Dashboard</a></p>
</main>
</body>
</html>
