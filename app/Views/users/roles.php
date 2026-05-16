<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Role Permissions - Skyare Accounting</title>
    <link rel="stylesheet" href="/assets/css/legacy-style.css">
    <link rel="stylesheet" href="/assets/css/app.css">
    <style>
        .roles-grid { width: 100%; border-collapse: collapse; margin-top: 18px; }
        .roles-grid th, .roles-grid td { padding: 8px 12px; border: 1px solid #ddd; text-align: center; }
        .roles-grid th:first-child, .roles-grid td:first-child { text-align: left; font-weight: 600; }
        .roles-grid thead th { background: #f4f6fa; }
        .role-tabs { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 20px; }
        .role-tab { padding: 8px 16px; border-radius: 6px; border: 1px solid #ccc; text-decoration: none; color: #333; background: #f9f9f9; font-size: 0.9rem; }
        .role-tab.active { background: #4361ee; color: #fff; border-color: #4361ee; }
        .module-label { text-transform: capitalize; }
    </style>
</head>
<body>
<div class="container" style="max-width:900px;margin:0 auto;padding:24px;">
    <a href="/users" class="back-link">&larr; Back to Users</a>
    <h2 style="margin-top:16px;">Role Permissions</h2>
    <p>Configure what each non-admin role can access. Admin and primary admin retain full access and cannot be reduced below other roles.</p>

    <?php if ($saved ?? false): ?>
        <p class="alert" style="background:#d1fae5;color:#065f46;border:1px solid #6ee7b7;padding:10px 14px;border-radius:6px;">Permissions saved successfully.</p>
    <?php endif; ?>

    <!-- Role selector tabs -->
    <div class="role-tabs">
        <?php foreach (($roles ?? []) as $r): ?>
            <a href="/users/roles?role=<?= urlencode($r) ?>"
               class="role-tab <?= ($selected_role === $r) ? 'active' : '' ?>">
                <?= htmlspecialchars($r, ENT_QUOTES, 'UTF-8') ?>
            </a>
        <?php endforeach; ?>
    </div>

    <form method="post" action="/users/roles">
        <input type="hidden" name="_token" value="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>">
        <input type="hidden" name="role_key" value="<?= htmlspecialchars($selected_role, ENT_QUOTES, 'UTF-8') ?>">

        <table class="roles-grid">
            <thead>
                <tr>
                    <th>Module</th>
                    <th>View</th>
                    <th>Create</th>
                    <th>Edit</th>
                    <th>Delete</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach (($modules ?? []) as $mod):
                $p = $perms[$mod] ?? ['view' => false, 'create' => false, 'edit' => false, 'delete' => false];
                $moduleAllowed = in_array('*', $allowed_modules ?? [], true) || in_array($mod, $allowed_modules ?? [], true);
            ?>
                <tr>
                    <td class="module-label">
                        <?= htmlspecialchars(str_replace('_', ' ', $mod), ENT_QUOTES, 'UTF-8') ?>
                        <?php if (!$moduleAllowed): ?><small style="display:block;color:#888;">Not available for this role</small><?php endif; ?>
                    </td>
                    <td><input type="checkbox" name="perms[<?= htmlspecialchars($mod, ENT_QUOTES, 'UTF-8') ?>][view]"   value="1" <?= $p['view']   ? 'checked' : '' ?> <?= $moduleAllowed ? '' : 'disabled' ?>></td>
                    <td><input type="checkbox" name="perms[<?= htmlspecialchars($mod, ENT_QUOTES, 'UTF-8') ?>][create]" value="1" <?= $p['create'] ? 'checked' : '' ?> <?= $moduleAllowed ? '' : 'disabled' ?>></td>
                    <td><input type="checkbox" name="perms[<?= htmlspecialchars($mod, ENT_QUOTES, 'UTF-8') ?>][edit]"   value="1" <?= $p['edit']   ? 'checked' : '' ?> <?= $moduleAllowed ? '' : 'disabled' ?>></td>
                    <td><input type="checkbox" name="perms[<?= htmlspecialchars($mod, ENT_QUOTES, 'UTF-8') ?>][delete]" value="1" <?= $p['delete'] ? 'checked' : '' ?> <?= $moduleAllowed ? '' : 'disabled' ?>></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <div style="margin-top:16px;display:flex;gap:10px;align-items:center;">
            <button type="submit" class="btn btn-primary">Save Permissions for <?= htmlspecialchars($selected_role, ENT_QUOTES, 'UTF-8') ?></button>
            <small style="color:#666;">Saving applies only to the currently selected role.</small>
        </div>
    </form>
</div>
</body>
</html>
