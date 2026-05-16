<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Skyare Accounting - Login</title>
    <link rel="stylesheet" href="/assets/css/legacy-style.css?v=6">
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>
<?php $companyData = is_array($company ?? null) ? $company : []; ?>
<div class="login-container">
    <div class="logo-section">
        <a href="/login">
            <?php if (!empty($companyData['logo_data'])): ?>
                <img src="<?= htmlspecialchars((string) $companyData['logo_data'], ENT_QUOTES, 'UTF-8') ?>" alt="Skyare Accounting Logo" class="logo">
            <?php else: ?>
                <img src="/assets/images/skyare-logo.png" alt="Skyare Accounting Logo" class="logo">
            <?php endif; ?>
        </a>
    </div>
    <h2>Skyare Accounting</h2>
    <?php if (!empty($is_directory_login)): ?>
        <p>Select your company to continue to its tenant login page.</p>
    <?php else: ?>
        <p>Company: <?= htmlspecialchars((string) ($companyData['company_name'] ?? 'Unknown'), ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
        <p id="error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>

    <?php if (!empty($is_directory_login)): ?>
        <div style="text-align:left;">
            <label for="tenant-selector">Company</label>
            <select id="tenant-selector" style="width:100%;margin-bottom:12px;">
                <option value="">Choose a company...</option>
                <?php foreach (($available_companies ?? []) as $tenant): ?>
                    <option value="<?= htmlspecialchars((string) ($tenant['tenant_url'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                        <?= htmlspecialchars((string) ($tenant['company_name'] ?? 'Unknown'), ENT_QUOTES, 'UTF-8') ?>
                        <?php if (!empty($tenant['subdomain'])): ?>
                            (<?= htmlspecialchars((string) $tenant['subdomain'], ENT_QUOTES, 'UTF-8') ?>)
                        <?php endif; ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="button" class="btn btn-primary" onclick="var target = document.getElementById('tenant-selector').value; if (target) { window.location.href = target; }">Continue to company login</button>
        </div>
    <?php else: ?>
        <form method="post" action="/login">
            <input type="hidden" name="_token" value="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>">

            <label for="email">Email</label>
            <input id="email" name="email" type="email" required>

            <label for="password">Password</label>
            <input id="password" name="password" type="password" required>

            <button type="submit" class="btn btn-primary">Login</button>
        </form>
    <?php endif; ?>

    <?php if (!empty($_GET['reset'])): ?>
        <p class="alert" style="background:#d4edda;color:#155724;">Your password has been reset. Please log in with the new password.</p>
    <?php endif; ?>

    <p class="hint"><a href="/forgot-password">Forgot password?</a> | <a href="/register">Need a new company account? Register here.</a></p>
    <?php if (empty($is_directory_login) && !empty($base_domain)): ?>
        <p class="hint"><a href="https://<?= htmlspecialchars($base_domain, ENT_QUOTES, 'UTF-8') ?>/login">Change company / Back to company selection</a></p>
    <?php endif; ?>
</div>

<footer class="company-footer" style="text-align: center;">
    <div class="footer-content">
        <p><strong>Skyare Trading CC</strong></p>
        <p>Website: <a href="https://www.skyare.space" target="_blank" rel="noopener">www.skyare.space</a></p>
        <p>Email: <a href="mailto:info@skyare.space">info@skyare.space</a> | <a href="mailto:skyaretradingcc@outlook.com">skyaretradingcc@outlook.com</a></p>
        <p>Contact: 0812016012</p>
        <p>Developer: Fritz Shilongo Munenguni | PO Box 95072, Windhoek, Soweto</p>
    </div>
</footer>
</body>
</html>

