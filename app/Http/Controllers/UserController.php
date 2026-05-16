<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Core\RequestContext;
use App\Core\Database;
use App\Core\Mailer;
use App\Core\Env;
use App\Models\User;

class UserController extends Controller
{
    public function index(RequestContext $context, Database $db)
    {
        $company = $context->company();
        if (!$company) {
            return response('Company not found', 404);
        }
        $companyId = (int) $company['company_id'];

        $users = [];
        try {
            $stmt = $db->pdo()->prepare(
                'SELECT id AS user_id, COALESCE(full_name, name) AS full_name, email, role_key, is_active, COALESCE(created_at, updated_at) AS created_at
                 FROM users WHERE company_id = :cid ORDER BY id ASC'
            );
            $stmt->execute(['cid' => $companyId]);
            $users = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable $e) {
            // table may be missing columns
        }

        $invitations = [];
        try {
            $stmt = $db->pdo()->prepare(
                'SELECT invitation_id, email, role_key, expires_at, accepted_at, created_at
                 FROM user_invitations WHERE company_id = :cid ORDER BY created_at DESC LIMIT 50'
            );
            $stmt->execute(['cid' => $companyId]);
            $invitations = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable $e) {
            // table may not exist yet
        }

        return view('users.index', [
            'company' => $company,
            'users' => $users,
            'invitations' => $invitations,
        ]);
    }

    public function invite(Request $request, RequestContext $context, Database $db)
    {
        if (($_SESSION['user']['role_key'] ?? '') !== 'admin') {
            return back()->withErrors(['email' => 'Only admins can invite users.']);
        }

        $company = $context->company();
        if (!$company) {
            return response('Company not found', 404);
        }
        $companyId = (int) $company['company_id'];

        $validated = $request->validate([
            'email' => 'required|email|max:191',
            'role_key' => 'required|in:admin,manager,user,viewer',
        ]);

        // Check user doesn't already exist in this tenant
        try {
            $existing = $db->pdo()->prepare('SELECT id FROM users WHERE email = :email AND company_id = :cid LIMIT 1');
            $existing->execute(['email' => $validated['email'], 'cid' => $companyId]);
            if ($existing->fetch()) {
                return back()->withErrors(['email' => 'A user with this email already exists.']);
            }
        } catch (\Throwable $e) {
            return back()->withErrors(['email' => 'Could not verify existing users: ' . $e->getMessage()]);
        }

        // Check for an existing pending (non-expired, non-accepted) invitation — resend if found
        $pendingInv = null;
        try {
            $pendingCheck = $db->pdo()->prepare(
                'SELECT invitation_id, token FROM user_invitations
                 WHERE company_id = :cid AND email = :email AND accepted_at IS NULL AND expires_at > NOW()
                 LIMIT 1'
            );
            $pendingCheck->execute(['cid' => $companyId, 'email' => $validated['email']]);
            $pendingInv = $pendingCheck->fetch(\PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            // If user_invitations table doesn't exist, proceed to create new invitation
        }

        if ($pendingInv) {
            // Resend the email for the existing invitation
            $token = $pendingInv['token'];
            $inviteUrl = request()->getSchemeAndHttpHost() . '/invite/accept?token=' . urlencode($token);
            $body = '<h2>You\'re invited to ' . htmlspecialchars($company['company_name'] ?? 'Skyare', ENT_QUOTES) . '</h2>'
                  . '<p>' . htmlspecialchars($_SESSION['user']['full_name'] ?? 'A team member', ENT_QUOTES) . ' has invited you to join their accounting workspace.</p>'
                  . '<p><a href="' . htmlspecialchars($inviteUrl, ENT_QUOTES) . '" style="display:inline-block;padding:12px 24px;background:#12807a;color:#fff;border-radius:8px;text-decoration:none;font-weight:bold;">Accept Invitation</a></p>'
                  . '<p>If you did not expect this invitation, you can safely ignore it.</p>';

            try {
                Mailer::send($validated['email'], 'You\'re invited to ' . ($company['company_name'] ?? 'Skyare'), $body);
            } catch (\Throwable $e) {
                error_log('[invite_resend_failed] to=' . $validated['email'] . ' error=' . $e->getMessage());
                return redirect('/users')->with('success', 'Invitation exists but email resend failed: ' . $e->getMessage());
            }

            return redirect('/users')->with('success', 'Invitation resent to ' . $validated['email']);
        }

        $token = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', strtotime('+7 days'));
        $invitedBy = (int) ($_SESSION['user']['user_id'] ?? 0) ?: null;

        try {
            $stmt = $db->pdo()->prepare(
                'INSERT INTO user_invitations (company_id, email, role_key, token, invited_by, expires_at, created_at, updated_at)
                 VALUES (:cid, :email, :role_key, :token, :invited_by, :expires_at, NOW(), NOW())'
            );
            $stmt->execute([
                'cid' => $companyId,
                'email' => $validated['email'],
                'role_key' => $validated['role_key'],
                'token' => $token,
                'invited_by' => $invitedBy,
                'expires_at' => $expiresAt,
            ]);
        } catch (\Throwable $e) {
            return back()->withErrors(['email' => 'Could not send invitation: ' . $e->getMessage()]);
        }

        // Send invitation email
        $inviteUrl = request()->getSchemeAndHttpHost() . '/invite/accept?token=' . urlencode($token);
        $body = '<h2>You\'re invited to ' . htmlspecialchars($company['company_name'] ?? 'Skyare', ENT_QUOTES) . '</h2>'
              . '<p>' . htmlspecialchars($_SESSION['user']['full_name'] ?? 'A team member', ENT_QUOTES) . ' has invited you to join their accounting workspace.</p>'
              . '<p><a href="' . htmlspecialchars($inviteUrl, ENT_QUOTES) . '" style="display:inline-block;padding:12px 24px;background:#12807a;color:#fff;border-radius:8px;text-decoration:none;font-weight:bold;">Accept Invitation</a></p>'
              . '<p>This invitation expires on ' . date('M j, Y', strtotime($expiresAt)) . '.</p>';

        try {
            Mailer::send($validated['email'], 'You\'re invited to ' . ($company['company_name'] ?? 'Skyare'), $body);
        } catch (\Throwable $e) {
            error_log('[invite_email_failed] to=' . $validated['email'] . ' error=' . $e->getMessage());
            return redirect('/users')->with('success', 'Invitation created but email delivery failed: ' . $e->getMessage());
        }

        return redirect('/users')->with('success', 'Invitation sent to ' . $validated['email']);
    }

    public function cancelInvitation(int $id, RequestContext $context, Database $db)
    {
        if (($_SESSION['user']['role_key'] ?? '') !== 'admin') {
            return redirect('/users')->withErrors(['email' => 'Only admins can cancel invitations.']);
        }

        $company = $context->company();
        if (!$company) {
            return response('Company not found', 404);
        }
        $companyId = (int) $company['company_id'];

        try {
            $stmt = $db->pdo()->prepare(
                'DELETE FROM user_invitations WHERE invitation_id = :id AND company_id = :cid AND accepted_at IS NULL'
            );
            $stmt->execute(['id' => $id, 'cid' => $companyId]);
        } catch (\Throwable $e) {
            return redirect('/users')->withErrors(['email' => 'Could not cancel invitation.']);
        }

        return redirect('/users')->with('success', 'Invitation cancelled.');
    }

    public function acceptForm(Request $request, Database $db)
    {
        $token = trim((string) $request->query('token', ''));
        if ($token === '') {
            return response('Invalid invitation link.', 400);
        }

        try {
            $stmt = $db->pdo()->prepare(
                'SELECT i.*, c.company_name FROM user_invitations i
                 LEFT JOIN companies c ON c.company_id = i.company_id
                 WHERE i.token = :token AND i.accepted_at IS NULL AND i.expires_at > NOW() LIMIT 1'
            );
            $stmt->execute(['token' => $token]);
            $invitation = $stmt->fetch(\PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            $invitation = null;
        }

        if (!$invitation) {
            return response('Invitation is invalid or expired.', 404);
        }

        return view('users.accept-invite', [
            'invitation' => $invitation,
            'token' => $token,
        ]);
    }

    public function acceptStore(Request $request, Database $db)
    {
        $validated = $request->validate([
            'token' => 'required|string',
            'full_name' => 'required|string|max:191',
            'password' => 'required|string|min:8|confirmed',
        ]);

        try {
            $stmt = $db->pdo()->prepare(
                'SELECT * FROM user_invitations WHERE token = :token AND accepted_at IS NULL AND expires_at > NOW() LIMIT 1'
            );
            $stmt->execute(['token' => $validated['token']]);
            $invitation = $stmt->fetch(\PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            return back()->withErrors(['token' => 'Could not process invitation.']);
        }

        if (!$invitation) {
            return back()->withErrors(['token' => 'Invitation is invalid or expired.']);
        }

        $userModel = new User($db->pdo());

        try {
            $db->pdo()->beginTransaction();

            // Check if user already exists (e.g. from a previous attempt or another company)
            $existingUser = $db->pdo()->prepare('SELECT id, company_id FROM users WHERE email = :email LIMIT 1');
            $existingUser->execute(['email' => $invitation['email']]);
            $existing = $existingUser->fetch(\PDO::FETCH_ASSOC);

            if ($existing) {
                // User already exists — update their details, company, role and activate
                $passwordHash = password_hash($validated['password'], PASSWORD_DEFAULT);

                // Detect password column name
                $pwCol = 'password_hash';
                try {
                    $cols = $db->pdo()->query("SHOW COLUMNS FROM users LIKE 'password'")->fetchAll();
                    if (count($cols) > 0) $pwCol = 'password';
                } catch (\Throwable $e) {}

                $upd = $db->pdo()->prepare(
                    "UPDATE users SET full_name = :name, {$pwCol} = :pw, role_key = :role, company_id = :cid, is_active = 1, created_at = COALESCE(created_at, NOW()), updated_at = NOW() WHERE id = :id"
                );
                $upd->execute([
                    'name' => $validated['full_name'],
                    'pw'   => $passwordHash,
                    'role' => $invitation['role_key'],
                    'cid'  => $invitation['company_id'],
                    'id'   => $existing['id'],
                ]);
            } else {
                $userModel->createAdmin(
                    (int) $invitation['company_id'],
                    $validated['full_name'],
                    $invitation['email'],
                    password_hash($validated['password'], PASSWORD_DEFAULT)
                );

                // Update the role to what was specified in invitation
                $updateRole = $db->pdo()->prepare('UPDATE users SET role_key = :role WHERE email = :email AND company_id = :cid');
                $updateRole->execute([
                    'role' => $invitation['role_key'],
                    'email' => $invitation['email'],
                    'cid' => $invitation['company_id'],
                ]);
            }

            $db->pdo()->prepare('UPDATE user_invitations SET accepted_at = NOW() WHERE invitation_id = :id')
                ->execute(['id' => $invitation['invitation_id']]);

            $db->pdo()->commit();
        } catch (\Throwable $e) {
            if ($db->pdo()->inTransaction()) $db->pdo()->rollBack();
            return back()->withErrors(['token' => 'Registration failed: ' . $e->getMessage()]);
        }

        return redirect('/login')->with('success', 'Account created! You can now log in.');
    }

    public function toggleStatus(Request $request, $id, RequestContext $context, Database $db)
    {
        if (($_SESSION['user']['role_key'] ?? '') !== 'admin') {
            return back()->withErrors(['user' => 'Only admins can change user status.']);
        }

        $company = $context->company();
        if (!$company) {
            return response('Company not found', 404);
        }
        $companyId = (int) $company['company_id'];
        $currentUserId = (int) ($_SESSION['user']['user_id'] ?? 0);

        if ((int) $id === $currentUserId) {
            return back()->withErrors(['user' => 'You cannot deactivate your own account.']);
        }

        try {
            $stmt = $db->pdo()->prepare('UPDATE users SET is_active = NOT is_active WHERE id = :id AND company_id = :cid');
            $stmt->execute(['id' => (int) $id, 'cid' => $companyId]);
        } catch (\Throwable $e) {
            return back()->withErrors(['user' => 'Could not update user status.']);
        }

        return redirect('/users')->with('success', 'User status updated.');
    }

    public function updateRole(Request $request, $id, RequestContext $context, Database $db)
    {
        if (($_SESSION['user']['role_key'] ?? '') !== 'admin') {
            return back()->withErrors(['user' => 'Only admins can change user roles.']);
        }

        $company = $context->company();
        if (!$company) {
            return response('Company not found', 404);
        }
        $companyId = (int) $company['company_id'];

        $validated = $request->validate([
            'role_key' => 'required|in:admin,manager,user,viewer',
        ]);

        try {
            $stmt = $db->pdo()->prepare('UPDATE users SET role_key = :role WHERE id = :id AND company_id = :cid');
            $stmt->execute(['role' => $validated['role_key'], 'id' => (int) $id, 'cid' => $companyId]);
        } catch (\Throwable $e) {
            return back()->withErrors(['user' => 'Could not update user role.']);
        }

        return redirect('/users')->with('success', 'User role updated.');
    }

    public function sendPasswordReset($id, RequestContext $context, Database $db)
    {
        if (($_SESSION['user']['role_key'] ?? '') !== 'admin') {
            return back()->withErrors(['user' => 'Only admins can initiate password resets.']);
        }

        $company = $context->company();
        if (!$company) {
            return response('Company not found', 404);
        }
        $companyId = (int) $company['company_id'];

        // Fetch the target user
        $user = null;
        try {
            $stmt = $db->pdo()->prepare('SELECT id AS user_id, email, COALESCE(full_name, name) AS full_name FROM users WHERE id = :id AND company_id = :cid LIMIT 1');
            $stmt->execute(['id' => (int) $id, 'cid' => $companyId]);
            $user = $stmt->fetch(\PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            return back()->withErrors(['user' => 'Could not retrieve user details.']);
        }

        if (!$user) {
            return back()->withErrors(['user' => 'User not found.']);
        }

        // Cannot reset your own password from here
        if ((int) $user['user_id'] === (int) ($_SESSION['user']['user_id'] ?? 0)) {
            return back()->withErrors(['user' => 'Use your profile page to change your own password.']);
        }

        // Generate a secure token — store hashed, send raw
        $rawToken = bin2hex(random_bytes(32));
        $storedToken = hash('sha256', $rawToken);
        $expiresAt = date('Y-m-d H:i:s', time() + (int) Env::get('PASSWORD_RESET_EXPIRY_SECONDS', '7200'));

        try {
            // Invalidate any existing tokens for this user
            $invalidate = $db->pdo()->prepare('UPDATE password_resets SET used_at = NOW() WHERE user_id = :uid AND used_at IS NULL');
            $invalidate->execute(['uid' => $user['user_id']]);

            // Insert new token
            $insert = $db->pdo()->prepare(
                'INSERT INTO password_resets (user_id, token, expires_at, ip) VALUES (:uid, :token, :expires_at, :ip)'
            );
            $insert->execute([
                'uid' => $user['user_id'],
                'token' => $storedToken,
                'expires_at' => $expiresAt,
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'admin-initiated',
            ]);
        } catch (\Throwable $e) {
            return back()->withErrors(['user' => 'Could not create password reset token. Ensure migrations have been run.']);
        }

        // Build the reset link for this tenant
        $subdomain = $company['subdomain'] ?? 'www';
        $host = request()->getSchemeAndHttpHost();
        $resetLink = rtrim($host, '/') . '/reset-password?token=' . urlencode($rawToken);

        $adminName = htmlspecialchars($_SESSION['user']['full_name'] ?? 'Your administrator', ENT_QUOTES);
        $companyName = htmlspecialchars($company['company_name'] ?? 'Skyare', ENT_QUOTES);
        $userName = htmlspecialchars($user['full_name'] ?? 'Team member', ENT_QUOTES);

        $body = '<h2>Password Reset Requested</h2>'
              . '<p>Hi ' . $userName . ',</p>'
              . '<p>' . $adminName . ' has initiated a password reset for your account at <strong>' . $companyName . '</strong>.</p>'
              . '<p><a href="' . htmlspecialchars($resetLink, ENT_QUOTES) . '" style="display:inline-block;padding:12px 24px;background:#12807a;color:#fff;border-radius:8px;text-decoration:none;font-weight:bold;">Reset Your Password</a></p>'
              . '<p>This link expires on ' . date('M j, Y \a\t H:i', strtotime($expiresAt)) . '.</p>'
              . '<p>If you did not expect this, please contact your administrator.</p>';

        try {
            Mailer::send($user['email'], 'Password Reset - ' . ($company['company_name'] ?? 'Skyare'), $body);
        } catch (\Throwable $e) {
            error_log('[admin_pwd_reset_email_failed] to=' . $user['email'] . ' error=' . $e->getMessage());
            return redirect('/users')->with('success', 'Reset token created but email delivery failed.');
        }

        return redirect('/users')->with('success', 'Password reset link sent to ' . $user['email']);
    }
}
