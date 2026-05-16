<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Expenses</title>
    <link rel="stylesheet" href="/assets/css/legacy-style.css">
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>
<main class="card card-wide">
    <h1>Expenses</h1>
    <p>Company: <?= htmlspecialchars($company['company_name'] ?? 'Unknown', ENT_QUOTES, 'UTF-8') ?></p>
    <p class="hint">Expenses captured here are the same records used in financial statements, ledger exports, and sales PDF summaries.</p>

    <?php foreach (($errors ?? []) as $error): ?>
        <p class="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endforeach; ?>

    <form method="get" action="/expenses" style="display:flex;gap:10px;align-items:end;flex-wrap:wrap;margin:10px 0 14px;">
        <div>
            <label>Search</label>
            <input type="text" name="q" value="<?= htmlspecialchars($search ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="ID, category, description, amount">
        </div>
        <div>
            <label>Category</label>
            <select name="category">
                <option value="">All</option>
                <?php foreach (($categories ?? []) as $category): ?>
                    <option value="<?= htmlspecialchars((string) $category, ENT_QUOTES, 'UTF-8') ?>" <?= (($category_filter ?? '') === $category) ? 'selected' : '' ?>>
                        <?= htmlspecialchars((string) $category, ENT_QUOTES, 'UTF-8') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label>From</label>
            <input type="date" name="from" value="<?= htmlspecialchars($from_date ?? '', ENT_QUOTES, 'UTF-8') ?>">
        </div>
        <div>
            <label>To</label>
            <input type="date" name="to" value="<?= htmlspecialchars($to_date ?? '', ENT_QUOTES, 'UTF-8') ?>">
        </div>
        <button type="submit" class="btn btn-primary">Filter</button>
        <?php if (!empty($search) || !empty($category_filter) || !empty($from_date) || !empty($to_date)): ?>
            <a class="btn btn-secondary" href="/expenses">Clear</a>
        <?php endif; ?>
    </form>

    <div class="module-grid" style="margin-bottom:14px;">
        <section class="module-card">
            <h2>Total Entries</h2>
            <p><?= number_format((float) ($summary['count'] ?? 0), 0) ?></p>
        </section>
        <section class="module-card">
            <h2>Total Amount</h2>
            <p>N$ <?= number_format((float) ($summary['total_amount'] ?? 0), 2) ?></p>
        </section>
    </div>

    <form method="post" action="/expenses">
        <input type="hidden" name="_token" value="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>">

        <label>Category</label>
        <select name="category" required>
            <option value="">Select category</option>
            <?php foreach (($categories ?? []) as $category): ?>
                <option value="<?= htmlspecialchars((string) $category, ENT_QUOTES, 'UTF-8') ?>"
                    <?= (($old['category'] ?? '') === $category) ? 'selected' : '' ?>>
                    <?= htmlspecialchars((string) $category, ENT_QUOTES, 'UTF-8') ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label>Description</label>
        <input name="description" value="<?= htmlspecialchars($old['description'] ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="Example: Electricity bill - Windhoek office">

        <label>Amount</label>
        <input name="amount" type="number" min="0.01" step="0.01" value="<?= htmlspecialchars($old['amount'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>

        <label>Expense Date</label>
        <input name="expense_date" type="date" value="<?= htmlspecialchars($old['expense_date'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>

        <button type="submit">Record Expense</button>
    </form>

    <table class="table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Category</th>
                <th>Description</th>
                <th>Amount</th>
                <th>Expense Date</th>
                <th>Recorded At</th>
            </tr>
        </thead>
        <tbody>
            <?php if (($rows ?? []) === []): ?>
                <tr><td colspan="6">No expenses found.</td></tr>
            <?php else: ?>
                <?php foreach ($rows as $row): ?>
                    <tr>
                        <td><?= (int) $row['expense_id'] ?></td>
                        <td><?= htmlspecialchars((string) $row['category'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string) ($row['description'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        <td>N$ <?= number_format((float) $row['amount'], 2) ?></td>
                        <td><?= htmlspecialchars((string) $row['expense_date'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string) ($row['created_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <p><a href="/dashboard">Back to Dashboard</a></p>
</main>
</body>
</html>

