@extends('layouts.app')

@section('title', 'Users - ' . ($company['company_name'] ?? 'Skyare'))

@section('content')
<div class="hero-card">
    <div class="toolbar">
        <div>
            <h1 class="hero-title">Team Management</h1>
            <p class="hero-copy">Manage users and invite new team members to your workspace.</p>
        </div>
    </div>
</div>

{{-- Invite Form (Admin only) --}}
@if(($_SESSION['user']['role_key'] ?? '') === 'admin')
<div class="form-card">
    <h3 class="section-title" style="margin-bottom:18px;"><i class="fas fa-paper-plane" style="color:var(--teal);margin-right:8px;"></i>Invite Team Member</h3>
    <form method="POST" action="/users/invite">
        <input type="hidden" name="_token" value="{{ \App\Middleware\CsrfMiddleware::token() }}">
        <div class="form-grid three">
            <div>
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" placeholder="user@example.com" required value="{{ old('email') }}">
            </div>
            <div>
                <label for="role_key">Role</label>
                <select id="role_key" name="role_key">
                    <option value="user">User</option>
                    <option value="viewer">Viewer (Read-only)</option>
                    <option value="manager">Manager</option>
                    <option value="admin">Admin</option>
                </select>
            </div>
            <div style="display:flex;align-items:flex-end;">
                <button type="submit" class="btn btn-primary"><i class="fas fa-envelope" style="margin-right:6px;"></i>Send Invitation</button>
            </div>
        </div>
    </form>
</div>
@endif

{{-- Active Users --}}
<div class="card">
    <h3 class="section-title" style="margin-bottom:18px;">Active Users</h3>
    @if(count($users) > 0)
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Joined</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($users as $u)
                        <tr>
                            <td><div class="row-title">{{ $u['full_name'] ?? '-' }}</div></td>
                            <td>{{ $u['email'] ?? '-' }}</td>
                            <td>
                                @if(($_SESSION['user']['role_key'] ?? '') === 'admin')
                                <form method="POST" action="/users/{{ $u['user_id'] }}/role" style="display:inline;">
                                    <input type="hidden" name="_token" value="{{ \App\Middleware\CsrfMiddleware::token() }}">
                                    <select name="role_key" onchange="this.form.submit()" style="padding:8px 12px;border-radius:999px;font-size:12px;min-width:100px;">
                                        @foreach(['admin','manager','user','viewer'] as $role)
                                            <option value="{{ $role }}" {{ ($u['role_key'] ?? '') === $role ? 'selected' : '' }}>{{ ucfirst($role) }}</option>
                                        @endforeach
                                    </select>
                                </form>
                                @else
                                    <span class="badge navy">{{ ucfirst($u['role_key'] ?? 'user') }}</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge {{ ($u['is_active'] ?? 1) ? 'teal' : 'rose' }}">
                                    {{ ($u['is_active'] ?? 1) ? 'Active' : 'Disabled' }}
                                </span>
                            </td>
                            <td>{{ isset($u['created_at']) ? date('M j, Y', strtotime($u['created_at'])) : '-' }}</td>
                            <td>
                                @if(($_SESSION['user']['role_key'] ?? '') === 'admin')
                                <div style="display:flex;gap:6px;flex-wrap:wrap;">
                                    <form method="POST" action="/users/{{ $u['user_id'] }}/toggle" style="display:inline;">
                                        <input type="hidden" name="_token" value="{{ \App\Middleware\CsrfMiddleware::token() }}">
                                        <button type="submit" class="btn btn-sm {{ ($u['is_active'] ?? 1) ? 'btn-danger' : 'btn-secondary' }}">
                                            {{ ($u['is_active'] ?? 1) ? 'Disable' : 'Enable' }}
                                        </button>
                                    </form>
                                    @if((int)($u['user_id'] ?? 0) !== (int)($_SESSION['user']['user_id'] ?? 0))
                                    <form method="POST" action="/users/{{ $u['user_id'] }}/reset-password" style="display:inline;" onsubmit="return confirm('Send a password reset email to {{ $u['email'] ?? '' }}?');">
                                        <input type="hidden" name="_token" value="{{ \App\Middleware\CsrfMiddleware::token() }}">
                                        <button type="submit" class="btn btn-sm btn-secondary"><i class="fas fa-key" style="margin-right:4px;"></i>Reset Password</button>
                                    </form>
                                    @endif
                                </div>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="empty-state">No users found. Invite your first team member above.</div>
    @endif
</div>

{{-- Pending Invitations --}}
@if(count($invitations) > 0)
<div class="card">
    <h3 class="section-title" style="margin-bottom:18px;">Pending Invitations</h3>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Sent</th>
                    <th>Expires</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($invitations as $inv)
                    <tr>
                        <td><div class="row-title">{{ $inv['email'] ?? '-' }}</div></td>
                        <td><span class="badge navy">{{ ucfirst($inv['role_key'] ?? 'user') }}</span></td>
                        <td>
                            @if(!empty($inv['accepted_at']))
                                <span class="badge teal">Accepted</span>
                            @elseif(isset($inv['expires_at']) && strtotime($inv['expires_at']) < time())
                                <span class="badge rose">Expired</span>
                            @else
                                <span class="badge amber">Pending</span>
                            @endif
                        </td>
                        <td>{{ isset($inv['created_at']) ? date('M j, Y', strtotime($inv['created_at'])) : '-' }}</td>
                        <td>{{ isset($inv['expires_at']) ? date('M j, Y', strtotime($inv['expires_at'])) : '-' }}</td>
                        <td>
                            @if(empty($inv['accepted_at']) && ($_SESSION['user']['role_key'] ?? '') === 'admin')
                                <form method="POST" action="/users/invite/{{ $inv['invitation_id'] }}/cancel" style="display:inline;" onsubmit="return confirm('Cancel this invitation?');">
                                    <input type="hidden" name="_token" value="{{ \App\Middleware\CsrfMiddleware::token() }}">
                                    <button type="submit" class="btn btn-danger btn-sm" style="font-size:12px;padding:4px 10px;"><i class="fas fa-times" style="margin-right:4px;"></i>Cancel</button>
                                </form>
                            @elseif(!empty($inv['accepted_at']))
                                —
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif
@endsection
