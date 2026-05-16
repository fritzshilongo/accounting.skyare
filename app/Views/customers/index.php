<!doctype html>
<html lang="en">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Customers</title>
<link rel="stylesheet" href="/assets/css/legacy-style.css"><link rel="stylesheet" href="/assets/css/app.css">
<style>
.company-fields { display: none; }
.customer-type-badge { display: inline-block; padding: 2px 8px; border-radius: 12px; font-size: .8rem; font-weight: 600; }
.badge-individual { background: #e3f2fd; color: #1565c0; }
.badge-company { background: #f3e5f5; color: #6a1b9a; }
</style>
</head>
<body>
<main class="card card-wide">
<h1>Customers</h1>
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
    <a class="btn btn-secondary" href="/customers/export/csv<?= htmlspecialchars($exportSuffix, ENT_QUOTES, 'UTF-8') ?>">Export CSV</a>
    <a class="btn btn-secondary" href="/customers/export/pdf<?= htmlspecialchars($exportSuffix, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener">Export PDF</a>
</div>
<p>Company: <?= htmlspecialchars($company['company_name'] ?? 'Unknown', ENT_QUOTES, 'UTF-8') ?></p>
<form method="get" action="/customers" style="display:flex;gap:10px;align-items:end;flex-wrap:wrap;margin:10px 0 16px;">
    <div>
        <label>Search Customers</label>
        <input type="text" name="q" value="<?= htmlspecialchars($search ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="Name, company, email, phone, ID number">
    </div>
    <div>
        <label>Status</label>
        <select name="status">
            <option value="">All</option>
            <option value="active" <?= (($status_filter ?? '') === 'active') ? 'selected' : '' ?>>Active</option>
            <option value="inactive" <?= (($status_filter ?? '') === 'inactive') ? 'selected' : '' ?>>Inactive</option>
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
    <?php if (!empty($search) || !empty($status_filter) || !empty($from_date) || !empty($to_date)): ?><a class="btn btn-secondary" href="/customers">Clear</a><?php endif; ?>
</form>
<?php foreach (($errors ?? []) as $error): ?><p class="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p><?php endforeach; ?>

<form method="post" action="/customers">
    <input type="hidden" name="_token" value="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>">

    <label>Customer Type</label>
    <select name="customer_type" id="ctype" onchange="toggleCompanyFields(this.value)">
        <option value="individual" <?= (($old['customer_type'] ?? 'individual') === 'individual') ? 'selected' : '' ?>>Individual</option>
        <option value="company" <?= (($old['customer_type'] ?? 'individual') === 'company') ? 'selected' : '' ?>>Company</option>
    </select>

    <label>Full Name / Contact Name <span style="color:red">*</span></label>
    <input name="customer_name" value="<?= htmlspecialchars($old['customer_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>

    <div class="company-fields" id="company-fields">
        <label>Company Name</label>
        <input name="company_name" value="<?= htmlspecialchars($old['company_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>">

        <label>Registration Number</label>
        <input name="registration_number" value="<?= htmlspecialchars($old['registration_number'] ?? '', ENT_QUOTES, 'UTF-8') ?>">

        <label>Tax Number</label>
        <input name="tax_number" value="<?= htmlspecialchars($old['tax_number'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
    </div>

    <label>ID Number</label>
    <input name="id_number" value="<?= htmlspecialchars($old['id_number'] ?? '', ENT_QUOTES, 'UTF-8') ?>">

    <label>Email</label>
    <input name="email" type="email" value="<?= htmlspecialchars($old['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>">

    <label>Phone</label>
    <input name="phone" value="<?= htmlspecialchars($old['phone'] ?? '', ENT_QUOTES, 'UTF-8') ?>">

    <label>Address</label>
    <textarea name="address" rows="2"><?= htmlspecialchars($old['address'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>

    <label>Notes</label>
    <textarea name="notes" rows="2"><?= htmlspecialchars($old['notes'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>

    <label>Credit Limit</label>
    <input name="credit_limit" type="number" min="0" step="0.01" value="<?= htmlspecialchars($old['credit_limit'] ?? '0.00', ENT_QUOTES, 'UTF-8') ?>">

    <label>Status</label>
    <select name="is_active">
        <option value="1" <?= (($old['is_active'] ?? '1') === '1') ? 'selected' : '' ?>>Active</option>
        <option value="0" <?= (($old['is_active'] ?? '1') === '0') ? 'selected' : '' ?>>Inactive</option>
    </select>

    <button type="submit">Add Customer</button>
</form>

<table class="table">
    <thead>
        <tr><th>ID</th><th>Type</th><th>Name</th><th>Company</th><th>Email</th><th>Phone</th><th>Credit Limit</th><th>Status</th><th>Actions</th></tr>
    </thead>
    <tbody>
    <?php if (($rows ?? []) === []): ?>
        <tr><td colspan="9">No customers yet.</td></tr>
    <?php else: foreach ($rows as $row): ?>
        <tr>
            <td><?= (int) $row['customer_id'] ?></td>
            <td><span class="customer-type-badge <?= $row['customer_type'] === 'company' ? 'badge-company' : 'badge-individual' ?>"><?= ucfirst(htmlspecialchars($row['customer_type'] ?? 'individual', ENT_QUOTES, 'UTF-8')) ?></span></td>
            <td><?= htmlspecialchars($row['customer_name'], ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars((string) ($row['company_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars((string) ($row['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars((string) ($row['phone'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= number_format((float) $row['credit_limit'], 2) ?></td>
            <td><?= ((int) $row['is_active'] === 1) ? 'Active' : 'Inactive' ?></td>
            <td>
                <a href="/customers/edit?customer_id=<?= (int) $row['customer_id'] ?>">Edit</a>
                |
                <form method="post" action="/customers/delete" class="inline-form" onsubmit="return confirm('Delete this customer?');">
                    <input type="hidden" name="_token" value="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="customer_id" value="<?= (int) $row['customer_id'] ?>">
                    <button type="submit">Delete</button>
                </form>
            </td>
        </tr>
    <?php endforeach; endif; ?>
    </tbody>
</table>
<p><a href="/customer-statement">Customer Statement</a> | <a href="/dashboard">Back to Dashboard</a></p>
</main>
<script>
function toggleCompanyFields(type) {
    document.getElementById('company-fields').style.display = (type === 'company') ? 'block' : 'none';
}
// On load
toggleCompanyFields(document.getElementById('ctype').value);
</script>
</body>
</html>
