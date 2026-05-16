<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Dashboard - Skyare Accounting</title>
    <link rel="stylesheet" href="/assets/css/legacy-style.css">
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>
<nav>
    <h1>Skyare Accounting</h1>
    <form method="post" action="/logout">
        <input type="hidden" name="_token" value="<?= htmlspecialchars(\App\Middleware\CsrfMiddleware::token(), ENT_QUOTES, 'UTF-8') ?>">
        <button type="submit" class="btn btn-secondary">Logout</button>
    </form>
</nav>

<div class="container">
    <h2>Welcome, <?= htmlspecialchars($user['full_name'] ?: ($user['role_key'] ?? 'admin'), ENT_QUOTES, 'UTF-8') ?>!</h2>

    <div class="dashboard-grid">
        <a id="sales" class="dashboard-tile tile-link" href="/sales">Sales: N$ <?= number_format((float) ($stats['sales'] ?? 0), 2) ?></a>
        <a id="expenses" class="dashboard-tile tile-link" href="/expenses">Expenses: N$ <?= number_format((float) ($stats['expenses'] ?? 0), 2) ?></a>
        <a class="dashboard-tile tile-link" style="background:linear-gradient(135deg,#20c997,#0d6e4f);" href="/journal-entries">Journal Entries: <?= (int) ($stats['journal_entries'] ?? 0) ?></a>
        <a id="customers" class="dashboard-tile tile-link" href="/customers">Customers: <?= (int) ($stats['customers'] ?? 0) ?></a>
        <a id="invoices" class="dashboard-tile tile-link" href="/invoices">Invoices: <?= (int) ($stats['invoices'] ?? 0) ?></a>
        <a id="products" class="dashboard-tile tile-link" href="/products">Products: <?= (int) ($stats['products'] ?? 0) ?></a>
        <a id="estimates" class="dashboard-tile tile-link" href="/estimates">Estimates: <?= (int) ($stats['estimates'] ?? 0) ?></a>
        <a class="dashboard-tile tile-link" style="background:linear-gradient(135deg,#f093fb,#f5576c);" href="/credit-management">💳 Credit Management</a>
        <a class="dashboard-tile tile-link" style="background:linear-gradient(135deg,#6c757d,#495057);" href="/users">Users</a>
        <a class="dashboard-tile tile-link" style="background:linear-gradient(135deg,#ff9800,#ffb74d);" href="/inventory">Inventory</a>
        <a class="dashboard-tile tile-link" style="background:linear-gradient(135deg,#6f42c1,#9966ff);" href="/company-details">Company Details</a>
        <a class="dashboard-tile tile-link" style="background:linear-gradient(135deg,#667eea,#764ba2);" href="/audit-trail">📋 Audit Trail</a>
    </div>

    <div class="menu-section">
        <h3>Quick Links</h3>
        <ul>
            <li><a href="/sales">Sales (Cash Transactions)</a></li>
            <li><a href="/credit-management">Credit Management (Accounts Receivable)</a></li>
            <li><a href="/invoices">Invoices</a></li>
            <li><a href="/expenses">Expenses</a></li>
            <li><a href="/journal-entries">Journal Entries (Manual Debits &amp; Credits)</a></li>
            <li><a href="/estimates">Estimates</a></li>
            <li><a href="/customers">Customers</a></li>
            <li><a href="/products">Products</a></li>
            <li><a href="/inventory">Inventory</a></li>
            <li><a href="/audit-trail">Audit Trail (Forensic Investigation)</a></li>
            <li><a href="/module-hub">Module Hub</a></li>
        </ul>
    </div>
</div>

<footer class="company-footer">
    <div class="footer-content">
        <p><strong><?= htmlspecialchars($company['company_name'] ?? 'Skyare Trading CC', ENT_QUOTES, 'UTF-8') ?></strong></p>
        <p>Website: <a href="https://www.skyare.space" target="_blank" rel="noopener">www.skyare.space</a></p>
        <p>Email: <a href="mailto:info@skyare.space">info@skyare.space</a> | <a href="mailto:skyaretradingcc@outlook.com">skyaretradingcc@outlook.com</a></p>
        <p>Contact: 0812016012</p>
        <p>Developer: Fritz Shilongo Munenguni | PO Box 95072, Windhoek, Soweto</p>
    </div>
</footer>
</body>
</html>

