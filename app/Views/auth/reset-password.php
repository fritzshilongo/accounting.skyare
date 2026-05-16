<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Reset Password</title>
    <link rel="stylesheet" href="/assets/css/legacy-style.css?v=3">
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>
<div class="login-container">
    <div class="logo-section">
        <?php if (!empty($company['logo_data'])): ?>
            <img src="<?= htmlspecialchars($company['logo_data'], ENT_QUOTES, 'UTF-8') ?>" alt="Skyare Accounting Logo" class="logo">
        <?php else: ?>
            <img src="/assets/images/skyare-logo.png" alt="Skyare Accounting Logo" class="logo">
        <?php endif; ?>
    </div>
    <h2>Reset Password</h2>

    <?php if (!empty($success)): ?>
        <p class="alert" style="background:#d4edda;color:#155724;"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
        <p id="error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>

    <form method="post" action="/reset-password">
        <input type="hidden" name="_token" value="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>">
        <input type="hidden" name="token" value="<?= htmlspecialchars($tokenValue ?? '', ENT_QUOTES, 'UTF-8') ?>">

        <label for="new_password">New password</label>
        <input id="new_password" name="new_password" type="password" required>

        <label for="confirm_password">Confirm password</label>
        <input id="confirm_password" name="confirm_password" type="password" required>

        <button type="submit" class="btn btn-primary">Reset password</button>
    </form>

    <p class="hint"><a href="/login">Back to login</a></p>
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
