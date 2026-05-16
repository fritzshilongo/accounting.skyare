@extends('layouts.app')

@section('title', 'License - ' . ($company['company_name'] ?? 'Skyare'))

@section('content')
<div class="hero-card">
    <div class="toolbar">
        <div>
            <h1 class="hero-title">License & Subscription</h1>
            <p class="hero-copy">View your current license status and plan details.</p>
        </div>
    </div>
</div>

@if($license)
@php
    $expiry = $license['expiry_date'] ?? null;
    $isExpired = $expiry && strtotime($expiry) < time();
    $daysLeft = $expiry ? (int) max(0, floor((strtotime($expiry) - time()) / 86400)) : null;
    $isTrial = str_starts_with($license['license_key'] ?? '', 'TRIAL-');
    $plan = $license['plan'] ?? 'professional';
    $status = $license['status'] ?? 'unknown';

    if ($isExpired) {
        $statusColor = 'rose';
        $statusLabel = 'Expired';
    } elseif ($isTrial) {
        $statusColor = 'amber';
        $statusLabel = 'Trial';
    } elseif ($status === 'active') {
        $statusColor = 'teal';
        $statusLabel = 'Active';
    } else {
        $statusColor = 'navy';
        $statusLabel = ucfirst($status);
    }
@endphp

{{-- License Status Card --}}
<div class="card" style="margin-bottom:24px;">
    <div style="display:flex;align-items:center;gap:18px;flex-wrap:wrap;">
        <div style="width:64px;height:64px;border-radius:16px;display:flex;align-items:center;justify-content:center;font-size:28px;
            background:{{ $isExpired ? 'rgba(223,111,95,0.12)' : ($isTrial ? 'rgba(215,154,30,0.12)' : 'rgba(18,128,122,0.12)') }};
            color:{{ $isExpired ? 'var(--rose)' : ($isTrial ? 'var(--amber)' : 'var(--teal)') }};">
            <i class="fas {{ $isExpired ? 'fa-triangle-exclamation' : ($isTrial ? 'fa-hourglass-half' : 'fa-shield-check') }}"></i>
        </div>
        <div style="flex:1;min-width:200px;">
            <div style="font-size:22px;font-weight:700;color:var(--ink);">
                <span class="badge {{ $statusColor }}" style="font-size:13px;vertical-align:middle;margin-right:8px;">{{ $statusLabel }}</span>
                {{ ucfirst($plan) }} Plan
            </div>
            <div style="color:var(--mute);margin-top:4px;">
                @if($isTrial)
                    Free trial — upgrade to continue after expiry
                @else
                    Licensed to {{ $company['company_name'] ?? 'your organization' }}
                @endif
            </div>
        </div>
        @if(!$isExpired && $daysLeft !== null)
        <div style="text-align:center;padding:12px 20px;border-radius:12px;background:{{ $daysLeft <= 7 ? 'rgba(223,111,95,0.08)' : ($daysLeft <= 30 ? 'rgba(215,154,30,0.08)' : 'rgba(18,128,122,0.08)') }};">
            <div style="font-size:32px;font-weight:800;color:{{ $daysLeft <= 7 ? 'var(--rose)' : ($daysLeft <= 30 ? 'var(--amber)' : 'var(--teal)') }};">{{ $daysLeft }}</div>
            <div style="font-size:12px;color:var(--mute);font-weight:600;">days left</div>
        </div>
        @endif
    </div>
</div>

{{-- License Details --}}
<div class="card" style="margin-bottom:24px;">
    <h3 class="section-title" style="margin-bottom:18px;"><i class="fas fa-info-circle" style="color:var(--teal);margin-right:8px;"></i>License Details</h3>
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:20px;">
        <div>
            <div style="font-size:12px;color:var(--mute);text-transform:uppercase;letter-spacing:1px;margin-bottom:4px;">License Key</div>
            <div style="font-family:monospace;font-size:14px;color:var(--ink);word-break:break-all;">{{ $license['license_key'] ?? '-' }}</div>
        </div>
        <div>
            <div style="font-size:12px;color:var(--mute);text-transform:uppercase;letter-spacing:1px;margin-bottom:4px;">Domain</div>
            <div style="font-size:14px;color:var(--ink);">{{ $license['domain'] ?? request()->getHost() }}</div>
        </div>
        <div>
            <div style="font-size:12px;color:var(--mute);text-transform:uppercase;letter-spacing:1px;margin-bottom:4px;">Plan</div>
            <div style="font-size:14px;color:var(--ink);">{{ ucfirst($plan) }}</div>
        </div>
        <div>
            <div style="font-size:12px;color:var(--mute);text-transform:uppercase;letter-spacing:1px;margin-bottom:4px;">Expiry Date</div>
            <div style="font-size:14px;color:{{ $isExpired ? 'var(--rose)' : 'var(--ink)' }};font-weight:{{ $isExpired ? '700' : '400' }};">
                {{ $expiry ? date('F j, Y', strtotime($expiry)) : 'No expiry set' }}
            </div>
        </div>
        <div>
            <div style="font-size:12px;color:var(--mute);text-transform:uppercase;letter-spacing:1px;margin-bottom:4px;">Last Verified</div>
            <div style="font-size:14px;color:var(--ink);">{{ !empty($license['last_verified_at']) ? date('M j, Y H:i', strtotime($license['last_verified_at'])) : 'Never' }}</div>
        </div>
        <div>
            <div style="font-size:12px;color:var(--mute);text-transform:uppercase;letter-spacing:1px;margin-bottom:4px;">Status</div>
            <div><span class="badge {{ $statusColor }}">{{ $statusLabel }}</span></div>
        </div>
    </div>
</div>

@if($isExpired || $isTrial)
{{-- Expiry / Trial Warning --}}
<div class="card" style="margin-bottom:24px;border-left:4px solid {{ $isExpired ? 'var(--rose)' : 'var(--amber)' }};">
    <div style="display:flex;align-items:center;gap:12px;">
        <i class="fas {{ $isExpired ? 'fa-circle-exclamation' : 'fa-clock' }}" style="font-size:24px;color:{{ $isExpired ? 'var(--rose)' : 'var(--amber)' }};"></i>
        <div>
            <div style="font-weight:700;color:var(--ink);margin-bottom:4px;">
                {{ $isExpired ? 'Your license has expired' : 'Your trial ends in ' . $daysLeft . ' days' }}
            </div>
            <div style="color:var(--mute);font-size:14px;">
                Contact us to {{ $isExpired ? 'renew' : 'upgrade' }} your license and continue using all features.
            </div>
        </div>
    </div>
</div>
@endif

@else
{{-- No License Found --}}
<div class="card" style="margin-bottom:24px;border-left:4px solid var(--rose);">
    <div style="display:flex;align-items:center;gap:12px;">
        <i class="fas fa-triangle-exclamation" style="font-size:24px;color:var(--rose);"></i>
        <div>
            <div style="font-weight:700;color:var(--ink);margin-bottom:4px;">No Active License Found</div>
            <div style="color:var(--mute);font-size:14px;">
                No license is associated with this workspace. Contact support to get licensed.
                @if(!empty($showIssuerUI))
                    <br><strong>This issuer workspace can still issue tenant licenses below.</strong>
                @endif
            </div>
        </div>
    </div>
</div>
@endif

@if(!empty($showIssuerUI))
<div class="card" style="margin-bottom:24px;">
    <h3 class="section-title" style="margin-bottom:18px;"><i class="fas fa-building" style="color:var(--teal);margin-right:8px;"></i>Register a Tenant</h3>
    <p style="color:var(--mute);margin-bottom:18px;">Create a new tenant company from this issuer tenant. The tenant will receive a 14-day trial and a welcome email with login details.</p>
    <form method="POST" action="/settings/license/create-tenant">
        @csrf
        <div class="form-grid" style="grid-template-columns:1fr 1fr;gap:18px;">
            <div class="form-group">
                <label for="company_name">Company name</label>
                <input id="company_name" name="company_name" type="text" value="{{ old('company_name') }}" required>
                @if(!empty($errors) && method_exists($errors, 'has') && $errors->has('company_name'))
                    <div class="field-error" style="color:var(--rose);font-size:13px;margin-top:6px;">{{ $errors->first('company_name') }}</div>
                @endif
            </div>
            <div class="form-group">
                <label for="subdomain">Tenant subdomain</label>
                <input id="subdomain" name="subdomain" type="text" value="{{ old('subdomain') }}" pattern="[a-z0-9\-]{3,32}" title="3-32 lowercase letters, numbers, or hyphens" required placeholder="tenant-name">
                <small style="color:var(--mute);">Tenant URL will be <strong>subdomain.{{ config('app.base_domain', 'skyare.space') }}</strong></small>
                @if(!empty($errors) && method_exists($errors, 'has') && $errors->has('subdomain'))
                    <div class="field-error" style="color:var(--rose);font-size:13px;margin-top:6px;">{{ $errors->first('subdomain') }}</div>
                @endif
            </div>
            <div class="form-group">
                <label for="admin_email">Admin email</label>
                <input id="admin_email" name="admin_email" type="email" value="{{ old('admin_email') }}" required>
                @if(!empty($errors) && method_exists($errors, 'has') && $errors->has('admin_email'))
                    <div class="field-error" style="color:var(--rose);font-size:13px;margin-top:6px;">{{ $errors->first('admin_email') }}</div>
                @endif
            </div>
            <div class="form-group">
                <label for="admin_password">Temporary password</label>
                <input id="admin_password" name="admin_password" type="text" value="{{ old('admin_password') }}" placeholder="Leave blank to generate automatically">
                <small style="color:var(--mute);">This password will be emailed to the tenant admin.</small>
                @if(!empty($errors) && method_exists($errors, 'has') && $errors->has('admin_password'))
                    <div class="field-error" style="color:var(--rose);font-size:13px;margin-top:6px;">{{ $errors->first('admin_password') }}</div>
                @endif
            </div>
        </div>
        <div style="margin-top:16px;">
            <button type="submit" class="btn btn-primary"><i class="fas fa-user-plus" style="margin-right:8px;"></i>Create Tenant</button>
        </div>
    </form>
</div>

<script>
    (function() {
        const companyNameInput = document.getElementById('company_name');
        const subdomainInput = document.getElementById('subdomain');

        if (!companyNameInput || !subdomainInput) {
            return;
        }

        companyNameInput.addEventListener('input', function() {
            const name = this.value.trim().toLowerCase();
            if (subdomainInput.value.trim() !== '') {
                return;
            }

            const slug = name
                .replace(/[^a-z0-9\-\s]/g, '')
                .replace(/\s+/g, '-')
                .replace(/\-+/g, '-')
                .replace(/^-+|-+$/g, '');

            if (slug.length >= 3) {
                subdomainInput.value = slug.substring(0, 32);
            }
        });
    })();
</script>

<div class="card" style="margin-bottom:24px;">
    <h3 class="section-title" style="margin-bottom:18px;"><i class="fas fa-plus-circle" style="color:var(--teal);margin-right:8px;"></i>Issue a License</h3>
    <p style="color:var(--mute);margin-bottom:18px;">Select a registered company and issue a license from this tenant's admin UI.</p>
    <form method="POST" action="/settings/license/issue">
        @csrf
        <div class="form-group">
            <label for="company_id">Company</label>
            <select id="company_id" name="company_id" required>
                <option value="">Select company</option>
                @foreach($issuerCompanies as $issuerCompany)
                    @if((int) $issuerCompany['company_id'] === (int) ($company['company_id'] ?? 0) || $issuerCompany['status'] === 'deleted')
                        @continue
                    @endif
                    <option value="{{ $issuerCompany['company_id'] }}">{{ $issuerCompany['company_name'] }} ({{ $issuerCompany['subdomain'] }})</option>
                @endforeach
            </select>
        </div>
        <div class="form-group">
            <label>License duration</label>
            <div style="display:flex;gap:12px;flex-wrap:wrap;">
                @foreach($plans as $planKey => $planMeta)
                    <label class="radio-inline" style="border:1px solid var(--line);border-radius:12px;padding:16px;flex:1;min-width:130px;">
                        <input type="radio" name="plan" value="{{ $planKey }}" {{ $loop->first ? 'checked' : '' }}>
                        <strong>{{ $planMeta['label'] }}</strong>
                        <div style="font-size:13px;color:var(--mute);margin-top:4px;">{{ $planMeta['price'] }}</div>
                    </label>
                @endforeach
            </div>
        </div>
        <div style="margin-top:16px;">
            <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane" style="margin-right:8px;"></i>Issue License</button>
        </div>
    </form>
</div>

<div class="card" style="margin-bottom:24px;">
    <h3 class="section-title" style="margin-bottom:18px;"><i class="fas fa-gear" style="color:var(--teal);margin-right:8px;"></i>Tenant Management</h3>
    <p style="color:var(--mute);margin-bottom:18px;">Enable or disable tenant companies, review active licenses, monitor recent activity, and access tenant login or password recovery links.</p>

    <div class="status-filter" style="display:flex;flex-wrap:wrap;gap:10px;margin-bottom:20px;font-size:0.95rem;">
        @foreach(['all' => 'All', 'active' => 'Active', 'inactive' => 'Inactive', 'deleted' => 'Deleted'] as $key => $label)
            <a href="?status={{ $key }}" class="status-filter-pill{{ ($statusFilter ?? 'all') === $key ? ' is-active' : '' }}">{{ $label }}</a>
        @endforeach
    </div>

    @if(count($issuerCompanies) > 0)
    <div style="overflow-x:auto;">
        <table class="table" style="width:100%;border-collapse:collapse;">
            <thead>
                <tr>
                    <th style="text-align:left;padding:12px;border-bottom:1px solid var(--line);">Tenant</th>
                    <th style="text-align:left;padding:12px;border-bottom:1px solid var(--line);">Contact Email</th>
                    <th style="text-align:left;padding:12px;border-bottom:1px solid var(--line);">Subdomain</th>
                    <th style="text-align:left;padding:12px;border-bottom:1px solid var(--line);">Status</th>
                    <th style="text-align:left;padding:12px;border-bottom:1px solid var(--line);">License Expiry</th>
                    <th style="text-align:left;padding:12px;border-bottom:1px solid var(--line);">Tenant Summary</th>
                    <th style="text-align:left;padding:12px;border-bottom:1px solid var(--line);">Activity (30d)</th>
                    <th style="text-align:left;padding:12px;border-bottom:1px solid var(--line);">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($issuerCompanies as $issuerCompany)
                <tr>
                    <td style="padding:12px;border-bottom:1px solid var(--line);">{{ $issuerCompany['company_name'] }}</td>
                    <td style="padding:12px;border-bottom:1px solid var(--line);vertical-align:top;">
                        <form method="POST" action="/settings/license/update-email" style="display:grid; gap:6px;">
                            @csrf
                            <input type="hidden" name="company_id" value="{{ $issuerCompany['company_id'] }}">
                            <label style="font-size:12px;color:var(--mute);">Tenant contact/login email</label>
                            <input type="email" name="email" value="{{ $issuerCompany['tenant_contact_email'] }}" placeholder="tenant@example.com" style="width:100%;padding:8px;border:1px solid var(--line);border-radius:6px;" />
                            <button type="submit" class="btn btn-sm btn-primary">Update Login Email</button>
                        </form>
                    </td>
                    <td style="padding:12px;border-bottom:1px solid var(--line);">{{ $issuerCompany['subdomain'] }}.{{ config('app.base_domain', 'skyare.space') }}</td>
                    <td style="padding:12px;border-bottom:1px solid var(--line);">
                        <span class="badge {{ $issuerCompany['status'] === 'active' ? 'teal' : ($issuerCompany['status'] === 'deleted' ? 'deleted' : 'inactive') }}">{{ ucfirst($issuerCompany['status']) }}</span>
                    </td>
                    <td style="padding:12px;border-bottom:1px solid var(--line);">
                        {{ $issuerCompany['license_expiry'] ? date('M j, Y', strtotime($issuerCompany['license_expiry'])) : 'No active license' }}
                    </td>
                    <td style="padding:12px;border-bottom:1px solid var(--line);">
                        <div style="font-size:13px;line-height:1.5;max-width:260px;">
                            <div><strong>Users:</strong> {{ $issuerCompany['tenant_user_count'] }}</div>
                            <div><strong>Licenses:</strong> {{ $issuerCompany['tenant_license_count'] }}</div>
                            <div><strong>Active plan:</strong> {{ $issuerCompany['tenant_active_license_plan'] ?? '—' }}</div>
                            <div><strong>Last issue:</strong> {{ $issuerCompany['tenant_last_license_date'] ?? '—' }}</div>
                            @if(!empty($issuerCompany['tenant_last_license_key']))
                                <div><strong>Latest key:</strong> {{ \Illuminate\Support\Str::limit($issuerCompany['tenant_last_license_key'], 16, '...') }}</div>
                            @endif
                            <div><strong>Last login:</strong> {{ $issuerCompany['tenant_last_login_at'] ?? '—' }}</div>
                        </div>
                    </td>
                    <td style="padding:12px;border-bottom:1px solid var(--line);">{{ $issuerCompany['activity_count'] }}</td>
                    <td style="padding:12px;border-bottom:1px solid var(--line);">
                        <div style="display:flex;flex-direction:column;gap:8px;">
                            @if($issuerCompany['status'] !== 'deleted')
                                <a href="{{ $issuerCompany['tenant_login_url'] }}" target="_blank" class="btn btn-sm btn-secondary">Tenant Login</a>
                                <a href="{{ $issuerCompany['tenant_forgot_password_url'] }}" target="_blank" class="btn btn-sm btn-secondary">Password Reset</a>
                            @else
                                <div style="font-size:13px;color:var(--mute);">Tenant deleted, login disabled.</div>
                            @endif
                            @if($issuerCompany['tenant_contact_email'] && $issuerCompany['status'] !== 'deleted')
                                <form method="POST" action="/settings/license/send-reset" style="display:inline; margin-top:8px;">
                                    @csrf
                                    <input type="hidden" name="company_id" value="{{ $issuerCompany['company_id'] }}">
                                    <button type="submit" class="btn btn-sm btn-secondary">Email Reset Link</button>
                                </form>
                            @endif
                            <a href="/settings/license/edit/{{ $issuerCompany['company_id'] }}" class="btn btn-sm btn-secondary" style="margin-top:8px;">Edit Tenant</a>
                            <a href="/settings/license/history/{{ $issuerCompany['company_id'] }}" class="btn btn-sm btn-secondary" style="margin-top:8px;">License History</a>
                            <a href="/audit?company_id={{ $issuerCompany['company_id'] }}" class="btn btn-sm btn-secondary" style="margin-top:8px;">Audit Trail</a>
                            @if($issuerCompany['status'] !== 'deleted')
                                @if($issuerCompany['status'] === 'active')
                                    <form method="POST" action="/settings/license/company-status" style="display:inline; margin-top:8px;">
                                        @csrf
                                        <input type="hidden" name="company_id" value="{{ $issuerCompany['company_id'] }}">
                                        <input type="hidden" name="action" value="disable">
                                        <button type="submit" class="btn btn-sm btn-secondary">Disable</button>
                                    </form>
                                @else
                                    <form method="POST" action="/settings/license/company-status" style="display:inline; margin-top:8px;">
                                        @csrf
                                        <input type="hidden" name="company_id" value="{{ $issuerCompany['company_id'] }}">
                                        <input type="hidden" name="action" value="enable">
                                        <button type="submit" class="btn btn-sm btn-primary">Enable</button>
                                    </form>
                                @endif
                                <form method="POST" action="/settings/license/delete-tenant" style="display:inline; margin-top:8px;">
                                    @csrf
                                    <input type="hidden" name="company_id" value="{{ $issuerCompany['company_id'] }}">
                                    <button type="submit" class="btn btn-sm btn-ghost" style="color:#b91c1c;border:1px solid rgba(185,28,28,0.16);" data-company-name="{{ e($issuerCompany['company_name']) }}" onclick="return confirm('Delete tenant ' + this.dataset.companyName + '? This will soft-delete their account and disable tenant access.')">Delete Tenant</button>
                                </form>
                            @else
                                <form method="POST" action="/settings/license/company-status" style="display:inline; margin-top:8px;">
                                    @csrf
                                    <input type="hidden" name="company_id" value="{{ $issuerCompany['company_id'] }}">
                                    <input type="hidden" name="action" value="enable">
                                    <button type="submit" class="btn btn-sm btn-primary">Restore Tenant</button>
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @else
        <div style="color:var(--mute);">No tenant companies found.</div>
    @endif
</div>
@endif

{{-- Pricing Table --}}
<div class="card">
    <h3 class="section-title" style="margin-bottom:18px;"><i class="fas fa-tags" style="color:var(--teal);margin-right:8px;"></i>Available Plans</h3>
    <p style="color:var(--mute);margin-bottom:18px;">All plans include full access to every module. Choose the billing period that works for you.</p>
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px;">
        @foreach($plans as $key => $p)
        <div style="border:2px solid {{ ($license && !($license['status'] ?? '') !== 'active') ? 'var(--line)' : 'var(--line)' }};border-radius:12px;padding:24px 20px;text-align:center;position:relative;
            {{ $key === 'yearly' ? 'border-color:var(--teal);background:rgba(18,128,122,0.04);' : '' }}">
            @if($key === 'yearly')
            <div style="position:absolute;top:-12px;left:50%;transform:translateX(-50%);background:var(--teal);color:#fff;padding:3px 14px;border-radius:99px;font-size:11px;font-weight:700;">BEST VALUE</div>
            @endif
            <div style="font-weight:700;font-size:16px;color:var(--ink);margin-bottom:6px;">{{ $p['label'] }}</div>
            <div style="font-size:28px;font-weight:800;color:var(--teal);">{{ $p['price'] }}</div>
            <div style="color:var(--mute);font-size:13px;margin-top:4px;">N${{ number_format(((int) str_replace(['N$', ','], '', $p['price'])) / $p['months'], 0) }}/month</div>
            <div style="margin-top:12px;font-size:13px;color:var(--mute);">
                <i class="fas fa-check" style="color:var(--teal);margin-right:4px;"></i>All modules included<br>
                <i class="fas fa-check" style="color:var(--teal);margin-right:4px;"></i>Unlimited users<br>
                <i class="fas fa-check" style="color:var(--teal);margin-right:4px;"></i>Email support
            </div>
        </div>
        @endforeach
    </div>
    <div style="text-align:center;margin-top:24px;">
        <p style="color:var(--mute);font-size:14px;margin-bottom:12px;">To subscribe or renew, contact us:</p>
        <a href="mailto:support@skyare.space" class="btn btn-primary" style="margin-right:8px;">
            <i class="fas fa-envelope" style="margin-right:6px;"></i>support@skyare.space
        </a>
        <a href="tel:+264812016012" class="btn btn-secondary">
            <i class="fas fa-phone" style="margin-right:6px;"></i>+264 81 201 6012
        </a>
    </div>
</div>
@endsection
