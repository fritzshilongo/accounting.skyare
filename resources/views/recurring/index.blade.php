@extends('layouts.app')

@section('title', 'Recurring Invoices - ' . ($company['company_name'] ?? 'Skyare'))

@section('content')
<div class="hero-card">
    <div class="toolbar">
        <div>
            <h1 class="hero-title">Recurring Invoices</h1>
            <p class="hero-copy">Automate billing with scheduled recurring invoices for your clients.</p>
        </div>
        <a href="/recurring-invoices/create" class="btn btn-primary"><i class="fas fa-plus" style="margin-right:6px;"></i>New Recurring Invoice</a>
    </div>
</div>

@if(count($recurring ?? []) > 0)
    <div class="stats-grid">
        @php
            $activeCount = count(array_filter($recurring, fn($r) => ($r['status'] ?? '') === 'active'));
            $totalMonthly = array_sum(array_map(fn($r) => ($r['status'] ?? '') === 'active' && ($r['frequency'] ?? '') === 'monthly' ? (float)($r['total'] ?? 0) : 0, $recurring));
        @endphp
        <div class="metric-card teal">
            <div class="metric-label">Active Schedules</div>
            <div class="metric-value">{{ $activeCount }}</div>
            <div class="metric-meta">Currently running</div>
        </div>
        <div class="metric-card amber">
            <div class="metric-label">Monthly Revenue (est.)</div>
            <div class="metric-value">${{ number_format($totalMonthly, 2) }}</div>
            <div class="metric-meta">From monthly recurring</div>
        </div>
        <div class="metric-card navy">
            <div class="metric-label">Total Templates</div>
            <div class="metric-value">{{ count($recurring) }}</div>
            <div class="metric-meta">All recurring invoices</div>
        </div>
    </div>

    <div class="card">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Client</th>
                        <th>Frequency</th>
                        <th>Amount</th>
                        <th>Next Run</th>
                        <th>Status</th>
                        <th>Generated</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($recurring as $r)
                        <tr>
                            <td>
                                <div class="row-title">{{ $r['client_name'] ?? 'Unknown' }}</div>
                                @if(!empty($r['description']))
                                    <div class="row-subtitle">{{ \Illuminate\Support\Str::limit($r['description'], 40) }}</div>
                                @endif
                            </td>
                            <td><span class="badge navy">{{ ucfirst($r['frequency'] ?? '-') }}</span></td>
                            <td style="font-weight:700;">${{ number_format($r['total'] ?? 0, 2) }}</td>
                            <td>{{ isset($r['next_run_date']) ? date('M j, Y', strtotime($r['next_run_date'])) : '-' }}</td>
                            <td>
                                @if(($r['status'] ?? '') === 'active')
                                    <span class="badge teal">Active</span>
                                @elseif(($r['status'] ?? '') === 'paused')
                                    <span class="badge amber">Paused</span>
                                @else
                                    <span class="badge rose">{{ ucfirst($r['status'] ?? 'Unknown') }}</span>
                                @endif
                            </td>
                            <td>{{ $r['occurrences_generated'] ?? 0 }}{{ !empty($r['max_occurrences']) ? ' / ' . $r['max_occurrences'] : '' }}</td>
                            <td>
                                <div class="inline-actions">
                                    <a href="/recurring-invoices/{{ $r['recurring_id'] }}" class="btn btn-ghost btn-sm">View</a>
                                    <form method="POST" action="/recurring-invoices/{{ $r['recurring_id'] }}/toggle" style="display:inline;">
                                        <input type="hidden" name="_token" value="{{ \App\Middleware\CsrfMiddleware::token() }}">
                                        <button type="submit" class="btn btn-sm {{ ($r['status'] ?? '') === 'active' ? 'btn-secondary' : 'btn-primary' }}">
                                            {{ ($r['status'] ?? '') === 'active' ? 'Pause' : 'Resume' }}
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@else
    <div class="empty-state">
        <i class="fas fa-rotate" style="font-size:32px;color:var(--muted);margin-bottom:12px;display:block;"></i>
        No recurring invoices yet. <a href="/recurring-invoices/create" style="color:var(--teal);font-weight:700;">Create your first one</a>.
    </div>
@endif
@endsection
