<!doctype html>
<html lang="en">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Edit Customer</title>
<link rel="stylesheet" href="/assets/css/legacy-style.css"><link rel="stylesheet" href="/assets/css/app.css">
<style>.company-fields { display: none; }</style>
</head>
<body>
<main class="card">
<h1>Edit Customer</h1>
<?php foreach (($errors ?? []) as $error): ?><p class="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p><?php endforeach; ?>

<form method="post" action="/customers/update">
    <input type="hidden" name="_token" value="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>">
    <input type="hidden" name="customer_id" value="<?= (int) $row['customer_id'] ?>">

    <label>Customer Type</label>
    <select name="customer_type" id="ctype" onchange="toggleCompanyFields(this.value)">
        <option value="individual" <?= (($row['customer_type'] ?? 'individual') === 'individual') ? 'selected' : '' ?>>Individual</option>
        <option value="company" <?= (($row['customer_type'] ?? 'individual') === 'company') ? 'selected' : '' ?>>Company</option>
    </select>

    <label>Full Name / Contact Name <span style="color:red">*</span></label>
    <input name="customer_name" value="<?= htmlspecialchars($row['customer_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>

    <div class="company-fields" id="company-fields">
        <label>Company Name</label>
        <input name="company_name" value="<?= htmlspecialchars((string) ($row['company_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">

        <label>Registration Number</label>
        <input name="registration_number" value="<?= htmlspecialchars((string) ($row['registration_number'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">

        <label>Tax Number</label>
        <input name="tax_number" value="<?= htmlspecialchars((string) ($row['tax_number'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
    </div>

    <label>ID Number</label>
    <input name="id_number" value="<?= htmlspecialchars((string) ($row['id_number'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">

    <label>Email</label>
    <input name="email" type="email" value="<?= htmlspecialchars((string) ($row['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">

    <label>Phone</label>
    <input name="phone" value="<?= htmlspecialchars((string) ($row['phone'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">

    <label>Address</label>
    <textarea name="address" rows="2"><?= htmlspecialchars((string) ($row['address'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>

    <label>Notes</label>
    <textarea name="notes" rows="2"><?= htmlspecialchars((string) ($row['notes'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>

    <label>Credit Limit</label>
    <input name="credit_limit" type="number" min="0" step="0.01" value="<?= htmlspecialchars((string) ($row['credit_limit'] ?? '0.00'), ENT_QUOTES, 'UTF-8') ?>">

    <label>Status</label>
    <select name="is_active">
        <option value="1" <?= ((int) ($row['is_active'] ?? 1) === 1) ? 'selected' : '' ?>>Active</option>
        <option value="0" <?= ((int) ($row['is_active'] ?? 1) === 0) ? 'selected' : '' ?>>Inactive</option>
    </select>

    <button type="submit">Update Customer</button>
</form>
<p><a href="/customers">Back to customers</a></p>
</main>
<script>
function toggleCompanyFields(type) {
    document.getElementById('company-fields').style.display = (type === 'company') ? 'block' : 'none';
}
toggleCompanyFields(document.getElementById('ctype').value);
</script>
</body>
</html>
