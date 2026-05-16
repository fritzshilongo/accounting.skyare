@extends('layouts.app')

@section('title', 'Clients')

@section('content')
<section class="hero-card">
    <div class="toolbar">
        <div>
            <h1 class="hero-title">Client Portfolio</h1>
            <p class="hero-copy">Track customer relationships, outstanding balances, and company records with a cleaner finance workflow.</p>
        </div>
        <div class="toolbar-right">
            <a href="/clients/export?{{ http_build_query(request()->query()) }}" class="btn btn-secondary">Export CSV</a>
            <a href="/clients/create" class="btn btn-primary">New Client</a>
        </div>
    </div>
</section>

<section class="table-card">
    <form method="GET" action="/clients" class="filter-bar" style="margin-bottom:18px;">
        <div>
            <label for="search">Search</label>
            <input id="search" name="search" value="{{ $search }}" placeholder="Name, email, phone">
        </div>
        <div>
            <label for="type">Client type</label>
            <select id="type" name="type">
                <option value="">All types</option>
                <option value="individual" {{ $type === 'individual' ? 'selected' : '' }}>Individual</option>
                <option value="company" {{ $type === 'company' ? 'selected' : '' }}>Company</option>
            </select>
        </div>
        <div>
            <label for="status">Status</label>
            <select id="status" name="status">
                <option value="">All statuses</option>
                <option value="active" {{ $status === 'active' ? 'selected' : '' }}>Active</option>
                <option value="inactive" {{ $status === 'inactive' ? 'selected' : '' }}>Inactive</option>
                <option value="suspended" {{ $status === 'suspended' ? 'selected' : '' }}>Suspended</option>
            </select>
        </div>
        <div style="display:flex; gap:10px; align-items:end;">
            <button type="submit" class="btn-primary">Apply</button>
            <a href="/clients" class="btn btn-ghost">Reset</a>
        </div>
    </form>

    @if($clients->count())
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Client</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th>Outstanding</th>
                        <th>Contact</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($clients as $client)
                        <tr>
                            <td>
                                <div class="row-title">{{ $client->name }}</div>
                                <div class="row-subtitle">{{ $client->registration_number ?: ($client->vat_number ?: 'No registration yet') }}</div>
                            </td>
                            <td><span class="badge {{ $client->type === 'company' ? 'navy' : 'teal' }}">{{ ucfirst($client->type) }}</span></td>
                            <td><span class="badge {{ $client->status === 'active' ? 'teal' : ($client->status === 'suspended' ? 'rose' : 'amber') }}">{{ ucfirst($client->status) }}</span></td>
                            <td>
                                <div class="row-title">${{ number_format($client->outstanding, 2) }}</div>
                                <div class="row-subtitle">Open receivables</div>
                            </td>
                            <td>
                                <div class="row-title">{{ $client->email ?: 'No email' }}</div>
                                <div class="row-subtitle">{{ $client->phone ?: 'No phone' }}</div>
                            </td>
                            <td>
                                <div class="inline-actions">
                                    <a href="/clients/{{ $client->client_id }}" class="btn btn-sm btn-ghost">View</a>
                                    <a href="/clients/{{ $client->client_id }}/edit" class="btn btn-sm btn-secondary">Edit</a>
                                    <form method="POST" action="/clients/{{ $client->client_id }}/toggle-status" style="display:inline;">
                                        <input type="hidden" name="_token" value="{{ \App\Middleware\CsrfMiddleware::token() }}">
                                        <button type="submit" class="btn btn-sm {{ $client->status === 'active' ? 'btn-danger' : 'btn-primary' }}" title="{{ $client->status === 'active' ? 'Deactivate' : 'Activate' }}">
                                            {{ $client->status === 'active' ? 'Deactivate' : 'Activate' }}
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="pagination-wrap">{{ $clients->links() }}</div>
    @else
        <div class="empty-state">No clients match your filters yet.</div>
    @endif
</section>
@endsection