@extends('layouts.app')

@section('title', 'Client Details')

@section('content')
<section class="hero-card">
    <div class="toolbar">
        <div>
            <h1 class="hero-title">{{ $client->name }}</h1>
            <p class="hero-copy">{{ $client->contact_person ?: 'Primary billing relationship' }} · {{ ucfirst($client->type) }}</p>
        </div>
        <div class="toolbar-right">
            <span class="badge {{ $client->status === 'active' ? 'teal' : ($client->status === 'suspended' ? 'rose' : 'amber') }}">{{ ucfirst($client->status) }}</span>
            <a href="/clients/{{ $client->client_id }}/edit" class="btn btn-primary">Edit</a>
            @if($client->status === 'active')
                <form method="POST" action="/clients/{{ $client->client_id }}" style="display:inline;" onsubmit="return confirm('Deactivate this client? They will no longer appear in active lists.')">
                    <input type="hidden" name="_token" value="{{ \App\Middleware\CsrfMiddleware::token() }}">
                    <input type="hidden" name="_method" value="DELETE">
                    <button type="submit" class="btn btn-ghost" style="color:var(--rose);border-color:var(--rose);">Deactivate</button>
                </form>
            @endif
        </div>
    </div>
</section>

<div class="panel-grid">
    <section class="card">
        <h2 class="section-title">Profile</h2>
        <p class="section-copy">Tax and contact snapshot.</p>
        <div class="form-grid two" style="margin-top:18px;">
            <div><label>Email</label><input value="{{ $client->email }}" disabled></div>
            <div><label>Phone</label><input value="{{ $client->phone }}" disabled></div>
            <div><label>VAT number</label><input value="{{ $client->vat_number }}" disabled></div>
            <div><label>Tax number</label><input value="{{ $client->tax_number }}" disabled></div>
            <div class="span-full"><label>Address</label><textarea disabled>{{ $client->address }}</textarea></div>
        </div>
    </section>

    <section class="card">
        <h2 class="section-title">Receivables</h2>
        <div class="metric-grid" style="margin-top:18px;">
            <div class="metric-card teal">
                <div class="metric-label">Open balance</div>
                <div class="metric-value">${{ number_format($client->outstanding, 2) }}</div>
                <div class="metric-meta">Across unpaid invoices</div>
            </div>
            <div class="metric-card amber">
                <div class="metric-label">Invoices</div>
                <div class="metric-value">{{ $client->invoices->count() }}</div>
                <div class="metric-meta">Billing records linked</div>
            </div>
        </div>
    </section>
</div>

<section class="table-card">
    <h2 class="section-title">Recent Invoices</h2>
    @if($client->invoices->count())
        <div class="table-wrap" style="margin-top:18px;">
            <table>
                <thead>
                    <tr>
                        <th>Invoice</th>
                        <th>Issue Date</th>
                        <th>Total</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($client->invoices as $invoice)
                        <tr>
                            <td><a href="/invoices/{{ $invoice->invoice_id }}" class="row-title">{{ $invoice->invoice_no }}</a></td>
                            <td>{{ $invoice->issue_date }}</td>
                            <td>${{ number_format($invoice->total ?: $invoice->amount, 2) }}</td>
                            <td><span class="badge navy">{{ ucfirst($invoice->status) }}</span></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="empty-state" style="margin-top:18px;">No invoices linked to this client yet.</div>
    @endif
</section>
@endsection