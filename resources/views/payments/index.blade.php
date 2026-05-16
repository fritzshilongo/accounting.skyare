@extends('layouts.app')

@section('title', 'Payments')

@section('content')
<section class="hero-card">
    <div class="toolbar">
        <div>
            <h1 class="hero-title">Payment Register</h1>
            <p class="hero-copy">Review collections against invoices and keep cash application visible.</p>
        </div>
        <a href="/payments/create" class="btn btn-primary">Record Payment</a>
    </div>
</section>

<section class="table-card">
    <form method="GET" action="/payments" class="filter-bar" style="margin-bottom:18px;">
        <div>
            <label for="search">Search</label>
            <input id="search" name="search" value="{{ request('search') }}" placeholder="Invoice no or client">
        </div>
        <div>
            <label for="method">Method</label>
            <select id="method" name="method">
                <option value="">All methods</option>
                <option value="bank_transfer" {{ request('method') === 'bank_transfer' ? 'selected' : '' }}>Bank transfer</option>
                <option value="credit_card" {{ request('method') === 'credit_card' ? 'selected' : '' }}>Credit card</option>
                <option value="check" {{ request('method') === 'check' ? 'selected' : '' }}>Check</option>
                <option value="cash" {{ request('method') === 'cash' ? 'selected' : '' }}>Cash</option>
            </select>
        </div>
        <div>
            <label for="from">From</label>
            <input id="from" type="date" name="from" value="{{ request('from') }}">
        </div>
        <div>
            <label for="to">To</label>
            <input id="to" type="date" name="to" value="{{ request('to') }}">
        </div>
        <div style="display:flex; gap:10px; align-items:end;">
            <button type="submit" class="btn-primary">Apply</button>
            <a href="/payments" class="btn btn-ghost">Reset</a>
        </div>
    </form>

    @if($payments->count())
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Payment</th>
                        <th>Invoice</th>
                        <th>Amount</th>
                        <th>Date</th>
                        <th>Method</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($payments as $payment)
                        <tr>
                            <td><a href="/payments/{{ $payment->payment_id }}" class="row-link"><div class="row-title">#{{ $payment->payment_id }}</div></a></td>
                            <td>{{ $payment->invoice->invoice_no ?? 'Unknown invoice' }}</td>
                            <td>${{ number_format($payment->amount, 2) }}</td>
                            <td>{{ $payment->payment_date }}</td>
                            <td><span class="badge teal">{{ ucfirst(str_replace('_', ' ', $payment->method)) }}</span></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="pagination-wrap">{{ $payments->links() }}</div>
    @else
        <div class="empty-state">No payments found matching your filters.</div>
    @endif
</section>
@endsection