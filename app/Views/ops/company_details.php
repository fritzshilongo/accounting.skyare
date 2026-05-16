<!doctype html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width,initial-scale=1">
	<title>Company Details - Skyare Accounting</title>
	<link rel="stylesheet" href="/assets/css/legacy-style.css">
	<link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>
<nav>
	<h1>Company Details</h1>
	<div style="display:flex;gap:8px;align-items:center;">
		<a href="/company-details/export/csv" class="btn btn-success btn-sm">&#11015; Export CSV</a>
		<a href="/company-details/export/pdf" class="btn btn-info btn-sm" target="_blank">&#11015; Export PDF</a>
		<a href="/dashboard" class="btn btn-secondary">Back to Dashboard</a>
	</div>
</nav>

<div class="container">
	<?php foreach (($errors ?? []) as $error): ?>
		<p class="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
	<?php endforeach; ?>

	<?php $d = $company_details ?? $company ?? []; ?>

	<form method="post" action="/company-details">
		<input type="hidden" name="_token" value="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>">
		<input type="hidden" id="logo_data" name="logo_data" value="<?= htmlspecialchars($d['logo_data'] ?? '', ENT_QUOTES, 'UTF-8') ?>">

		<h3>Company Branding</h3>
		<label>Company Name *</label>
		<input type="text" name="company_name" value="<?= htmlspecialchars($d['company_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
		<label>Registration Number</label>
		<input type="text" name="registration_number" value="<?= htmlspecialchars($d['registration_number'] ?? '', ENT_QUOTES, 'UTF-8') ?>">

		<label>Subdomain</label>
		<input type="text" value="<?= htmlspecialchars($d['subdomain'] ?? '', ENT_QUOTES, 'UTF-8') ?>" disabled>

		<div class="logo-upload-section">
			<label>Company Logo</label>
			<p class="hint">Upload PNG/JPG (max 500KB)</p>
			<button type="button" class="btn btn-primary" onclick="document.getElementById('logo_file').click()">Choose Logo</button>
			<input id="logo_file" type="file" accept="image/png,image/jpeg" style="display:none;">
			<div id="logo_preview" style="margin-top:12px;">
				<?php if (!empty($d['logo_data'])): ?>
					<img src="<?= htmlspecialchars($d['logo_data'], ENT_QUOTES, 'UTF-8') ?>" alt="Company Logo" class="logo" style="max-width:180px;max-height:180px;">
				<?php endif; ?>
			</div>
		</div>

		<h3>Contact Information</h3>
		<label>Phone</label>
		<input name="phone" value="<?= htmlspecialchars($d['phone'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
		<label>Email</label>
		<input type="email" name="email" value="<?= htmlspecialchars($d['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>">

		<h3>Address</h3>
		<label>Street Address</label>
		<input name="address" value="<?= htmlspecialchars($d['address'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
		<label>City</label>
		<input name="city" value="<?= htmlspecialchars($d['city'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
		<label>Province/State</label>
		<input name="province" value="<?= htmlspecialchars($d['province'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
		<label>Postal Code</label>
		<input name="postal_code" value="<?= htmlspecialchars($d['postal_code'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
		<label>Country</label>
		<input name="country" value="<?= htmlspecialchars($d['country'] ?? '', ENT_QUOTES, 'UTF-8') ?>">

		<h3>Tax Information</h3>
		<label>Tax Number</label>
		<input name="tax_number" value="<?= htmlspecialchars($d['tax_number'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
		<label>VAT Number</label>
		<input name="vat_number" value="<?= htmlspecialchars($d['vat_number'] ?? '', ENT_QUOTES, 'UTF-8') ?>">

		<h3>Banking Details</h3>
		<label>Bank Name</label>
		<input name="bank_name" value="<?= htmlspecialchars($d['bank_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
		<label>Account Holder Name</label>
		<input name="bank_account_holder" value="<?= htmlspecialchars($d['bank_account_holder'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
		<label>Account Number</label>
		<input name="bank_account_number" value="<?= htmlspecialchars($d['bank_account_number'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
		<label>Routing Number</label>
		<input name="bank_routing_number" value="<?= htmlspecialchars($d['bank_routing_number'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
		<label>SWIFT Code</label>
		<input name="bank_swift_code" value="<?= htmlspecialchars($d['bank_swift_code'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
		<label>IBAN</label>
		<input name="bank_iban" value="<?= htmlspecialchars($d['bank_iban'] ?? '', ENT_QUOTES, 'UTF-8') ?>">

		<div class="button-group">
			<button class="btn btn-primary" type="submit">Save Company Details</button>
			<a href="/dashboard" class="btn btn-secondary">Cancel</a>
		</div>
	</form>
</div>

<script>
document.getElementById('logo_file').addEventListener('change', function (event) {
	const file = event.target.files[0];
	if (!file) return;
	if (file.size > 500 * 1024) {
		alert('Logo file is too large. Maximum 500KB.');
		event.target.value = '';
		return;
	}
	const reader = new FileReader();
	reader.onload = function (e) {
		const base64 = String(e.target.result || '');
		document.getElementById('logo_data').value = base64;
		document.getElementById('logo_preview').innerHTML = '<img src="' + base64.replace(/"/g, '&quot;') + '" alt="Company Logo" class="logo" style="max-width:180px;max-height:180px;">';
	};
	reader.readAsDataURL(file);
});
</script>
</body>
</html>
