<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Register Company</title>
    <link rel="stylesheet" href="/assets/css/legacy-style.css?v=6">
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>
<main class="card register-card">
    <div class="logo-section">
        <a href="/login">
            <img src="/assets/images/skyare-logo.png" alt="Skyare Accounting Logo" class="logo">
        </a>
    </div>
    <h1>Create Your Company</h1>
    <p class="hint">Set up your company, subdomain, and first admin user.</p>

    <?php foreach (($errors ?? []) as $error): ?>
        <p class="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endforeach; ?>

    <form method="post" action="/register">
        <input type="hidden" name="_token" value="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>">

        <label>Company Name</label>
        <input name="company_name" placeholder="Example: Acme Trading" value="<?= htmlspecialchars($old['company_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>

        <label>Subdomain</label>
        <input name="subdomain" placeholder="Example: acme" pattern="[a-z0-9-]{2,63}" value="<?= htmlspecialchars($old['subdomain'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
        <p class="hint">Your URL: https://&lt;subdomain&gt;.<?= htmlspecialchars($base_domain ?? 'skyare.space', ENT_QUOTES, 'UTF-8') ?></p>

        <label>Full Name</label>
        <input name="full_name" placeholder="Example: John Doe" value="<?= htmlspecialchars($old['full_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>

        <label>Admin Email</label>
        <input name="email" type="email" placeholder="Example: admin@acme.com" value="<?= htmlspecialchars($old['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>

        <label>Password</label>
        <input name="password" type="password" placeholder="Minimum 8 characters" minlength="8" required>

        <button type="submit">Create Company</button>
    </form>

    <p class="hint">Already have a company? Open your subdomain and sign in.</p>
</main>
</body>
</html>

