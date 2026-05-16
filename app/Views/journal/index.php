<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Journal Entries</title>
    <link rel="stylesheet" href="/css/legacy-style.css">
    <link rel="stylesheet" href="/css/app.css">
</head>
<body>
<main class="card card-wide">
    <h1>Journal Entries</h1>
    <p>Company: <?= htmlspecialchars($company['company_name'] ?? 'Unknown', ENT_QUOTES, 'UTF-8') ?></p>
    <p class="hint">
        Journal entries let you manually post debit and credit transactions to any account.
        These entries appear directly in the <strong>General Ledger</strong> alongside auto-generated
        invoice (Sales Revenue) and expense rows.
        Use them for opening balances, bank transfers, payroll, tax payments, depreciation, and any
        other manual adjustment. Enter either a <em>Debit</em> or a <em>Credit</em> — not both on the same line.
        &nbsp;<a href="/sales/general-ledger/export/pdf" target="_blank">View General Ledger PDF &rarr;</a>
    </p>

    <?php if (isset($_GET['saved'])): ?>
        <p class="alert alert-success">Journal entry recorded successfully.</p>
    <?php elseif (isset($_GET['deleted'])): ?>
        <p class="alert alert-success">Journal entry deleted.</p>
    <?php endif; ?>

    <?php foreach (($errors ?? []) as $field => $msg): ?>
        <p class="alert"><?= htmlspecialchars((string) $msg, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endforeach; ?>

    <!-- Filter form -->
    <form method="get" action="/journal-entries" style="display:flex;gap:10px;align-items:end;flex-wrap:wrap;margin:10px 0 14px;">
        <div>
            <label>Search</label>
            <input type="text" name="q" value="<?= htmlspecialchars($search ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="account, reference, description">
        </div>
        <div>
            <label>Account</label>
            <select name="account">
                <option value="">All accounts</option>
                <?php foreach (($accounts ?? []) as $acc): ?>
                    <option value="<?= htmlspecialchars((string) $acc, ENT_QUOTES, 'UTF-8') ?>"
                        <?= (($account_filter ?? '') === $acc) ? 'selected' : '' ?>>
                        <?= htmlspecialchars((string) $acc, ENT_QUOTES, 'UTF-8') ?>
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
        <?php if (!empty($search) || !empty($account_filter) || !empty($from_date) || !empty($to_date)): ?>
            <a class="btn btn-secondary" href="/journal-entries">Clear</a>
        <?php endif; ?>
    </form>

    <!-- Summary tiles -->
    <div class="module-grid" style="margin-bottom:14px;">
        <section class="module-card">
            <h2>Entries</h2>
            <p><?= number_format((int) ($summary['count'] ?? 0)) ?></p>
        </section>
        <section class="module-card">
            <h2>Total Debits</h2>
            <p>N$ <?= number_format((float) ($summary['total_debit'] ?? 0), 2) ?></p>
        </section>
        <section class="module-card">
            <h2>Total Credits</h2>
            <p>N$ <?= number_format((float) ($summary['total_credit'] ?? 0), 2) ?></p>
        </section>
        <section class="module-card">
            <h2>Net (Credits &minus; Debits)</h2>
            <?php $net = (float) ($summary['net'] ?? 0); ?>
            <p style="color:<?= $net >= 0 ? '#28a745' : '#dc3545' ?>">
                N$ <?= number_format(abs($net), 2) ?><?= $net < 0 ? ' (deficit)' : '' ?>
            </p>
        </section>
    </div>

    <!-- Post new entry form -->
    <form method="post" action="/journal-entries">
        <input type="hidden" name="_token" value="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>">

        <label>Entry Date <span style="color:red">*</span></label>
        <input name="entry_date" type="date"
               value="<?= htmlspecialchars($old['entry_date'] ?? date('Y-m-d'), ENT_QUOTES, 'UTF-8') ?>"
               required>

        <label>Account <span style="color:red">*</span></label>
        <select name="account" required>
            <option value="">Select account</option>
            <?php foreach (($accounts ?? []) as $acc): ?>
                <option value="<?= htmlspecialchars((string) $acc, ENT_QUOTES, 'UTF-8') ?>"
                    <?= (($old['account'] ?? '') === $acc) ? 'selected' : '' ?>>
                    <?= htmlspecialchars((string) $acc, ENT_QUOTES, 'UTF-8') ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label>Reference <span style="color:#888;font-weight:normal;font-size:0.85em">(optional)</span></label>
        <input name="reference"
               value="<?= htmlspecialchars($old['reference'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
               placeholder="e.g. BANK-001, PAYROLL-2026-03, JNL-001">

        <label>Description <span style="color:#888;font-weight:normal;font-size:0.85em">(optional)</span></label>
        <input name="description"
               value="<?= htmlspecialchars($old['description'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
               placeholder="e.g. Monthly rent payment, Opening bank balance">

        <div style="display:flex;gap:20px;flex-wrap:wrap;">
            <div style="flex:1;min-width:150px;">
                <label>Debit Amount</label>
                <input name="debit" type="number" min="0" step="0.01"
                       value="<?= htmlspecialchars($old['debit'] ?? '0', ENT_QUOTES, 'UTF-8') ?>"
                       placeholder="0.00">
                <small style="color:#888">Enter amount for a Debit entry, leave 0 for Credit.</small>
            </div>
            <div style="flex:1;min-width:150px;">
                <label>Credit Amount</label>
                <input name="credit" type="number" min="0" step="0.01"
                       value="<?= htmlspecialchars($old['credit'] ?? '0', ENT_QUOTES, 'UTF-8') ?>"
                       placeholder="0.00">
                <small style="color:#888">Enter amount for a Credit entry, leave 0 for Debit.</small>
            </div>
        </div>

        <button type="submit" style="margin-top:12px;">Post Journal Entry</button>
    </form>

    <!-- Entries table -->
    <table class="table" style="margin-top:20px;">
        <thead>
            <tr>
                <th>ID</th>
                <th>Date</th>
                <th>Account</th>
                <th>Reference</th>
                <th>Description</th>
                <th>Debit</th>
                <th>Credit</th>
                <th>Posted At</th>
                <th>Delete</th>
            </tr>
        </thead>
        <tbody>
            <?php if (($rows ?? []) === []): ?>
                <tr><td colspan="9">No journal entries found. Use the form above to post your first entry.</td></tr>
            <?php else: ?>
                <?php foreach ($rows as $row): ?>
                    <tr>
                        <td><?= (int) $row['entry_id'] ?></td>
                        <td><?= htmlspecialchars((string) $row['entry_date'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string) $row['account'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string) ($row['reference'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string) ($row['description'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= (float) $row['debit'] > 0 ? 'N$ ' . number_format((float) $row['debit'], 2) : '—' ?></td>
                        <td><?= (float) $row['credit'] > 0 ? 'N$ ' . number_format((float) $row['credit'], 2) : '—' ?></td>
                        <td><?= htmlspecialchars((string) ($row['created_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        <td>
                            <form method="post" action="/journal-entries/delete"
                                  onsubmit="return confirm('Delete this journal entry?')">
                                <input type="hidden" name="_token" value="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>">
                                <input type="hidden" name="entry_id" value="<?= (int) $row['entry_id'] ?>">
                                <button type="submit" class="btn btn-danger" style="padding:2px 8px;font-size:0.8em;">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <p style="margin-top:14px;">
        <a href="/sales/general-ledger/export/pdf" target="_blank">General Ledger PDF</a>
        &nbsp;|&nbsp;
        <a href="/sales/general-ledger/export/csv" target="_blank">General Ledger CSV</a>
        &nbsp;|&nbsp;
        <a href="/dashboard">Back to Dashboard</a>
    </p>
</main>
</body>
</html>
