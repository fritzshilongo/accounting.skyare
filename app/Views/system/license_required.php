<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>License Required</title>
    <link rel="stylesheet" href="/assets/css/legacy-style.css">
    <link rel="stylesheet" href="/assets/css/app.css">
    <style>
        .license-box { max-width:520px; margin:60px auto; text-align:center; padding:40px; }
        .license-box h1 { color: var(--danger, #dc3545); margin-bottom:12px; }
        .badge-trial { display:inline-block; background:#fff3cd; color:#856404; border:1px solid #ffc107; border-radius:4px; padding:6px 14px; font-size:.9em; margin-bottom:18px; }
        .license-box p { color: var(--muted, #6c757d); margin-bottom:10px; }
        .contact-block { background:#f8f9fa; border-radius:6px; padding:16px; margin-top:24px; font-size:.92em; }
    </style>
</head>
<body>
<main class="card license-box">
    <img src="/assets/images/skyare-logo.png" alt="Skyare" style="height:48px;margin-bottom:20px;">
    <h1>&#128274; License Required</h1>

    <div class="badge-trial">Your 7-day free trial has ended</div>

    <p><strong>Company:</strong> <?= htmlspecialchars($company['company_name'] ?? 'Unknown', ENT_QUOTES, 'UTF-8') ?></p>
    <p><strong>Domain:</strong> <?= htmlspecialchars($host ?? 'Unknown', ENT_QUOTES, 'UTF-8') ?></p>

    <?php if (!empty($grace_until)): ?>
        <p style="color:#28a745;">&#10003; Grace period active until <?= htmlspecialchars($grace_until, ENT_QUOTES, 'UTF-8') ?>.</p>
    <?php endif; ?>

    <div class="contact-block">
        <p style="margin-bottom:6px;"><strong>To activate a full license, contact Skyare:</strong></p>
        <p>&#128231; <a href="mailto:info@skyare.space">info@skyare.space</a></p>
        <p>&#128222; 0812016012</p>
        <p>&#127760; <a href="https://www.skyare.space" target="_blank">www.skyare.space</a></p>
    </div>
</main>
</body>
</html>
