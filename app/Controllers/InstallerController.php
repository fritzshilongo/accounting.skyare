<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\RequestContext;
use App\Core\View;

final class InstallerController
{
    public static function show(Database $db, RequestContext $context): void
    {
        View::render('auth/install', [
            'token' => \App\Middleware\CsrfMiddleware::token(),
        ]);
    }

    public static function run(Database $db, RequestContext $context): void
    {
        $dbHost = trim($_POST['db_host'] ?? '127.0.0.1');
        $dbPort = trim($_POST['db_port'] ?? '3306');
        $dbName = trim($_POST['db_name'] ?? 'skyare_main_db');
        $dbUser = trim($_POST['db_user'] ?? 'root');
        $dbPass = trim($_POST['db_pass'] ?? '');

        $env = "APP_NAME=SkyAre Accounting\nAPP_ENV=production\nBASE_DOMAIN=skyare.space\nDEFAULT_SUBDOMAIN=www\nAPP_TIMEZONE=Africa/Windhoek\nDB_HOST={$dbHost}\nDB_PORT={$dbPort}\nDB_NAME={$dbName}\nDB_USER={$dbUser}\nDB_PASS={$dbPass}\n";

        file_put_contents(__DIR__ . '/../../.env', $env);
        echo 'Installer saved .env. Import migrations in order (001_init.sql through 012_accounting_schema_hardening.sql), then create admin user.';
    }
}
