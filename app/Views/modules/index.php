<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Modules</title>
    <link rel="stylesheet" href="/css/legacy-style.css">
    <link rel="stylesheet" href="/css/app.css">
</head>
<body>
<main class="card card-wide">
    <h1>Modules</h1>
    <p>Company: <?= htmlspecialchars($company['company_name'] ?? 'Unknown', ENT_QUOTES, 'UTF-8') ?></p>
    <p class="hint">Role: <?= htmlspecialchars($role_key ?? 'unknown', ENT_QUOTES, 'UTF-8') ?></p>

    <div class="module-grid">
        <?php foreach (($modules ?? []) as $module): ?>
            <section class="module-card">
                <h2><?= htmlspecialchars($module['name'], ENT_QUOTES, 'UTF-8') ?></h2>
                <p><?= htmlspecialchars($module['description'], ENT_QUOTES, 'UTF-8') ?></p>
                <p class="hint">Status: <?= htmlspecialchars($module['status'], ENT_QUOTES, 'UTF-8') ?></p>
                <p><a href="<?= htmlspecialchars($module['path'], ENT_QUOTES, 'UTF-8') ?>">Open <?= htmlspecialchars($module['name'], ENT_QUOTES, 'UTF-8') ?></a></p>
            </section>
        <?php endforeach; ?>
    </div>

    <p><a href="/dashboard">Back to dashboard</a></p>
</main>
</body>
</html>

