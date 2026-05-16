<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>SkyAre Installer</title>
    <link rel="stylesheet" href="/assets/css/legacy-style.css">
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>
<main class="card">
    <h1>Install SkyAre Accounting</h1>
    <form method="post" action="/install">
        <input type="hidden" name="_token" value="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>">

        <label>DB Host</label>
        <input name="db_host" value="127.0.0.1" required>

        <label>DB Port</label>
        <input name="db_port" value="3306" required>

        <label>DB Name</label>
        <input name="db_name" value="skyare_main_db" required>

        <label>DB User</label>
        <input name="db_user" value="root" required>

        <label>DB Password</label>
        <input name="db_pass" type="password">

        <button type="submit">Save Configuration</button>
    </form>
    <p class="hint">After saving, import migrations in order: <code>database/migrations/001_init.sql</code> through <code>database/migrations/013_password_resets.sql</code>.</p>
</main>
</body>
</html>

