<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\Env;
use App\Core\Mailer;
use App\Core\RequestContext;
use App\Core\View;
use App\Models\Company;
use App\Models\User;
use Throwable;

final class AuthController
{
    public static function showRegister(Database $db, RequestContext $context): void
    {
        View::render('auth/register', [
            'token' => \App\Middleware\CsrfMiddleware::token(),
            'errors' => [],
            'old' => [
                'company_name' => '',
                'subdomain' => '',
                'full_name' => '',
                'email' => '',
            ],
            'base_domain' => $context->appConfig()['base_domain'] ?? 'skyare.space',
        ]);
    }

    public static function register(Database $db, RequestContext $context): void
    {
        $companyName = trim((string) ($_POST['company_name'] ?? ''));
        $subdomain = strtolower(trim((string) ($_POST['subdomain'] ?? '')));
        $fullName = trim((string) ($_POST['full_name'] ?? ''));
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        $password = (string) ($_POST['password'] ?? '');

        $errors = [];
        if ($companyName === '' || mb_strlen($companyName) < 2) {
            $errors[] = 'Company name is required.';
        }

        if ($subdomain === '' || !preg_match('/^[a-z0-9][a-z0-9-]{1,62}$/', $subdomain)) {
            $errors[] = 'Subdomain must be 2-63 chars (letters, numbers, hyphen).';
        }

        if ($fullName === '') {
            $errors[] = 'Full name is required.';
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'A valid email is required.';
        }

        if (mb_strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters.';
        }

        $companyModel = new Company($db->pdo());
        if ($subdomain !== '' && $companyModel->findBySubdomain($subdomain) !== null) {
            $errors[] = 'Subdomain is already taken.';
        }

        if ($errors !== []) {
            http_response_code(422);
            View::render('auth/register', [
                'token' => \App\Middleware\CsrfMiddleware::token(),
                'errors' => $errors,
                'old' => [
                    'company_name' => $companyName,
                    'subdomain' => $subdomain,
                    'full_name' => $fullName,
                    'email' => $email,
                ],
                'base_domain' => $context->appConfig()['base_domain'] ?? 'skyare.space',
            ]);
            return;
        }

        $userModel = new User($db->pdo());

        try {
            $db->pdo()->beginTransaction();

            $companyId = $companyModel->create($companyName, $subdomain);
            $userModel->createAdmin($companyId, $fullName, $email, password_hash($password, PASSWORD_DEFAULT));

            $db->pdo()->commit();
        } catch (Throwable $e) {
            if ($db->pdo()->inTransaction()) {
                $db->pdo()->rollBack();
            }

            http_response_code(500);
            View::render('auth/register', [
                'token' => \App\Middleware\CsrfMiddleware::token(),
                'errors' => ['Unable to complete registration right now.'],
                'old' => [
                    'company_name' => $companyName,
                    'subdomain' => $subdomain,
                    'full_name' => $fullName,
                    'email' => $email,
                ],
                'base_domain' => $context->appConfig()['base_domain'] ?? 'skyare.space',
            ]);
            return;
        }

        header('Location: ' . self::buildTenantLoginUrl($subdomain, $context));
    }

    public static function showLogin(Database $db, RequestContext $context): void
    {
        $availableCompanies = self::availableCompanies($db, $context);
        $_SESSION['available_companies'] = $availableCompanies;

        View::render('auth/login', [
            'company' => $context->company(),
            'available_companies' => $availableCompanies,
            'is_directory_login' => $context->company() === null,
            'base_domain' => $context->appConfig()['base_domain'] ?? 'skyare.space',
            'token' => \App\Middleware\CsrfMiddleware::token(),
        ]);
    }

    public static function login(Database $db, RequestContext $context)
    {
        $company = $context->company();
        if ($company === null) {
            http_response_code(422);
            $availableCompanies = self::availableCompanies($db, $context);
            $_SESSION['available_companies'] = $availableCompanies;

            View::render('auth/login', [
                'company' => null,
                'available_companies' => $availableCompanies,
                'is_directory_login' => true,
                'token' => \App\Middleware\CsrfMiddleware::token(),
                'error' => 'Select your company first to continue to the correct login page.',
            ]);
            return;
        }

        $email = trim($_POST['email'] ?? '');
        $password = (string) ($_POST['password'] ?? '');

        if ($email === '' || $password === '') {
            http_response_code(422);
            View::render('auth/login', [
                'company' => $company,
                'token' => \App\Middleware\CsrfMiddleware::token(),
                'error' => 'Email and password are required.',
            ]);
            return;
        }

        $userModel = new User($db->pdo());
        $user = $userModel->findByEmailAndCompany($email, (int) $company['company_id']);

        if (!$user || !(bool) $user['is_active'] || !password_verify($password, $user['password_hash'])) {
            http_response_code(401);
            View::render('auth/login', [
                'company' => $company,
                'token' => \App\Middleware\CsrfMiddleware::token(),
                'error' => 'Invalid credentials.',
            ]);
            return;
        }

        session()->regenerate();

        $authenticatedUser = [
            'user_id' => (int) $user['user_id'],
            'company_id' => (int) $user['company_id'],
            'full_name' => $user['full_name'] ?? '',
            'email' => $user['email'],
            'role_key' => $user['role_key'],
        ];
        $availableCompanies = self::availableCompanies($db, $context);

        session([
            'user' => $authenticatedUser,
            'available_companies' => $availableCompanies,
        ]);

        return redirect('/dashboard');
    }

    public static function showForgotPassword(Database $db, RequestContext $context): void
    {
        View::render('auth/forgot-password', [
            'company' => $context->company(),
            'token' => \App\Middleware\CsrfMiddleware::token(),
            'errors' => [],
            'success' => '',
            'email' => '',
            'resetLink' => '',
        ]);
    }

    public static function forgotPassword(Database $db, RequestContext $context): void
    {
        $company = $context->company();
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            http_response_code(422);
            View::render('auth/forgot-password', [
                'company' => $company,
                'token' => \App\Middleware\CsrfMiddleware::token(),
                'errors' => ['A valid email is required.'],
                'success' => '',
                'email' => $email,
                'resetLink' => '',
            ]);
            return;
        }

        $userModel = new User($db->pdo());
        $user = null;

        if ($company !== null) {
            $user = $userModel->findByEmailAndCompany($email, (int) $company['company_id']);
        } else {
            // Directory host fallback: resolve tenant from the user's email.
            $user = $userModel->findByEmail($email);
            if ($user !== null) {
                $company = (new Company($db->pdo()))->findById((int) ($user['company_id'] ?? 0));
                if ($company === null || (($company['status'] ?? 'inactive') !== 'active')) {
                    $user = null;
                }
            }
        }

        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';

        if (!$user) {
            error_log(sprintf(
                "[pwd_reset_miss] email=%s tenant_id=%s host=%s ip=%s ua=%s",
                $email,
                $company['company_id'] ?? 'null',
                $_SERVER['HTTP_HOST'] ?? 'unknown',
                $ip,
                $ua
            ));

            $userAnyTenantStmt = $db->pdo()->prepare('SELECT id AS user_id, company_id FROM users WHERE email = :email LIMIT 1');
            $userAnyTenantStmt->execute(['email' => $email]);
            if ($userAnyTenantStmt->fetch()) {
                error_log(sprintf(
                    "[pwd_reset_cross_tenant] email=%s tenant_id=%s host=%s ip=%s ua=%s",
                    $email,
                    $company['company_id'] ?? 'null',
                    $_SERVER['HTTP_HOST'] ?? 'unknown',
                    $ip,
                    $ua
                ));
            }

            $message = 'If an account with that email exists, a password reset link has been sent.';
            View::render('auth/forgot-password', [
                'company' => $company,
                'token' => \App\Middleware\CsrfMiddleware::token(),
                'errors' => [],
                'success' => $message,
                'email' => $email,
                'resetLink' => '',
            ]);
            return;
        }

        // rate limit per user + ip, simple window
        $rateStmt = $db->pdo()->prepare(
            'SELECT COUNT(*) as cnt FROM password_resets WHERE user_id = :user_id AND ip = :ip AND created_at > DATE_SUB(NOW(), INTERVAL 15 MINUTE)'
        );
        $rateStmt->execute(['user_id' => $user['user_id'], 'ip' => $ip]);
        $rateCount = (int) ($rateStmt->fetchColumn() ?? 0);
        if ($rateCount >= 3) {
            error_log(sprintf(
                "[pwd_reset_rate_limited] user_id=%s tenant_id=%s host=%s ip=%s ua=%s count=%d",
                $user['user_id'],
                $company['company_id'],
                $_SERVER['HTTP_HOST'] ?? 'unknown',
                $ip,
                $ua,
                $rateCount
            ));

            $message = 'If an account with that email exists, a password reset link has been sent.';
            View::render('auth/forgot-password', [
                'company' => $company,
                'token' => \App\Middleware\CsrfMiddleware::token(),
                'errors' => [],
                'success' => $message,
                'email' => $email,
                'resetLink' => '',
            ]);
            return;
        }

        $rawToken = bin2hex(random_bytes(32));
        $storedToken = hash('sha256', $rawToken);
        $expiresAt = date('Y-m-d H:i:s', time() + (int) Env::get('PASSWORD_RESET_EXPIRY_SECONDS', '7200'));

        // invalidate older token(s) for this user
        $invalidate = $db->pdo()->prepare('UPDATE password_resets SET used_at = NOW() WHERE user_id = :user_id AND used_at IS NULL');
        $invalidate->execute(['user_id' => $user['user_id']]);

        // insert hashed token
        $insert = $db->pdo()->prepare(
            'INSERT INTO password_resets (user_id, token, expires_at, ip) VALUES (:user_id, :token, :expires_at, :ip)'
        );
        $insert->execute([
            'user_id' => $user['user_id'],
            'token' => $storedToken,
            'expires_at' => $expiresAt,
            'ip' => $ip,
        ]);

        $tenantHost = self::buildTenantHost((string) ($company['subdomain'] ?? 'www'), $context);
        $https = strtolower((string) ($_SERVER['HTTPS'] ?? ''));
        $forwardedProto = strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''));
        $isHttps = $https === 'on' || $https === '1' || $forwardedProto === 'https';
        $scheme = $isHttps ? 'https' : 'http';
        $resetLink = $scheme . '://' . $tenantHost . '/reset-password?token=' . urlencode($rawToken);

        error_log(sprintf(
            "[pwd_reset_created] user_id=%d tenant_id=%d expires_at=%s",
            $user['user_id'],
            $company['company_id'],
            $expiresAt
        ));

        $body = '<p>To reset your password, click the link below:</p>'
            . '<p><a href="' . htmlspecialchars($resetLink, ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($resetLink, ENT_QUOTES, 'UTF-8') . '</a></p>'
            . '<p>If you did not request a password reset, please ignore this email.</p>';

        Mailer::send($email, 'Skyare Password Reset', $body);

        $message = 'If an account with that email exists, a password reset link has been sent.';
        $debug = Env::get('MAIL_DEBUG', '0');
        $showLink = $debug === '1' || strtolower($debug) === 'true';
        View::render('auth/forgot-password', [
            'company' => $company,
            'token' => \App\Middleware\CsrfMiddleware::token(),
            'errors' => [],
            'success' => $message,
            'email' => $email,
            'resetLink' => $showLink ? $resetLink : '',
        ]);
    }

    public static function showResetPassword(Database $db, RequestContext $context): void
    {
        $token = trim((string) ($_GET['token'] ?? ''));

        View::render('auth/reset-password', [
            'company' => $context->company(),
            'token' => \App\Middleware\CsrfMiddleware::token(),
            'errors' => [],
            'success' => '',
            'tokenValue' => $token,
        ]);
    }

    public static function resetPassword(Database $db, RequestContext $context): void
    {
        $token = trim((string) ($_POST['token'] ?? ''));
        $new = (string) ($_POST['new_password'] ?? '');
        $confirm = (string) ($_POST['confirm_password'] ?? '');

        if (mb_strlen($new) < 8) {
            http_response_code(422);
            View::render('auth/reset-password', [
                'company' => $context->company(),
                'token' => \App\Middleware\CsrfMiddleware::token(),
                'errors' => ['Password must be at least 8 characters.'],
                'success' => '',
                'tokenValue' => $token,
            ]);
            return;
        }

        if ($new !== $confirm) {
            http_response_code(422);
            View::render('auth/reset-password', [
                'company' => $context->company(),
                'token' => \App\Middleware\CsrfMiddleware::token(),
                'errors' => ['Password confirmation does not match.'],
                'success' => '',
                'tokenValue' => $token,
            ]);
            return;
        }

        $hashedIncoming = hash('sha256', $token);
        $stmt = $db->pdo()->prepare('SELECT user_id, expires_at, used_at FROM password_resets WHERE token = :token LIMIT 1');
        $stmt->execute(['token' => $hashedIncoming]);
        $row = $stmt->fetch();

        if (!$row || ($row['used_at'] !== null) || strtotime((string) $row['expires_at']) < time()) {
            http_response_code(422);
            View::render('auth/reset-password', [
                'company' => $context->company(),
                'token' => \App\Middleware\CsrfMiddleware::token(),
                'errors' => ['Invalid or expired reset token. Please request a new link.'],
                'success' => '',
                'tokenValue' => $token,
            ]);
            return;
        }

        $userId = (int) $row['user_id'];

        $newHash = password_hash($new, PASSWORD_DEFAULT);

        // Update password_hash column
        $up = $db->pdo()->prepare('UPDATE users SET password_hash = :password_hash WHERE id = :user_id');
        $up->execute(['password_hash' => $newHash, 'user_id' => $userId]);

        // Also update legacy `password` column if it exists, so login COALESCE always works
        try {
            $hasPwCol = (bool) $db->pdo()->query("SHOW COLUMNS FROM users LIKE 'password'")->fetch();
            if ($hasPwCol) {
                $up3 = $db->pdo()->prepare('UPDATE users SET password = :password WHERE id = :user_id');
                $up3->execute(['password' => $newHash, 'user_id' => $userId]);
            }
        } catch (\Throwable $ignored) {}

        $up2 = $db->pdo()->prepare('UPDATE password_resets SET used_at = NOW() WHERE token = :token');
        $up2->execute(['token' => $hashedIncoming]);

        // Build absolute login URL for the tenant so redirect works regardless of subdomain
        $company = $context->company();
        $loginUrl = self::buildTenantLoginUrl((string) ($company['subdomain'] ?? 'www'), $context) . '?reset=1';
        header('Location: ' . $loginUrl);
        exit;
    }

    public static function logout(Database $db, RequestContext $context)
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }

        return redirect('/login');
    }

    private static function availableCompanies(Database $db, RequestContext $context): array
    {
        $rows = (new Company($db->pdo()))->listActive();
        $rows = array_values(array_filter($rows, static fn (array $row): bool => trim((string) ($row['subdomain'] ?? '')) !== ''));

        return array_map(
            static fn (array $row): array => [
                'company_id' => (int) ($row['company_id'] ?? 0),
                'company_name' => (string) ($row['company_name'] ?? ''),
                'subdomain' => (string) ($row['subdomain'] ?? ''),
                'tenant_url' => self::buildTenantLoginUrl((string) ($row['subdomain'] ?? ''), $context),
            ],
            $rows
        );
    }

    private static function buildTenantLoginUrl(string $subdomain, RequestContext $context): string
    {
        $host = self::buildTenantHost($subdomain, $context);

        $https = strtolower((string) ($_SERVER['HTTPS'] ?? ''));
        $forwardedProto = strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''));
        $isHttps = $https === 'on' || $https === '1' || $forwardedProto === 'https';

        $scheme = $isHttps ? 'https' : 'http';
        return $scheme . '://' . $host . '/login';
    }

    private static function buildTenantHost(string $subdomain, RequestContext $context): string
    {
        $host = strtolower(trim($context->host()));
        $baseDomain = strtolower(trim((string) ($context->appConfig()['base_domain'] ?? 'skyare.space')));
        $forwardedPort = trim((string) ($_SERVER['HTTP_X_FORWARDED_PORT'] ?? ''));
        $serverPort = trim((string) ($_SERVER['SERVER_PORT'] ?? ''));
        $portValue = $forwardedPort !== '' ? $forwardedPort : $serverPort;
        $port = ($portValue !== '' && $portValue !== '80' && $portValue !== '443') ? ':' . $portValue : '';

        if ($host === 'localhost' || $host === '127.0.0.1') {
            return $subdomain . '.localhost' . $port;
        }

        if ($baseDomain !== '' && ($host === $baseDomain || str_ends_with($host, '.' . $baseDomain))) {
            return $subdomain . '.' . $baseDomain . $port;
        }

        $parts = $host !== '' ? explode('.', $host) : [];
        if (count($parts) > 2) {
            array_shift($parts);
            return $subdomain . '.' . implode('.', $parts) . $port;
        }

        return $subdomain . '.' . $baseDomain . $port;
    }

    private static function buildTenantLicenseDomain(string $subdomain, RequestContext $context): string
    {
        $host = self::buildTenantHost($subdomain, $context);
        return explode(':', $host, 2)[0];
    }
}
