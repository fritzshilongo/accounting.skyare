<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Forgot Password</title>
    <link rel="stylesheet" href="/assets/css/legacy-style.css?v=6">
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>
<div class="login-container">
    <div class="logo-section">
        <a href="/login">
            <?php if (!empty($company['logo_data'])): ?>
                <img src="<?= htmlspecialchars($company['logo_data'], ENT_QUOTES, 'UTF-8') ?>" alt="Skyare Accounting Logo" class="logo">
            <?php else: ?>
                <img src="/assets/images/skyare-logo.png" alt="Skyare Accounting Logo" class="logo">
            <?php endif; ?>
        </a>
    </div>
    <h2>Forgot Password</h2>
    <p>Enter your email and we'll send you a link to reset your password.</p>

    <?php if (!empty($success)): ?>
        <p class="alert" style="background:#d4edda;color:#155724;"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
        <p id="error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>

    <?php if (!empty($resetLink)): ?>
        <p class="hint">Reset link (dev mode): <a href="<?= htmlspecialchars($resetLink, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($resetLink, ENT_QUOTES, 'UTF-8') ?></a></p>
    <?php endif; ?>

    <form method="post" action="/forgot-password">
        <input type="hidden" name="_token" value="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>">

        <label for="email">Email</label>
        <input id="email" name="email" type="email" value="<?= htmlspecialchars($email ?? '', ENT_QUOTES, 'UTF-8') ?>" required>

        <button type="submit" class="btn btn-primary">Send reset link</button>
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
