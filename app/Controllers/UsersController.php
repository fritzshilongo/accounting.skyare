<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\AuditLogger;
use App\Core\Database;
use App\Core\RequestContext;
use App\Core\View;
use App\Models\User;

final class UsersController
{
    public static function index(Database $db, RequestContext $context): void
    {
        $companyId = self::companyId($context);
        if ($companyId === null) { self::deny(); return; }

        $currentRole = self::currentRole();
        $rows = self::listByCompany($db, $companyId);
        View::render('users/index', [
            'company' => $context->company(),
            'rows' => $rows,
            'token' => \App\Middleware\CsrfMiddleware::token(),
            'errors' => [],
            'old' => ['full_name' => '', 'email' => '', 'role_key' => 'sales', 'is_active' => '1'],
            'roles' => self::assignableRoles($currentRole),
            'all_roles' => array_keys(require dirname(__DIR__, 2) . '/config/role-modules.php'),
            'current_role' => $currentRole,
            'current_user_id' => (int) ($_SESSION['user']['user_id'] ?? 0),
        ]);
    }

    public static function store(Database $db, RequestContext $context): void
    {
        $companyId = self::companyId($context);
        if ($companyId === null) { self::deny(); return; }

        $fullName = trim((string) ($_POST['full_name'] ?? ''));
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        $password = (string) ($_POST['password'] ?? '');
        $roleKey = trim((string) ($_POST['role_key'] ?? 'sales'));
        $isActive = (string) ($_POST['is_active'] ?? '1') === '1';
        $currentRole = self::currentRole();

        if (!self::canManageUsers($currentRole)) {
            http_response_code(403);
            echo 'Only admins can manage users.';
            return;
        }

        $roles = self::assignableRoles($currentRole);
        $errors = [];
        if ($fullName === '') { $errors[] = 'Full name is required.'; }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { $errors[] = 'Valid email is required.'; }
        if (mb_strlen($password) < 8) { $errors[] = 'Password must be at least 8 characters.'; }
        if (!in_array($roleKey, $roles, true)) { $errors[] = 'Invalid role selected.'; }

        if ($errors !== []) {
            http_response_code(422);
            View::render('users/index', [
                'company' => $context->company(),
                'rows' => self::listByCompany($db, $companyId),
                'token' => \App\Middleware\CsrfMiddleware::token(),
                'errors' => $errors,
                'old' => ['full_name' => $fullName, 'email' => $email, 'role_key' => $roleKey, 'is_active' => $isActive ? '1' : '0'],
                'roles' => $roles,
                'all_roles' => array_keys(require dirname(__DIR__, 2) . '/config/role-modules.php'),
                'current_role' => $currentRole,
                'current_user_id' => (int) ($_SESSION['user']['user_id'] ?? 0),
            ]);
            return;
        }

        $stmt = $db->pdo()->prepare(
            'INSERT INTO users (company_id, full_name, email, password_hash, role_key, is_active)
             VALUES (:company_id, :full_name, :email, :password_hash, :role_key, :is_active)'
        );
        $stmt->execute([
            'company_id' => $companyId,
            'full_name' => $fullName,
            'email' => $email,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'role_key' => $roleKey,
            'is_active' => $isActive ? 1 : 0,
        ]);

        $newId = (int) $db->pdo()->lastInsertId();
        AuditLogger::log($db, $context, 'user.create', 'user', (string) $newId, 'Created user: ' . $email);
        header('Location: /users');
    }

    public static function update(Database $db, RequestContext $context): void
    {
        $companyId = self::companyId($context);
        if ($companyId === null) { self::deny(); return; }

        $userId = (int) ($_POST['user_id'] ?? 0);
        $fullName = trim((string) ($_POST['full_name'] ?? ''));
        $roleKey = trim((string) ($_POST['role_key'] ?? 'sales'));
        $isActive = (string) ($_POST['is_active'] ?? '1') === '1';
        $password = (string) ($_POST['password'] ?? '');
        $currentRole = self::currentRole();

        if (!self::canManageUsers($currentRole)) {
            http_response_code(403);
            echo 'Only admins can manage users.';
            return;
        }

        $target = self::findUserByIdForCompany($db, $companyId, $userId);
        if ($target === null) {
            http_response_code(404);
            echo 'User not found.';
            return;
        }

        $roles = self::assignableRoles($currentRole);
        if ($userId <= 0 || $fullName === '' || !in_array($roleKey, $roles, true)) {
            http_response_code(422);
            echo 'Invalid user update payload.';
            return;
        }

        $currentUserId = (int) ($_SESSION['user']['user_id'] ?? 0);
        $targetRole = (string) ($target['role_key'] ?? '');
        if ($userId === $currentUserId && ($roleKey !== $targetRole || $isActive !== ((int) ($target['is_active'] ?? 0) === 1))) {
            http_response_code(422);
            echo 'You cannot change your own role or status.';
            return;
        }

        if ($userId !== $currentUserId && !self::canManageTargetRole($currentRole, $targetRole)) {
            http_response_code(403);
            echo 'You cannot manage a user with this role.';
            return;
        }

        $sql = 'UPDATE users SET full_name = :full_name, role_key = :role_key, is_active = :is_active';
        $params = [
            'full_name' => $fullName,
            'role_key' => $roleKey,
            'is_active' => $isActive ? 1 : 0,
            'user_id' => $userId,
            'company_id' => $companyId,
        ];

        if ($password !== '') {
            if (mb_strlen($password) < 8) {
                http_response_code(422);
                echo 'Password must be at least 8 characters.';
                return;
            }

            $sql .= ', password_hash = :password_hash';
            $params['password_hash'] = password_hash($password, PASSWORD_DEFAULT);
        }

        $sql .= ' WHERE user_id = :user_id AND company_id = :company_id';
        $stmt = $db->pdo()->prepare($sql);
        $stmt->execute($params);

        AuditLogger::log($db, $context, 'user.update', 'user', (string) $userId, 'Updated user profile/role');
        header('Location: /users');
    }

    public static function delete(Database $db, RequestContext $context): void
    {
        $companyId = self::companyId($context);
        if ($companyId === null) { self::deny(); return; }

        $userId = (int) ($_POST['user_id'] ?? 0);
        $currentUser = (int) ($_SESSION['user']['user_id'] ?? 0);
        $currentRole = self::currentRole();
        if (!self::canManageUsers($currentRole)) {
            http_response_code(403);
            echo 'Only admins can manage users.';
            return;
        }
        if ($userId <= 0 || $userId === $currentUser) {
            http_response_code(422);
            echo 'Invalid delete operation.';
            return;
        }

        $target = self::findUserByIdForCompany($db, $companyId, $userId);
        if ($target === null) {
            http_response_code(404);
            echo 'User not found.';
            return;
        }
        if (!self::canManageTargetRole($currentRole, (string) ($target['role_key'] ?? ''))) {
            http_response_code(403);
            echo 'You cannot delete a user with this role.';
            return;
        }

        $stmt = $db->pdo()->prepare('DELETE FROM users WHERE user_id = :user_id AND company_id = :company_id');
        $stmt->execute(['user_id' => $userId, 'company_id' => $companyId]);

        AuditLogger::log($db, $context, 'user.delete', 'user', (string) $userId, 'Deleted user');
        header('Location: /users');
    }

    public static function adminResetPassword(Database $db, RequestContext $context): void
    {
        $companyId = self::companyId($context);
        if ($companyId === null) { self::deny(); return; }

        // Only admin role can reset other users' passwords
        $currentRole = (string) ($_SESSION['user']['role_key'] ?? '');
        if (!self::canManageUsers($currentRole)) {
            http_response_code(403);
            echo 'Only admins can reset user passwords.';
            return;
        }

        $targetUserId = (int) ($_POST['user_id'] ?? 0);
        $newPassword  = (string) ($_POST['new_password'] ?? '');

        if ($targetUserId <= 0) {
            http_response_code(422);
            echo 'Invalid user.';
            return;
        }
        if (mb_strlen($newPassword) < 8) {
            http_response_code(422);
            echo 'Password must be at least 8 characters.';
            return;
        }

        $target = self::findUserByIdForCompany($db, $companyId, $targetUserId);
        if ($target === null) {
            http_response_code(404);
            echo 'User not found.';
            return;
        }
        if (!self::canManageTargetRole($currentRole, (string) ($target['role_key'] ?? ''))) {
            http_response_code(403);
            echo 'You cannot reset this user\'s password.';
            return;
        }

        $stmt = $db->pdo()->prepare(
            'UPDATE users SET password_hash = :password_hash WHERE user_id = :user_id AND company_id = :company_id'
        );
        $stmt->execute([
            'password_hash' => password_hash($newPassword, PASSWORD_DEFAULT),
            'user_id'       => $targetUserId,
            'company_id'    => $companyId,
        ]);

        if ($stmt->rowCount() === 0) {
            http_response_code(404);
            echo 'User not found.';
            return;
        }

        AuditLogger::log($db, $context, 'user.admin_reset_password', 'user', (string) $targetUserId, 'Admin reset password for user #' . $targetUserId);
        header('Location: /users?reset=1');
    }

    private static function listByCompany(Database $db, int $companyId): array
    {
        $stmt = $db->pdo()->prepare('SELECT user_id, full_name, email, role_key, is_active, created_at FROM users WHERE company_id = :company_id ORDER BY user_id DESC');
        $stmt->execute(['company_id' => $companyId]);

        return $stmt->fetchAll() ?: [];
    }

    public static function rolesIndex(Database $db, RequestContext $context): void
    {
        $companyId = self::companyId($context);
        if ($companyId === null) { self::deny(); return; }

        $currentRole = self::currentRole();
        if (!self::canManageUsers($currentRole)) {
            http_response_code(403);
            echo 'Only admins can manage role permissions.';
            return;
        }

        $allRoles   = self::editablePermissionRoles();
        $allModules = ['invoices', 'customers', 'products', 'inventory', 'users', 'audit_trail',
                       'credit_management', 'sales', 'estimates', 'expenses', 'reports',
                       'exchange_rates', 'change_password'];

        $selectedRole = trim((string) ($_GET['role'] ?? ($allRoles[2] ?? '')));
        if (!in_array($selectedRole, $allRoles, true)) {
            $selectedRole = $allRoles[2] ?? '';
        }

        $stmt = $db->pdo()->prepare(
            'SELECT module_key, can_view, can_create, can_edit, can_delete
             FROM role_permissions
             WHERE company_id = :company_id AND role_key = :role_key'
        );
        $stmt->execute(['company_id' => $companyId, 'role_key' => $selectedRole]);
        $dbPerms = [];
        foreach ($stmt->fetchAll() ?: [] as $row) {
            $dbPerms[$row['module_key']] = $row;
        }

        $configMap     = require dirname(__DIR__, 2) . '/config/role-modules.php';
        $configAllowed = $configMap[$selectedRole] ?? [];

        $perms = [];
        foreach ($allModules as $mod) {
            if (isset($dbPerms[$mod])) {
                $perms[$mod] = [
                    'view'   => (bool) $dbPerms[$mod]['can_view'],
                    'create' => (bool) $dbPerms[$mod]['can_create'],
                    'edit'   => (bool) $dbPerms[$mod]['can_edit'],
                    'delete' => (bool) $dbPerms[$mod]['can_delete'],
                ];
            } else {
                $all = in_array('*', $configAllowed, true) || in_array($mod, $configAllowed, true);
                $perms[$mod] = ['view' => $all, 'create' => $all, 'edit' => $all, 'delete' => $all];
            }
        }

        View::render('users/roles', [
            'company'       => $context->company(),
            'roles'         => $allRoles,
            'modules'       => $allModules,
            'selected_role' => $selectedRole,
            'perms'         => $perms,
            'allowed_modules' => $configAllowed,
            'token'         => \App\Middleware\CsrfMiddleware::token(),
            'saved'         => isset($_GET['saved']),
        ]);
    }

    public static function rolesSave(Database $db, RequestContext $context): void
    {
        $companyId = self::companyId($context);
        if ($companyId === null) { self::deny(); return; }

        $currentRole = self::currentRole();
        if (!self::canManageUsers($currentRole)) {
            http_response_code(403);
            echo 'Only admins can manage role permissions.';
            return;
        }

        $configMap = require dirname(__DIR__, 2) . '/config/role-modules.php';
        $allRoles = self::editablePermissionRoles();
        $roleKey  = trim((string) ($_POST['role_key'] ?? ''));
        if (!in_array($roleKey, $allRoles, true)) {
            http_response_code(422);
            echo 'Invalid role selected.';
            return;
        }

        $allModules = ['invoices', 'customers', 'products', 'inventory', 'users', 'audit_trail',
                       'credit_management', 'sales', 'estimates', 'expenses', 'reports',
                       'exchange_rates', 'change_password'];

        $pdo = $db->pdo();
        $pdo->beginTransaction();
        try {
            $del = $pdo->prepare('DELETE FROM role_permissions WHERE company_id = :company_id AND role_key = :role_key');
            $del->execute(['company_id' => $companyId, 'role_key' => $roleKey]);

            $ins = $pdo->prepare(
                'INSERT INTO role_permissions (company_id, role_key, module_key, can_view, can_create, can_edit, can_delete)
                 VALUES (:company_id, :role_key, :module_key, :can_view, :can_create, :can_edit, :can_delete)'
            );
            $posted = $_POST['perms'] ?? [];
            $configAllowed = $configMap[$roleKey] ?? [];
            foreach ($allModules as $mod) {
                $mp = $posted[$mod] ?? [];
                $moduleAllowed = in_array('*', $configAllowed, true) || in_array($mod, $configAllowed, true);
                $view = $moduleAllowed && (isset($mp['view']) || isset($mp['create']) || isset($mp['edit']) || isset($mp['delete']));
                $create = $moduleAllowed && $view && isset($mp['create']);
                $edit = $moduleAllowed && $view && isset($mp['edit']);
                $delete = $moduleAllowed && $view && isset($mp['delete']);
                $ins->execute([
                    'company_id' => $companyId,
                    'role_key'   => $roleKey,
                    'module_key' => $mod,
                    'can_view'   => $view ? 1 : 0,
                    'can_create' => $create ? 1 : 0,
                    'can_edit'   => $edit ? 1 : 0,
                    'can_delete' => $delete ? 1 : 0,
                ]);
            }
            $pdo->commit();
            AuditLogger::log($db, $context, 'roles.update', 'role_permissions', $roleKey, 'Updated permissions for role: ' . $roleKey);
        } catch (\Throwable $e) {
            $pdo->rollBack();
            http_response_code(500);
            echo 'Failed to save role permissions.';
            return;
        }

        header('Location: /users/roles?role=' . urlencode($roleKey) . '&saved=1');
    }

    private static function companyId(RequestContext $context): ?int
    {
        $cid = (int) ($context->company()['company_id'] ?? 0);
        $sid = (int) ($_SESSION['user']['company_id'] ?? 0);
        return ($cid > 0 && $sid > 0 && $cid === $sid) ? $cid : null;
    }

    private static function currentRole(): string
    {
        return (string) ($_SESSION['user']['role_key'] ?? '');
    }

    private static function canManageUsers(string $roleKey): bool
    {
        return in_array($roleKey, ['admin', 'primary_admin'], true);
    }

    private static function assignableRoles(string $actorRole): array
    {
        $allRoles = array_keys(require dirname(__DIR__, 2) . '/config/role-modules.php');
        if ($actorRole === 'primary_admin') {
            return $allRoles;
        }
        if ($actorRole === 'admin') {
            return array_values(array_filter($allRoles, static fn(string $role): bool => $role !== 'primary_admin'));
        }

        return [];
    }

    private static function editablePermissionRoles(): array
    {
        return array_values(array_filter(
            array_keys(require dirname(__DIR__, 2) . '/config/role-modules.php'),
            static fn(string $role): bool => !in_array($role, ['admin', 'primary_admin'], true)
        ));
    }

    private static function roleRank(string $roleKey): int
    {
        return [
            'sales' => 10,
            'inventory' => 20,
            'inventory_manager' => 30,
            'creditor' => 40,
            'accountant' => 50,
            'admin' => 90,
            'primary_admin' => 100,
        ][$roleKey] ?? 0;
    }

    private static function canManageTargetRole(string $actorRole, string $targetRole): bool
    {
        return self::roleRank($actorRole) > self::roleRank($targetRole);
    }

    private static function findUserByIdForCompany(Database $db, int $companyId, int $userId): ?array
    {
        $stmt = $db->pdo()->prepare(
            'SELECT user_id, full_name, email, role_key, is_active
             FROM users
             WHERE user_id = :user_id AND company_id = :company_id
             LIMIT 1'
        );
        $stmt->execute(['user_id' => $userId, 'company_id' => $companyId]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    private static function deny(): void
    {
        http_response_code(403);
        echo 'Tenant context is invalid.';
    }
}
