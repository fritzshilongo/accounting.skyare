@extends('layouts.app')

@section('title', 'Profile')

@section('content')
<section class="hero-card">
    <div class="toolbar">
        <div>
            <h1 class="hero-title">Your Profile</h1>
            <p class="hero-copy">Manage account details, password, personal preferences, and review recent account activity.</p>
        </div>
        <a href="/notifications" class="btn btn-ghost">View Notifications</a>
    </div>
</section>

<div class="panel-grid">
    <section class="form-card">
        <h2 class="section-title">Account Details</h2>
        <form method="POST" action="/profile" class="page-stack" style="margin-top:18px;">
            @csrf
            <div class="form-grid two">
                <div>
                    <label for="full_name">Full name</label>
                    <input id="full_name" name="full_name" value="{{ old('full_name', $user['full_name'] ?? $user['name'] ?? '') }}" required>
                </div>
                <div>
                    <label for="email">Email address</label>
                    <input id="email" type="email" name="email" value="{{ old('email', $user['email'] ?? '') }}" required>
                </div>
            </div>
            <div class="inline-actions">
                <button type="submit" class="btn btn-primary">Save Profile</button>
            </div>
        </form>
    </section>

    <section class="form-card">
        <h2 class="section-title">Preferences</h2>
        <form method="POST" action="/profile/preferences" class="page-stack" style="margin-top:18px;">
            @csrf
            <div class="form-grid two">
                <div>
                    <label for="phone">Phone</label>
                    <input id="phone" name="phone" value="{{ old('phone', $user['phone'] ?? '') }}">
                </div>
                <div>
                    <label for="currency_symbol">Currency symbol</label>
                    <input id="currency_symbol" name="currency_symbol" value="{{ old('currency_symbol', $user['currency_symbol'] ?? 'N$') }}" required>
                </div>
                <div>
                    <label for="timezone">Timezone</label>
                    <input id="timezone" name="timezone" value="{{ old('timezone', $user['timezone'] ?? 'Africa/Windhoek') }}" required>
                </div>
                <div>
                    <label for="date_format">Date format</label>
                    <select id="date_format" name="date_format" required>
                        @php
                            $selectedDateFormat = old('date_format', $user['date_format'] ?? 'Y-m-d');
                        @endphp
                        @foreach(['Y-m-d', 'd/m/Y', 'm/d/Y', 'd-m-Y', 'd M Y'] as $dateFormat)
                            <option value="{{ $dateFormat }}" {{ $selectedDateFormat === $dateFormat ? 'selected' : '' }}>{{ $dateFormat }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="inline-actions">
                <button type="submit" class="btn btn-primary">Save Preferences</button>
            </div>
        </form>
    </section>
</div>

<div class="panel-grid">
    <section class="form-card">
        <h2 class="section-title">Change Password</h2>
        <form method="POST" action="/profile/password" class="page-stack" style="margin-top:18px;">
            @csrf
            <div class="form-grid two">
                <div>
                    <label for="current_password">Current password</label>
                    <input id="current_password" type="password" name="current_password" required>
                </div>
                <div></div>
                <div>
                    <label for="new_password">New password</label>
                    <input id="new_password" type="password" name="new_password" required>
                </div>
                <div>
                    <label for="new_password_confirmation">Confirm new password</label>
                    <input id="new_password_confirmation" type="password" name="new_password_confirmation" required>
                </div>
            </div>
            <div class="inline-actions">
                <button type="submit" class="btn btn-primary">Update Password</button>
            </div>
        </form>
    </section>

    <section class="table-card">
        <div class="toolbar" style="margin-bottom:16px;">
            <h2 class="section-title">Recent Notifications</h2>
            <a href="/notifications" class="btn btn-sm btn-ghost">See All</a>
        </div>
        @if(!empty($notifications))
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Type</th>
                            <th>Created</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($notifications as $notification)
                            <tr>
                                <td>
                                    <div class="row-title">{{ $notification['title'] ?? 'Notification' }}</div>
                                    <div class="row-subtitle">{{ $notification['message'] ?? '' }}</div>
                                </td>
                                <td>{{ ucfirst(str_replace('_', ' ', $notification['type'] ?? 'general')) }}</td>
                                <td>{{ $notification['created_at'] ?? '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="empty-state">No notifications yet.</div>
        @endif
    </section>
</div>

<div class="panel-grid">
    <section class="table-card">
        <h2 class="section-title" style="margin-bottom:16px;">Recent Activity</h2>
        @if(!empty($activities))
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Action</th>
                            <th>Entity</th>
                            <th>Created</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($activities as $activity)
                            <tr>
                                <td>{{ ucfirst(str_replace('_', ' ', $activity['action'] ?? 'updated')) }}</td>
                                <td>{{ $activity['entity_type'] ?? '-' }}</td>
                                <td>{{ $activity['created_at'] ?? '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="empty-state">No activity recorded for this account yet.</div>
        @endif
    </section>

    <section class="table-card">
        <h2 class="section-title" style="margin-bottom:16px;">Login History</h2>
        @if(!empty($loginHistory))
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>IP</th>
                            <th>User Agent</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($loginHistory as $login)
                            <tr>
                                <td>{{ $login['created_at'] ?? '-' }}</td>
                                <td>{{ $login['ip_address'] ?? '-' }}</td>
                                <td>{{ $login['user_agent'] ?? '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="empty-state">No login history available.</div>
        @endif
    </section>
</div>
@endsection