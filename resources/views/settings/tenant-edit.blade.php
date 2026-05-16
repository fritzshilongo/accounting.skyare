@extends('layouts.app')

@section('title', 'Edit Tenant - ' . ($company['company_name'] ?? 'Tenant'))

@section('content')
<div class="card" style="max-width:720px;margin:32px auto;">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px;">
        <div>
            <h2 class="section-title">Edit Tenant</h2>
            <p class="section-copy" style="margin-top:6px;color:var(--mute);">Update the tenant company name, admin email, and password. Changes apply to the tenant login and issuer tenant management.</p>
        </div>
        <a href="/settings/license" class="btn btn-secondary">Back to Tenant Management</a>
    </div>

    <form method="POST" action="/settings/license/update-tenant">
        @csrf
        <input type="hidden" name="company_id" value="{{ $company['company_id'] }}">

        <div class="form-group">
            <label for="company_name">Company name</label>
            <input id="company_name" name="company_name" type="text" value="{{ old('company_name', $company['company_name']) }}" required>
            @error('company_name')
                <div class="field-error" style="color:var(--rose);font-size:13px;margin-top:6px;">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-group">
            <label for="admin_email">Admin email</label>
            <input id="admin_email" name="admin_email" type="email" value="{{ old('admin_email', $company['email'] ?? '') }}" required>
            @error('admin_email')
                <div class="field-error" style="color:var(--rose);font-size:13px;margin-top:6px;">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-group">
            <label for="admin_password">Set new password</label>
            <input id="admin_password" name="admin_password" type="text" value="{{ old('admin_password') }}" placeholder="Leave blank to keep current password">
            <small style="color:var(--mute);">If provided, the tenant admin password will be updated immediately.</small>
            @error('admin_password')
                <div class="field-error" style="color:var(--rose);font-size:13px;margin-top:6px;">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-group">
            <label>Tenant subdomain</label>
            <div style="padding:12px 14px;border:1px solid var(--line);border-radius:10px;background:var(--surface);">{{ $company['subdomain'] }}.{{ config('app.base_domain', 'skyare.space') }}</div>
            <small style="color:var(--mute);">Subdomain changes are not supported here.</small>
        </div>

        <div style="margin-top:16px;display:flex;gap:12px;flex-wrap:wrap;align-items:center;">
            <button type="submit" class="btn btn-primary">Save Tenant Details</button>
            <a href="/settings/license" class="btn btn-ghost">Cancel</a>
        </div>
    </form>
</div>
@endsection
