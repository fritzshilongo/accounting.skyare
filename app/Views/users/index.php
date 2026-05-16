<!doctype html>
<html lang="en">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Users</title>
<link rel="stylesheet" href="/assets/css/legacy-style.css"><link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>
<main class="card card-wide">
<h1>Users &amp; Roles</h1>
<?php if (isset($_GET['reset'])): ?><p class="alert" style="background:#d4edda;color:#155724;">Password reset successfully.</p><?php endif; ?>
<p>Company: <?= htmlspecialchars($company['company_name'] ?? 'Unknown', ENT_QUOTES, 'UTF-8') ?></p>
<?php foreach (($errors ?? []) as $error): ?><p class="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p><?php endforeach; ?>

<?php
$currentRole = (string) ($current_role ?? '');
$currentUserId = (int) ($current_user_id ?? 0);
$roleRank = static function (string $role): int {
	return [
		'sales' => 10,
		'inventory' => 20,
		'inventory_manager' => 30,
		'creditor' => 40,
		'accountant' => 50,
		'admin' => 90,
		'primary_admin' => 100,
	][$role] ?? 0;
};
$canManageUsers = in_array($currentRole, ['admin', 'primary_admin'], true);
?>

<h2>Add User</h2>
<?php if ($canManageUsers): ?>
<form method="post" action="/users">
	<input type="hidden" name="_token" value="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>">
	<label>Full Name</label><input name="full_name" value="<?= htmlspecialchars($old['full_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
	<label>Email</label><input name="email" type="email" value="<?= htmlspecialchars($old['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
	<label>Password</label><input name="password" type="password" minlength="8" required>
	<label>Role</label>
	<select name="role_key">
		<?php foreach (($roles ?? []) as $r): ?>
			<option value="<?= htmlspecialchars($r, ENT_QUOTES, 'UTF-8') ?>" <?= (($old['role_key'] ?? 'sales') === $r) ? 'selected' : '' ?>><?= htmlspecialchars($r, ENT_QUOTES, 'UTF-8') ?></option>
		<?php endforeach; ?>
	</select>
	<label>Status</label>
	<select name="is_active">
		<option value="1" <?= (($old['is_active'] ?? '1') === '1') ? 'selected' : '' ?>>Active</option>
		<option value="0" <?= (($old['is_active'] ?? '1') === '0') ? 'selected' : '' ?>>Inactive</option>
	</select>
	<button type="submit">Add User</button>
</form>
<?php else: ?>
<p class="hint">Only admin roles can add or manage users.</p>
<?php endif; ?>

<h2>All Users</h2>
<table class="table">
	<thead>
		<tr><th>ID</th><th>Name</th><th>Email</th><th>Role</th><th>Status</th><th>Actions</th></tr>
	</thead>
	<tbody>
	<?php if (($rows ?? []) === []): ?>
		<tr><td colspan="6">No users found.</td></tr>
	<?php else: foreach ($rows as $row): ?>
		<tr>
			<td><?= (int) $row['user_id'] ?></td>
			<td><?= htmlspecialchars($row['full_name'], ENT_QUOTES, 'UTF-8') ?></td>
			<td><?= htmlspecialchars($row['email'], ENT_QUOTES, 'UTF-8') ?></td>
			<td><?= htmlspecialchars($row['role_key'], ENT_QUOTES, 'UTF-8') ?></td>
			<td><?= ((int) $row['is_active'] === 1) ? 'Active' : 'Inactive' ?></td>
			<td>
				<?php
				$targetRole = (string) $row['role_key'];
				$targetRank = $roleRank($targetRole);
				$actorRank = $roleRank($currentRole);
				$isSelf = (int) $row['user_id'] === $currentUserId;
				$canManageTarget = $canManageUsers && !$isSelf && $actorRank > $targetRank;
				$canEditSelf = $canManageUsers && $isSelf;
				?>
				<?php if ($canManageTarget || $canEditSelf): ?>
				<form method="post" action="/users/update" class="inline-form">
					<input type="hidden" name="_token" value="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>">
					<input type="hidden" name="user_id" value="<?= (int) $row['user_id'] ?>">
					<input name="full_name" value="<?= htmlspecialchars($row['full_name'], ENT_QUOTES, 'UTF-8') ?>" required>
					<select name="role_key" <?= $canEditSelf ? 'disabled' : '' ?>>
						<?php foreach (($roles ?? []) as $r): ?>
							<option value="<?= htmlspecialchars($r, ENT_QUOTES, 'UTF-8') ?>" <?= ($row['role_key'] === $r) ? 'selected' : '' ?>><?= htmlspecialchars($r, ENT_QUOTES, 'UTF-8') ?></option>
						<?php endforeach; ?>
					</select>
					<?php if ($canEditSelf): ?><input type="hidden" name="role_key" value="<?= htmlspecialchars((string) $row['role_key'], ENT_QUOTES, 'UTF-8') ?>"><?php endif; ?>
					<select name="is_active" <?= $canEditSelf ? 'disabled' : '' ?>>
						<option value="1" <?= ((int) $row['is_active'] === 1) ? 'selected' : '' ?>>Active</option>
						<option value="0" <?= ((int) $row['is_active'] === 0) ? 'selected' : '' ?>>Inactive</option>
					</select>
					<?php if ($canEditSelf): ?><input type="hidden" name="is_active" value="<?= ((int) $row['is_active'] === 1) ? '1' : '0' ?>"><?php endif; ?>
					<input name="password" type="password" placeholder="New password (optional)">
					<button type="submit">Update</button>
				</form>
				<?php if ($canManageTarget): ?>
				<form method="post" action="/users/reset-password" class="inline-form" onsubmit="return confirm('Reset password for this user?');">
					<input type="hidden" name="_token" value="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>">
					<input type="hidden" name="user_id" value="<?= (int) $row['user_id'] ?>">
					<input name="new_password" type="password" placeholder="New password" minlength="8" required>
					<button type="submit" style="background:#e67e22;">Reset Password</button>
				</form>
				<form method="post" action="/users/delete" class="inline-form" onsubmit="return confirm('Delete this user?');">
					<input type="hidden" name="_token" value="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>">
					<input type="hidden" name="user_id" value="<?= (int) $row['user_id'] ?>">
					<button type="submit">Delete</button>
				</form>
				<?php elseif ($canEditSelf): ?>
				<span class="hint">Your role and status are protected.</span>
				<?php endif; ?>
				<?php else: ?>
				<span class="hint">Protected user</span>
				<?php endif; ?>
			</td>
		</tr>
	<?php endforeach; endif; ?>
	</tbody>
</table>
<p><a href="/users/roles">Manage Role Permissions</a> | <a href="/dashboard">Back to Dashboard</a></p>
</main>
</body>
</html>
