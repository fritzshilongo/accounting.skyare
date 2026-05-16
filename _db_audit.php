<?php
$host = '127.0.0.1';
$port = 3306;
$db = 'sumbkqqz_accounting';
$user = 'sumbkqqz_sumbkqqz';
$pass = 'Ruflex@1965';

$pdo = new PDO(
    "mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4",
    $user,
    $pass,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
);

function scalar(PDO $pdo, string $sql): int|string {
    $v = $pdo->query($sql)->fetchColumn();
    return is_numeric($v) ? (int)$v : (string)$v;
}

$report = [];
$report['database'] = $db;
$report['table_count'] = scalar($pdo, "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = '{$db}'");
$report['all_tables'] = $pdo->query("SELECT table_name FROM information_schema.tables WHERE table_schema = '{$db}' ORDER BY table_name ASC")->fetchAll(PDO::FETCH_COLUMN);

$coreTables = [
    'companies','users','licenses','password_resets','clients','products','invoices','invoice_items',
    'payments','estimates','journal_entries','expenses','inventory_moves','inventory_levels',
    'tax_rates','recurring_invoices','audit_trails','backups'
];

$counts = [];
$missingTables = [];
foreach ($coreTables as $t) {
    $exists = (int)scalar($pdo, "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = '{$db}' AND table_name = '{$t}'");
    if ($exists === 0) {
        $missingTables[] = $t;
        continue;
    }
    $counts[$t] = (int)scalar($pdo, "SELECT COUNT(*) FROM {$t}");
}
$report['counts'] = $counts;
$report['missing_tables'] = $missingTables;

$orphans = [];
$orphans['users_company'] = (int)scalar($pdo, "SELECT COUNT(*) FROM users u LEFT JOIN companies c ON c.company_id = u.company_id WHERE u.company_id IS NOT NULL AND c.company_id IS NULL");
$orphans['licenses_company'] = (int)scalar($pdo, "SELECT COUNT(*) FROM licenses l LEFT JOIN companies c ON c.company_id = l.company_id WHERE l.company_id IS NOT NULL AND c.company_id IS NULL");
$orphans['clients_company'] = (int)scalar($pdo, "SELECT COUNT(*) FROM clients cl LEFT JOIN companies c ON c.company_id = cl.company_id WHERE cl.company_id IS NOT NULL AND c.company_id IS NULL");
$orphans['products_company'] = (int)scalar($pdo, "SELECT COUNT(*) FROM products p LEFT JOIN companies c ON c.company_id = p.company_id WHERE p.company_id IS NOT NULL AND c.company_id IS NULL");
$orphans['invoices_company'] = (int)scalar($pdo, "SELECT COUNT(*) FROM invoices i LEFT JOIN companies c ON c.company_id = i.company_id WHERE i.company_id IS NOT NULL AND c.company_id IS NULL");
$orphans['password_resets_user'] = (int)scalar($pdo, "SELECT COUNT(*) FROM password_resets pr LEFT JOIN users u ON u.id = pr.user_id WHERE pr.user_id IS NOT NULL AND u.id IS NULL");
$report['orphans'] = $orphans;

$criticalColumns = [
    ['companies', 'created_at'],
    ['users', 'password_hash'],
    ['users', 'role_key'],
    ['licenses', 'expiry_date'],
    ['password_resets', 'used_at'],
];
$columns = [];
foreach ($criticalColumns as [$table, $col]) {
    $columns["{$table}.{$col}"] = (int)scalar($pdo, "SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = '{$db}' AND table_name = '{$table}' AND column_name = '{$col}'") > 0;
}
$report['critical_columns'] = $columns;

$st = $pdo->query("SELECT company_id, company_name, subdomain, created_at FROM companies WHERE subdomain = 'st' LIMIT 1")->fetch();
$report['st_company'] = $st ?: null;
$report['st_licenses'] = $pdo->query("SELECT license_id, domain, status, expiry_date FROM licenses WHERE domain = 'st.skyare.space' ORDER BY license_id DESC LIMIT 5")->fetchAll();

$report['null_company_created_at'] = (int)scalar($pdo, "SELECT COUNT(*) FROM companies WHERE created_at IS NULL");
$report['companies_no_active_license_for_own_domain'] = (int)scalar($pdo, "
SELECT COUNT(*)
FROM companies c
LEFT JOIN licenses l
  ON l.company_id = c.company_id
 AND l.domain = CONCAT(c.subdomain, '.skyare.space')
 AND l.status = 'active'
WHERE c.status = 'active' AND c.subdomain IS NOT NULL AND c.subdomain <> '' AND l.license_id IS NULL
");

$modulePatterns = [
    'inventory' => 'inventory%',
    'audit' => 'audit%',
    'backup' => 'backup%',
    'invoice' => 'invoice%',
    'payment' => 'payment%',
    'estimate' => 'estimate%',
    'expense' => 'expense%',
    'journal' => 'journal%',
    'tax' => 'tax%',
    'recurring' => 'recurring%',
];
$moduleTables = [];
foreach ($modulePatterns as $module => $pattern) {
    $stmt = $pdo->prepare("SELECT table_name FROM information_schema.tables WHERE table_schema = :db AND table_name LIKE :p ORDER BY table_name");
    $stmt->execute(['db' => $db, 'p' => $pattern]);
    $moduleTables[$module] = $stmt->fetchAll(PDO::FETCH_COLUMN);
}
$report['module_tables'] = $moduleTables;

echo json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
