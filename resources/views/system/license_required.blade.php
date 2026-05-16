@extends('layouts.app')

@section('title', 'License Status')

@section('content')
<section class="hero-card">
    <div class="toolbar">
        <div>
            <h1 class="hero-title">License Status</h1>
            <p class="hero-copy">Verify the subscription assigned to this workspace and resolve any licensing issues before continuing.</p>
        </div>
        <a href="/settings/license" class="btn btn-primary">Open Licensing</a>
    </div>
</section>

<div class="panel-grid">
    <section class="card">
        <h2 class="section-title" style="margin-bottom:16px;">Current Status</h2>
        @php
            $normalizedMessage = strtolower((string) ($message ?? 'license verification failed.'));
            $statusClass = str_contains($normalizedMessage, 'active') ? 'teal' : (str_contains($normalizedMessage, 'expired') || str_contains($normalizedMessage, 'failed') || str_contains($normalizedMessage, 'no active license') ? 'rose' : 'amber');
        @endphp
        <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
            <span class="badge {{ $statusClass }}">{{ ucfirst($message ?? 'Unknown status') }}</span>
            <span class="muted">Host: {{ $host ?? request()->getHost() }}</span>
        </div>
        @if(!empty($company))
            <div style="margin-top:18px;display:grid;gap:10px;">
                <div><strong>Company:</strong> {{ $company['company_name'] ?? 'Unknown company' }}</div>
                @if(!empty($company['subdomain']))
                    <div><strong>Workspace:</strong> {{ $company['subdomain'] }}</div>
                @endif
                @if(!empty($grace_until))
                    <div><strong>Grace period until:</strong> {{ $grace_until }}</div>
                @endif
            </div>
        @endif
    </section>

    <section class="card">
        <h2 class="section-title" style="margin-bottom:16px;">What To Do Next</h2>
        <div class="page-stack">
            <div>Check the workspace domain and active license assignment in the licensing panel.</div>
            <div>Renew or reissue the license if the current record has expired.</div>
            <div>If this is a new deployment, confirm the domain matches the licensed hostname exactly.</div>
            <div>Contact support if the license is valid but this page still appears.</div>
        </div>
        <div class="inline-actions" style="margin-top:18px;">
            <a href="/settings/license" class="btn btn-primary">Manage License</a>
            <a href="/dashboard" class="btn btn-ghost">Back to Dashboard</a>
        </div>
    </section>
</div>
@endsection