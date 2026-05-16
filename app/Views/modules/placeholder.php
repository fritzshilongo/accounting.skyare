<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title><?= htmlspecialchars($module_name ?? 'Module', ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="stylesheet" href="/css/legacy-style.css">
    <link rel="stylesheet" href="/css/app.css">
</head>
<body>
<main class="card">
    <h1><?= htmlspecialchars($module_name ?? 'Module', ENT_QUOTES, 'UTF-8') ?></h1>
    <p>Company: <?= htmlspecialchars($company['company_name'] ?? 'Unknown', ENT_QUOTES, 'UTF-8') ?></p>
    <p><?= htmlspecialchars($summary ?? 'This module is under active migration.', ENT_QUOTES, 'UTF-8') ?></p>
    <p class="hint">This endpoint is wired and tenant-protected. Full features are next in migration order.</p>

    <p><a href="/dashboard">Back to Dashboard</a></p>
</main>
</body>
</html>

