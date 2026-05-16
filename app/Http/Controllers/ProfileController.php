<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Core\RequestContext;
use App\Core\Database;

class ProfileController extends Controller
{
    public function index(RequestContext $context, Database $db)
    {
        $company = $context->company();
        $user = $_SESSION['user'] ?? null;
        if (!$user) {
            return redirect('/login');
        }

        $fullUser = null;
        try {
            $stmt = $db->pdo()->prepare(
                'SELECT id AS user_id, COALESCE(full_name, name) AS full_name, email, role_key, is_active, created_at,
                        phone, timezone, date_format, currency_symbol, last_login_at, last_login_ip
                 FROM users WHERE id = :id LIMIT 1'
            );
            $stmt->execute(['id' => (int) $user['user_id']]);
            $fullUser = $stmt->fetch(\PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            // Fallback without preference columns (migration may not have run)
            try {
                $stmt = $db->pdo()->prepare('SELECT id AS user_id, COALESCE(full_name, name) AS full_name, email, role_key, is_active, created_at FROM users WHERE id = :id LIMIT 1');
                $stmt->execute(['id' => (int) $user['user_id']]);
                $fullUser = $stmt->fetch(\PDO::FETCH_ASSOC);
            } catch (\Throwable $e2) {}
        }

        $notifications = [];
        try {
            $stmt = $db->pdo()->prepare(
                'SELECT * FROM notifications WHERE company_id = :cid AND (user_id = :uid OR user_id IS NULL) ORDER BY created_at DESC LIMIT 20'
            );
            $stmt->execute(['cid' => (int) ($company['company_id'] ?? 0), 'uid' => (int) $user['user_id']]);
            $notifications = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable $e) {}

        $activities = [];
        try {
            $stmt = $db->pdo()->prepare(
                'SELECT * FROM activity_feed WHERE company_id = :cid ORDER BY created_at DESC LIMIT 30'
            );
            $stmt->execute(['cid' => (int) ($company['company_id'] ?? 0)]);
            $activities = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable $e) {}

        // Login history — last 10 logins from audit log (if available)
        $loginHistory = [];
        try {
            $stmt = $db->pdo()->prepare(
                "SELECT created_at, ip_address, user_agent FROM audit_logs
                 WHERE company_id = :cid AND user_id = :uid AND action LIKE '%login%'
                 ORDER BY created_at DESC LIMIT 10"
            );
            $stmt->execute(['cid' => (int) ($company['company_id'] ?? 0), 'uid' => (int) $user['user_id']]);
            $loginHistory = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable $e) {}

        return view('profile.index', [
            'company' => $company,
            'user' => $fullUser ?? $user,
            'notifications' => $notifications,
            'activities' => $activities,
            'loginHistory' => $loginHistory,
        ]);
    }

    public function update(Request $request, RequestContext $context, Database $db)
    {
        $user = $_SESSION['user'] ?? null;
        if (!$user) {
            return redirect('/login');
        }

        $validated = $request->validate([
            'full_name' => 'required|string|max:191',
            'email' => 'required|email|max:191',
        ]);

        try {
            // Update full_name (handle both column names)
            $db->pdo()->prepare('UPDATE users SET email = :email WHERE id = :id')
                ->execute(['email' => $validated['email'], 'id' => (int) $user['user_id']]);

            try {
                $db->pdo()->prepare('UPDATE users SET full_name = :name WHERE id = :id')
                    ->execute(['name' => $validated['full_name'], 'id' => (int) $user['user_id']]);
            } catch (\Throwable $e) {
                $db->pdo()->prepare('UPDATE users SET name = :name WHERE id = :id')
                    ->execute(['name' => $validated['full_name'], 'id' => (int) $user['user_id']]);
            }

            // Update session
            $_SESSION['user']['full_name'] = $validated['full_name'];
            $_SESSION['user']['email'] = $validated['email'];
            if (function_exists('session')) {
                session(['user' => $_SESSION['user']]);
            }
        } catch (\Throwable $e) {
            return back()->withErrors(['full_name' => 'Could not update profile.']);
        }

        return redirect('/profile')->with('success', 'Profile updated.');
    }

    public function changePassword(Request $request, RequestContext $context, Database $db)
    {
        $user = $_SESSION['user'] ?? null;
        if (!$user) {
            return redirect('/login');
        }

        $validated = $request->validate([
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:8|confirmed',
        ]);

        // Verify current password
        try {
            $stmt = $db->pdo()->prepare('SELECT COALESCE(password_hash, password) AS password_hash FROM users WHERE id = :id LIMIT 1');
            $stmt->execute(['id' => (int) $user['user_id']]);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$row || !password_verify($validated['current_password'], $row['password_hash'])) {
                return back()->withErrors(['current_password' => 'Current password is incorrect.']);
            }

            $hashed = password_hash($validated['new_password'], PASSWORD_DEFAULT);
            $db->pdo()->prepare('UPDATE users SET password_hash = :ph WHERE id = :id')
                ->execute(['ph' => $hashed, 'id' => (int) $user['user_id']]);

            try {
                $db->pdo()->prepare('UPDATE users SET password = :ph WHERE id = :id')
                    ->execute(['ph' => $hashed, 'id' => (int) $user['user_id']]);
            } catch (\Throwable $e) {}

        } catch (\Throwable $e) {
            return back()->withErrors(['current_password' => 'Could not change password.']);
        }

        return redirect('/profile')->with('success', 'Password changed successfully.');
    }

    public function updatePreferences(Request $request, RequestContext $context, Database $db)
    {
        $user = $_SESSION['user'] ?? null;
        if (!$user) {
            return redirect('/login');
        }

        $validated = $request->validate([
            'phone' => 'nullable|string|max:50',
            'timezone' => 'required|string|max:64',
            'date_format' => 'required|in:Y-m-d,d/m/Y,m/d/Y,d-m-Y,d M Y',
            'currency_symbol' => 'required|string|max:10',
        ]);

        try {
            $db->pdo()->prepare(
                'UPDATE users SET phone = :phone, timezone = :tz, date_format = :df, currency_symbol = :cs WHERE id = :id'
            )->execute([
                'phone' => $validated['phone'] ?? null,
                'tz' => $validated['timezone'],
                'df' => $validated['date_format'],
                'cs' => $validated['currency_symbol'],
                'id' => (int) $user['user_id'],
            ]);

            if (!isset($_SESSION['user']) || !is_array($_SESSION['user'])) {
                $_SESSION['user'] = [];
            }
            $_SESSION['user']['phone'] = $validated['phone'] ?? null;
            $_SESSION['user']['timezone'] = $validated['timezone'];
            $_SESSION['user']['date_format'] = $validated['date_format'];
            $_SESSION['user']['currency_symbol'] = $validated['currency_symbol'];
            if (function_exists('session')) {
                session(['user' => $_SESSION['user']]);
            }
        } catch (\Throwable $e) {
            return back()->withErrors(['timezone' => 'Could not save preferences. Please run database migrations.']);
        }

        return redirect('/profile')->with('success', 'Preferences saved.');
    }

    public function deleteAccount(Request $request, RequestContext $context, Database $db)
    {
        $user = $_SESSION['user'] ?? null;
        if (!$user) {
            return redirect('/login');
        }

        // Only admin users may deactivate accounts
        $roleKey = $user['role_key'] ?? '';
        if ($roleKey !== 'admin') {
            return back()->withErrors(['confirm_password' => 'Only administrators can deactivate accounts.']);
        }

        $validated = $request->validate([
            'confirm_password' => 'required|string',
        ]);

        try {
            $stmt = $db->pdo()->prepare('SELECT COALESCE(password_hash, password) AS password_hash FROM users WHERE id = :id LIMIT 1');
            $stmt->execute(['id' => (int) $user['user_id']]);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$row || !password_verify($validated['confirm_password'], $row['password_hash'])) {
                return back()->withErrors(['confirm_password' => 'Password is incorrect.']);
            }

            // Deactivate (soft-delete) the user instead of hard delete
            $db->pdo()->prepare('UPDATE users SET is_active = 0 WHERE id = :id')
                ->execute(['id' => (int) $user['user_id']]);

            // Destroy session
            $_SESSION = [];
            if (function_exists('session')) {
                session()->flush();
            }

            return redirect('/login')->with('success', 'Your account has been deactivated.');
        } catch (\Throwable $e) {
            return back()->withErrors(['confirm_password' => 'Could not deactivate account.']);
        }
    }

    public function notifications(RequestContext $context, Database $db)
    {
        $company = $context->company();
        $user = $_SESSION['user'] ?? null;
        if (!$user || !$company) {
            return redirect('/login');
        }

        $notifications = [];
        try {
            $stmt = $db->pdo()->prepare(
                'SELECT * FROM notifications WHERE company_id = :cid AND (user_id = :uid OR user_id IS NULL) ORDER BY created_at DESC LIMIT 50'
            );
            $stmt->execute(['cid' => (int) $company['company_id'], 'uid' => (int) $user['user_id']]);
            $notifications = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable $e) {}

        // Mark all as read
        try {
            $db->pdo()->prepare(
                'UPDATE notifications SET read_at = NOW() WHERE company_id = :cid AND (user_id = :uid OR user_id IS NULL) AND read_at IS NULL'
            )->execute(['cid' => (int) $company['company_id'], 'uid' => (int) $user['user_id']]);
        } catch (\Throwable $e) {}

        return view('profile.notifications', [
            'company' => $company,
            'user' => $user,
            'notifications' => $notifications,
        ]);
    }
}
