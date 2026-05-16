@extends('layouts.app')

@section('title', 'Recurring Invoice #' . ($recurring['recurring_id'] ?? '') . ' - ' . ($company['company_name'] ?? 'Skyare'))

@section('content')
<div class="hero-card">
    <div class="toolbar">
        <div>
            <h1 class="hero-title">Recurring Invoice #{{ $recurring['recurring_id'] ?? '' }}</h1>
            <p class="hero-copy">{{ $recurring['client_name'] ?? 'Unknown Client' }} · {{ ucfirst($recurring['frequency'] ?? 'monthly') }} billing</p>
        </div>
        <div class="inline-actions">
            <form method="POST" action="/recurring-invoices/{{ $recurring['recurring_id'] }}/toggle" style="display:inline;">
                <input type="hidden" name="_token" value="{{ \App\Middleware\CsrfMiddleware::token() }}">
                <button type="submit" class="btn {{ ($recurring['status'] ?? '') === 'active' ? 'btn-secondary' : 'btn-primary' }}">
                    <i class="fas {{ ($recurring['status'] ?? '') === 'active' ? 'fa-pause' : 'fa-play' }}" style="margin-right:6px;"></i>
                    {{ ($recurring['status'] ?? '') === 'active' ? 'Pause' : 'Resume' }}
                </button>
            </form>
            <a href="/recurring-invoices" class="btn btn-secondary"><i class="fas fa-arrow-left" style="margin-right:6px;"></i>Back</a>
        </div>
    </div>
</div>

<div class="stats-grid">
    <div class="metric-card teal">
        <div class="metric-label">Status</div>
        <div class="metric-value" style="font-size:22px;">{{ ucfirst($recurring['status'] ?? 'Unknown') }}</div>
    </div>
    <div class="metric-card amber">
        <div class="metric-label">Total per Invoice</div>
        <div class="metric-value">${{ number_format($recurring['total'] ?? 0, 2) }}</div>
        <div class="metric-meta">
            ${{ number_format($recurring['amount'] ?? 0, 2) }} + Tax ${{ number_format($recurring['tax_amount'] ?? 0, 2) }}
        </div>
    </div>
    <div class="metric-card navy">
        <div class="metric-label">Invoices Generated</div>
        <div class="metric-value">{{ $recurring['occurrences_generated'] ?? 0 }}{{ !empty($recurring['max_occurrences']) ? ' / ' . $recurring['max_occurrences'] : '' }}</div>
    </div>
    <div class="metric-card rose">
        <div class="metric-label">Next Run</div>
        <div class="metric-value" style="font-size:18px;">{{ isset($recurring['next_run_date']) ? date('M j, Y', strtotime($recurring['next_run_date'])) : 'N/A' }}</div>
    </div>
</div>

<div class="card">
    <h3 class="section-title" style="margin-bottom:18px;">Schedule Details</h3>
    <div class="form-grid two">
        <div><label>Frequency</label><div style="font-weight:700;">{{ ucfirst($recurring['frequency'] ?? '-') }}</div></div>
        <div><label>Start Date</label><div>{{ isset($recurring['start_date']) ? date('M j, Y', strtotime($recurring['start_date'])) : '-' }}</div></div>
        <div><label>End Date</label><div>{{ isset($recurring['end_date']) && $recurring['end_date'] ? date('M j, Y', strtotime($recurring['end_date'])) : 'No end date' }}</div></div>
        <div><label>Tax Rate</label><div>{{ $recurring['tax_rate'] ?? 0 }}%</div></div>
        @if(!empty($recurring['description']))
            <div class="span-full"><label>Description</label><div>{{ $recurring['description'] }}</div></div>
        @endif
    </div>
</div>

<div class="card">
    <h3 class="section-title" style="margin-bottom:18px;">Line Items</h3>
    @if(count($items ?? []) > 0)
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Description</th>
                        <th>Qty</th>
                        <th>Unit Price</th>
                        <th>Line Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($items as $item)
                        <tr>
                            <td><div class="row-title">{{ $item['description'] ?? '-' }}</div></td>
                            <td>{{ $item['quantity'] ?? 0 }}</td>
                            <td>${{ number_format($item['unit_price'] ?? 0, 2) }}</td>
                            <td style="font-weight:700;">${{ number_format($item['line_total'] ?? 0, 2) }}</td>
                        </tr>
                    @endforeach
                    <tr style="background:var(--teal-soft);">
                        <td colspan="3" style="text-align:right;font-weight:700;">Subtotal</td>
                        <td style="font-weight:700;">${{ number_format($recurring['amount'] ?? 0, 2) }}</td>
                    </tr>
                    @if(($recurring['tax_amount'] ?? 0) > 0)
                        <tr>
                            <td colspan="3" style="text-align:right;font-weight:700;">Tax ({{ $recurring['tax_rate'] ?? 0 }}%)</td>
                            <td style="font-weight:700;">${{ number_format($recurring['tax_amount'] ?? 0, 2) }}</td>
                        </tr>
                    @endif
                    <tr style="background:linear-gradient(135deg,#e3f4f1,#fff);">
                        <td colspan="3" style="text-align:right;font-weight:700;font-size:16px;">Total</td>
                        <td style="font-weight:700;font-size:16px;color:var(--teal);">${{ number_format($recurring['total'] ?? 0, 2) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    @else
        <div class="empty-state">No line items.</div>
    @endif
</div>

<div class="card" style="display:flex;justify-content:flex-end;">
    <form method="POST" action="/recurring-invoices/{{ $recurring['recurring_id'] }}" onsubmit="return confirm('Delete this recurring invoice?')">
        <input type="hidden" name="_token" value="{{ \App\Middleware\CsrfMiddleware::token() }}">
        <input type="hidden" name="_method" value="DELETE">
        <button type="submit" class="btn btn-danger"><i class="fas fa-trash" style="margin-right:6px;"></i>Delete Recurring Invoice</button>
    </form>
</div>
@endsection
