@extends('layouts.app')

@section('title', 'Audit Trail')

@section('content')
<section class="hero-card">
    <h1 class="hero-title">Audit Timeline</h1>
    <p class="hero-copy">Every interaction and data change is captured here for traceability and control.</p>
    @if(!empty($company['company_name']))
        <p class="hero-copy" style="margin-top:8px;">Viewing audit records for <strong>{{ $company['company_name'] }}</strong>.</p>
    @endif
</section>

<section class="table-card">
    <form method="GET" action="/audit" class="filter-bar" style="margin-bottom:18px;">
        @if(request()->query('company_id'))
            <input type="hidden" name="company_id" value="{{ request()->query('company_id') }}">
        @endif
        <div>
            <label for="search">Search details</label>
            <input id="search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Route, entity, payload key">
        </div>
        <div>
            <label for="method">Method</label>
            <select id="method" name="method">
                <option value="">All</option>
                <option value="GET" {{ ($filters['method'] ?? '') === 'GET' ? 'selected' : '' }}>GET</option>
                <option value="POST" {{ ($filters['method'] ?? '') === 'POST' ? 'selected' : '' }}>POST</option>
                <option value="PUT" {{ ($filters['method'] ?? '') === 'PUT' ? 'selected' : '' }}>PUT</option>
                <option value="PATCH" {{ ($filters['method'] ?? '') === 'PATCH' ? 'selected' : '' }}>PATCH</option>
                <option value="DELETE" {{ ($filters['method'] ?? '') === 'DELETE' ? 'selected' : '' }}>DELETE</option>
            </select>
        </div>
        <div>
            <label for="from">From</label>
            <input id="from" type="date" name="from" value="{{ $filters['from'] ?? '' }}">
        </div>
        <div>
            <label for="to">To</label>
            <input id="to" type="date" name="to" value="{{ $filters['to'] ?? '' }}">
        </div>
        <div style="display:flex; gap:10px; align-items:end;">
            <button type="submit" class="btn-primary">Filter</button>
            <a href="/audit{{ request()->query('company_id') ? '?company_id=' . request()->query('company_id') : '' }}" class="btn btn-ghost">Reset</a>
        </div>
    </form>

    @if(!empty($logs))
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>When</th>
                        <th>User</th>
                        <th>Action</th>
                        <th>Entity</th>
                        <th>Details</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($logs as $log)
                        @php
                            $details = null;
                            if (!empty($log['details'])) {
                                $decoded = json_decode($log['details'], true);
                                if (is_array($decoded)) {
                                    $details = $decoded;
                                }
                            }
                        @endphp
                        <tr>
                            <td>
                                <div class="row-title">{{ $log['created_at'] ?? '-' }}</div>
                                <div class="row-subtitle">Audit #{{ $log['audit_id'] ?? '-' }}</div>
                            </td>
                            <td>{{ $log['user_id'] ?? 'system' }}</td>
                            <td><span class="badge navy">{{ $log['action_key'] ?? 'unknown' }}</span></td>
                            <td>
                                <div class="row-title">{{ $log['entity_type'] ?? '-' }}</div>
                                <div class="row-subtitle">{{ $log['entity_id'] ?? '-' }}</div>
                            </td>
                            <td>
                                @if($details)
                                    <div class="row-subtitle">Method: {{ $details['method'] ?? '-' }} · Status: {{ $details['status_code'] ?? '-' }}</div>
                                    <div class="row-subtitle">Path: {{ $details['path'] ?? '-' }}</div>
                                    <div class="row-subtitle">IP: {{ $details['ip'] ?? '-' }}</div>
                                    @if(!empty($details['input_keys']) && is_array($details['input_keys']))
                                        <div class="row-subtitle">Payload keys: {{ implode(', ', $details['input_keys']) }}</div>
                                    @endif
                                @else
                                    <div class="row-subtitle">{{ \Illuminate\Support\Str::limit($log['details'] ?? '-', 120) }}</div>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="empty-state">No audit records match these filters.</div>
    @endif
</section>
@endsection
