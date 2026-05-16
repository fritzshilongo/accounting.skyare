@extends('layouts.app')

@section('title', 'Credit Customers')

@section('content')
@php($currencySymbol = $_SESSION['user']['currency_symbol'] ?? 'N$')
<div class="hero-card">
    <h1 class="hero-title">Credit Customers</h1>
    <p class="hero-copy">Manage credit clients, view outstanding balances, and access their loan portfolios.</p>
</div>

<div class="panel-grid">
    <div class="metric-card navy">
        <div class="metric-label">Total Customers</div>
        <div class="metric-value">{{ $stats['total_customers'] }}</div>
    </div>
    <div class="metric-card teal">
        <div class="metric-label">Active Facilities</div>
        <div class="metric-value">{{ $stats['active_facilities'] }}</div>
    </div>
    <div class="metric-card amber">
        <div class="metric-label">Total Outstanding</div>
        <div class="metric-value">{{ $currencySymbol }}{{ number_format($stats['total_outstanding'], 2) }}</div>
    </div>
    <div class="metric-card">
        <div class="metric-label">Total Collected</div>
        <div class="metric-value">{{ $currencySymbol }}{{ number_format($stats['total_collected'], 2) }}</div>
    </div>
</div>

<div class="card" style="margin-top: 16px;">
    <div class="toolbar-row">
        <h3 class="section-title">Search &amp; Filter</h3>
        <a href="/credit-customers/create" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Add Customer</a>
    </div>
    <form method="GET" action="/credit-customers" class="inline-actions" style="gap: 10px; flex-wrap: wrap; margin-top: 12px;">
        <input type="text" name="search" value="{{ $search }}" placeholder="Name, email, phone..." class="form-control" style="max-width: 260px;">
        <select name="status" class="form-control" style="max-width: 140px;">
            <option value="">All Status</option>
            <option value="active" {{ $selectedStatus === 'active' ? 'selected' : '' }}>Active</option>
            <option value="inactive" {{ $selectedStatus === 'inactive' ? 'selected' : '' }}>Inactive</option>
        </select>
        <button type="submit" class="btn btn-secondary btn-sm">Filter</button>
        @if($search || $selectedStatus)
            <a href="/credit-customers" class="btn btn-ghost btn-sm">Clear</a>
        @endif
    </form>
</div>

<div class="card" style="margin-top: 16px;">
    <div class="toolbar-row">
        <h3 class="section-title">Customer Directory ({{ count($customers) }})</h3>
        <a href="/credit-management" class="btn btn-ghost btn-sm"><i class="fas fa-hand-holding-dollar"></i> Credit Management</a>
    </div>

    @if(count($customers) > 0)
        <div class="table-scroll" style="margin-top: 14px;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>City</th>
                        <th style="text-align: center;">Facilities</th>
                        <th style="text-align: right;">Outstanding</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($customers as $customer)
                        <tr>
                            <td><strong>{{ $customer['customer_name'] }}</strong></td>
                            <td>{{ $customer['email'] ?? '-' }}</td>
                            <td>{{ $customer['phone'] ?? '-' }}</td>
                            <td>{{ $customer['city'] ?? '-' }}</td>
                            <td style="text-align: center;">{{ $customer['credit_count'] ?? 0 }}</td>
                            <td style="text-align: right;">
                                @if((float) ($customer['total_outstanding'] ?? 0) > 0)
                                    <span style="color: var(--rose); font-weight: 600;">{{ $currencySymbol }}{{ number_format((float) $customer['total_outstanding'], 2) }}</span>
                                @else
                                    {{ $currencySymbol }}0.00
                                @endif
                            </td>
                            <td>
                                <span class="badge {{ ($customer['status'] ?? 'active') === 'active' ? 'teal' : 'rose' }}">
                                    {{ ucfirst($customer['status'] ?? 'active') }}
                                </span>
                            </td>
                            <td class="inline-actions">
                                <a href="/credit-customers/{{ $customer['customer_id'] }}" class="btn btn-secondary btn-sm">View</a>
                                <a href="/credit-customers/{{ $customer['customer_id'] }}/edit" class="btn btn-ghost btn-sm">Edit</a>
                                <form method="POST" action="/credit-customers/{{ $customer['customer_id'] }}/toggle-status" style="display:inline;">
                                    <input type="hidden" name="_token" value="{{ \App\Middleware\CsrfMiddleware::token() }}">
                                    <button type="submit" class="btn btn-sm {{ ($customer['status'] ?? 'active') === 'active' ? 'btn-danger' : 'btn-primary' }}" title="{{ ($customer['status'] ?? 'active') === 'active' ? 'Deactivate' : 'Activate' }}">
                                        {{ ($customer['status'] ?? 'active') === 'active' ? 'Deactivate' : 'Activate' }}
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="empty-state" style="margin-top: 16px;">
            <i class="fas fa-user-slash" style="font-size: 32px; color: var(--ink-muted); margin-bottom: 8px;"></i>
            <p>No credit customers found. <a href="/credit-customers/create">Add your first customer</a>.</p>
        </div>
    @endif
</div>
@endsection
