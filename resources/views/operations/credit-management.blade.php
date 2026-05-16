@extends('layouts.app')

@section('title', 'Credit Management')

@section('content')
@php($currencySymbol = $_SESSION['user']['currency_symbol'] ?? 'N$')
<div class="hero-card">
    <h1 class="hero-title">Credit Management</h1>
    <p class="hero-copy">Issue facilities, capture repayments, and monitor paid versus outstanding amounts in real time.</p>
    <div style="margin-top: 10px;">
        <a href="/credit-customers" class="btn btn-secondary btn-sm"><i class="fas fa-users"></i> Manage Credit Customers</a>
    </div>
</div>

<div class="panel-grid">
    <div class="form-card">
        <h3 class="section-title">Issue Credit Facility</h3>
        <form method="POST" action="/credit-management/issue" class="form-grid two" style="margin-top:18px;">
            <input type="hidden" name="_token" value="{{ \App\Middleware\CsrfMiddleware::token() }}">

            <div>
                <label for="customer_id">Customer</label>
                <select id="customer_id" name="customer_id">
                    <option value="">Select customer (optional)</option>
                    @foreach(($customers ?? []) as $customer)
                        <option value="{{ $customer['customer_id'] }}">{{ $customer['customer_name'] }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="customer_name">Or customer name</label>
                <input id="customer_name" name="customer_name" value="{{ old('customer_name') }}" placeholder="If not selecting from list">
            </div>
            <div>
                <label for="amount">Principal amount</label>
                <input id="amount" type="number" step="0.01" min="0.01" name="amount" value="{{ old('amount') }}" required>
            </div>
            <div>
                <label for="interest_percent">Interest percentage (%)</label>
                <input id="interest_percent" type="number" step="0.01" min="0" name="interest_percent" value="{{ old('interest_percent', 0) }}" required>
            </div>
            <div>
                <label for="interest_type">Interest type</label>
                <select id="interest_type" name="interest_type" required>
                    <option value="flat">Flat</option>
                    <option value="monthly">Monthly</option>
                    <option value="daily">Daily</option>
                </select>
            </div>
            <div>
                <label for="due_date">Due date</label>
                <input id="due_date" type="date" name="due_date" value="{{ old('due_date') }}">
            </div>
            <div class="span-full">
                <label for="reason">Terms / notes</label>
                <textarea id="reason" name="reason" placeholder="Loan purpose, terms, and any lending conditions">{{ old('reason') }}</textarea>
            </div>
            <div class="span-full toolbar-left">
                <button type="submit" class="btn btn-primary">Issue Credit</button>
            </div>
        </form>
    </div>

    <div class="form-card">
        <h3 class="section-title">Record Credit Payment</h3>
        <form method="POST" action="/credit-management/payment" class="form-grid two" style="margin-top:18px;">
            <input type="hidden" name="_token" value="{{ \App\Middleware\CsrfMiddleware::token() }}">

            <div>
                <label for="credit_id">Credit facility</label>
                <select id="credit_id" name="credit_id" required>
                    <option value="">Select credit</option>
                    @foreach(($credits ?? []) as $credit)
                        <option value="{{ $credit['credit_id'] }}">
                            {{ $credit['credit_no'] }} · {{ $credit['customer_name'] }} · Owed {{ $currencySymbol }}{{ number_format((float) ($credit['outstanding'] ?? 0), 2) }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="payment_method">Method</label>
                <select id="payment_method" name="payment_method">
                    <option value="bank_transfer">Bank transfer</option>
                    <option value="cash">Cash</option>
                    <option value="check">Check</option>
                    <option value="mobile_money">Mobile money</option>
                </select>
            </div>
            <div>
                <label for="payment_date">Payment date</label>
                <input id="payment_date" type="date" name="payment_date" value="{{ old('payment_date', now()->toDateString()) }}">
            </div>
            <div>
                <label for="pay_amount">Amount paid</label>
                <input id="pay_amount" type="number" step="0.01" min="0.01" name="amount" value="{{ old('amount') }}" required>
            </div>
            <div class="span-full">
                <label for="reference">Reference</label>
                <input id="reference" name="reference" value="{{ old('reference') }}" placeholder="Receipt number / transfer reference">
            </div>
            <div class="span-full toolbar-left">
                <button type="submit" class="btn btn-accent">Apply Payment</button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="toolbar-row">
        <h3 class="section-title">Credit Facilities</h3>
        <span class="badge teal">Live Portfolio</span>
    </div>
    @if(!empty($credits))
        <div class="table-wrap" style="margin-top:18px;">
            <table>
                <thead>
                    <tr>
                        <th>Credit #</th>
                        <th>Customer</th>
                        <th>Principal</th>
                        <th>Interest</th>
                        <th>Total</th>
                        <th>Paid</th>
                        <th>Outstanding</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($credits as $credit)
                        <tr>
                            <td>{{ $credit['credit_no'] ?? ('#' . ($credit['credit_id'] ?? '-')) }}</td>
                            <td>{{ $credit['customer_name'] ?? '-' }}</td>
                            <td>{{ $currencySymbol }}{{ number_format((float) ($credit['amount'] ?? 0), 2) }}</td>
                            <td>{{ number_format((float) ($credit['interest_percent'] ?? 0), 2) }}%</td>
                            <td>{{ $currencySymbol }}{{ number_format((float) ($credit['total_amount'] ?? 0), 2) }}</td>
                            <td>{{ $currencySymbol }}{{ number_format((float) ($credit['amount_paid'] ?? 0), 2) }}</td>
                            <td>{{ $currencySymbol }}{{ number_format((float) ($credit['outstanding'] ?? 0), 2) }}</td>
                            <td>
                                <span class="badge {{ ($credit['status'] ?? '') === 'PAID' ? 'teal' : (($credit['status'] ?? '') === 'BAD_DEBT' ? 'rose' : 'amber') }}">
                                    {{ $credit['status'] ?? 'ACTIVE' }}
                                </span>
                            </td>
                            <td class="inline-actions">
                                <a href="/credit-management/view?credit_id={{ $credit['credit_id'] }}" class="btn btn-secondary btn-sm">View</a>
                                <a href="/credit-management/agreement?credit_id={{ $credit['credit_id'] }}" class="btn btn-ghost btn-sm">Agreement</a>
                                @if(($credit['status'] ?? '') !== 'BAD_DEBT' && (float) ($credit['outstanding'] ?? 0) > 0)
                                    <form method="POST" action="/credit-management/write-off" style="display:inline-flex;">
                                        <input type="hidden" name="_token" value="{{ \App\Middleware\CsrfMiddleware::token() }}">
                                        <input type="hidden" name="credit_id" value="{{ $credit['credit_id'] }}">
                                        <input type="hidden" name="reason" value="Write-off initiated from credit management panel">
                                        <button type="submit" class="btn btn-danger btn-sm">Write Off</button>
                                    </form>
                                @elseif(($credit['status'] ?? '') === 'BAD_DEBT')
                                    <form method="POST" action="/credit-management/reopen" style="display:inline-flex;">
                                        <input type="hidden" name="_token" value="{{ \App\Middleware\CsrfMiddleware::token() }}">
                                        <input type="hidden" name="credit_id" value="{{ $credit['credit_id'] }}">
                                        <button type="submit" class="btn btn-accent btn-sm">Reopen</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="empty-state" style="margin-top:18px;">No credits issued.</div>
    @endif
</div>

<div class="card">
    <div class="toolbar-row">
        <h3 class="section-title">Recent Credit Payments</h3>
        <span class="badge navy">Last 50</span>
    </div>
    @if(!empty($recentPayments))
        <div class="table-wrap" style="margin-top:18px;">
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Customer</th>
                        <th>Amount Paid</th>
                        <th>Method</th>
                        <th>Reference</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($recentPayments as $payment)
                        <tr>
                            <td>{{ $payment['payment_date'] ?? '-' }}</td>
                            <td>{{ $payment['customer_name'] ?? '-' }}</td>
                            <td>{{ $currencySymbol }}{{ number_format((float) ($payment['amount'] ?? 0), 2) }}</td>
                            <td>{{ ucwords(str_replace('_', ' ', (string) ($payment['payment_method'] ?? '-'))) }}</td>
                            <td>{{ $payment['reference'] ?? '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="empty-state" style="margin-top:18px;">No credit payments recorded yet.</div>
    @endif
</div>
@endsection
