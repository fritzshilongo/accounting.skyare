@extends('layouts.app')

@section('title', 'License History - ' . ($company['company_name'] ?? 'Tenant'))

@section('content')
<div class="hero-card">
    <div class="toolbar">
        <div>
            <h1 class="hero-title">License History for {{ $company['company_name'] ?? 'Tenant' }}</h1>
            <p class="hero-copy">Review issued licenses, active status, expiry dates, and plans for this tenant.</p>
        </div>
        <a href="/settings/license" class="btn btn-secondary">Back to Licensing</a>
    </div>
</div>

<div class="card" style="margin-bottom:24px;">
    <table class="table" style="width:100%;border-collapse:collapse;">
        <thead>
            <tr>
                <th style="padding:12px;border-bottom:1px solid var(--line);">Issued</th>
                <th style="padding:12px;border-bottom:1px solid var(--line);">License Key</th>
                <th style="padding:12px;border-bottom:1px solid var(--line);">Status</th>
                <th style="padding:12px;border-bottom:1px solid var(--line);">Plan</th>
                <th style="padding:12px;border-bottom:1px solid var(--line);">Domain</th>
                <th style="padding:12px;border-bottom:1px solid var(--line);">Expiry</th>
                <th style="padding:12px;border-bottom:1px solid var(--line);">Verified</th>
            </tr>
        </thead>
        <tbody>
            @forelse($licenses as $license)
                <tr>
                    <td style="padding:12px;border-bottom:1px solid var(--line);">{{ !empty($license['created_at']) ? date('M j, Y', strtotime($license['created_at'])) : 'N/A' }}</td>
                    <td style="padding:12px;border-bottom:1px solid var(--line);font-family:monospace;">{{ \Illuminate\Support\Str::limit($license['license_key'] ?? '-', 20, '...') }}</td>
                    <td style="padding:12px;border-bottom:1px solid var(--line);">{{ ucfirst($license['status'] ?? 'unknown') }}</td>
                    <td style="padding:12px;border-bottom:1px solid var(--line);">{{ ucfirst($license['plan'] ?? '-') }}</td>
                    <td style="padding:12px;border-bottom:1px solid var(--line);">{{ $license['domain'] ?? '-' }}</td>
                    <td style="padding:12px;border-bottom:1px solid var(--line);">{{ !empty($license['expiry_date']) ? date('M j, Y', strtotime($license['expiry_date'])) : 'N/A' }}</td>
                    <td style="padding:12px;border-bottom:1px solid var(--line);">{{ !empty($license['last_verified_at']) ? date('M j, Y', strtotime($license['last_verified_at'])) : 'Never' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" style="padding:18px;text-align:center;color:var(--mute);">No license history found for this tenant.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
