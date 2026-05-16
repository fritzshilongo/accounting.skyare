@extends('layouts.app')

@section('title', 'Credit Agreement')

@section('content')
@php($currencySymbol = $_SESSION['user']['currency_symbol'] ?? 'N$')
<div class="hero-card">
    <h1 class="hero-title">Credit Agreement {{ $credit['credit_no'] ?? '' }}</h1>
    <p class="hero-copy">Customer: {{ $credit['customer_name'] ?? '-' }} · Amount financed: {{ $currencySymbol }}{{ number_format((float) ($credit['amount'] ?? 0), 2) }}</p>
</div>

<div class="card">
    <div class="toolbar-row">
        <div>
            <h3 class="section-title">Agreement Summary</h3>
            <p class="section-copy">All values below are generated from the live credit ledger and update when repayments are posted.</p>
        </div>
        <a class="btn btn-secondary" href="/credit-management/agreement?credit_id={{ $credit['credit_id'] }}&download=1">Download PDF</a>
    </div>

    <div class="form-grid two" style="margin-top:18px;">
        <div>
            <label>Customer</label>
            <input value="{{ $credit['customer_name'] ?? '-' }}" disabled>
        </div>
        <div>
            <label>Phone</label>
            <input value="{{ $credit['customer_phone'] ?? '-' }}" disabled>
        </div>
        <div>
            <label>Email</label>
            <input value="{{ $credit['customer_email'] ?? '-' }}" disabled>
        </div>
        <div>
            <label>ID / Registration</label>
            <input value="{{ $credit['tax_number'] ?? '-' }}" disabled>
        </div>
        <div>
            <label>ID Number</label>
            <input value="{{ $credit['id_number'] ?? '-' }}" disabled>
        </div>
        <div class="span-full">
            <label>Address</label>
            <input value="{{ $credit['customer_address'] ?? '-' }}" disabled>
        </div>
        <div>
            <label>Principal</label>
            <input value="{{ $currencySymbol }}{{ number_format((float) ($credit['amount'] ?? 0), 2) }}" disabled>
        </div>
        <div>
            <label>Interest</label>
            <input value="{{ number_format((float) ($credit['interest_percent'] ?? 0), 2) }}% ({{ strtoupper((string) ($credit['interest_type'] ?? 'flat')) }})" disabled>
        </div>
        <div>
            <label>Total repayable</label>
            <input value="{{ $currencySymbol }}{{ number_format((float) ($credit['total_amount'] ?? 0), 2) }}" disabled>
        </div>
        <div>
            <label>Outstanding</label>
            <input value="{{ $currencySymbol }}{{ number_format((float) ($credit['outstanding'] ?? 0), 2) }}" disabled>
        </div>
        <div>
            <label>Issue date</label>
            <input value="{{ $credit['issue_date'] ?? '-' }}" disabled>
        </div>
        <div>
            <label>Due date</label>
            <input value="{{ $credit['due_date'] ?? '-' }}" disabled>
        </div>
    </div>
</div>

<div class="card">
    <h3 class="section-title">Loan Terms & Conditions</h3>
    <div class="section-copy" style="margin-top:12px; line-height:1.7;">
        <p>1. The borrower agrees to repay the total repayable amount by the due date unless amended in writing.</p>
        <p>2. Repayments posted are applied directly to outstanding balance and are tracked on this agreement and exported PDFs.</p>
        <p>3. A payment cannot exceed the current outstanding amount.</p>
        <p>4. Late or non-payment may result in status changes to OVERDUE or BAD_DEBT based on company policy.</p>
        <p>5. This agreement is valid only when signed by both lender and borrower signatories.</p>
    </div>

    @if(!empty($credit['reason']))
        <h3 class="section-title" style="margin-top:18px;">Additional Notes</h3>
        <div class="section-copy" style="line-height:1.7; padding:12px; background:var(--surface-strong, rgba(255,255,255,0.7)); border-radius:12px; border:1px solid var(--line, rgba(24,49,83,0.08));">
            {{ $credit['reason'] }}
        </div>
    @endif
</div>

<div class="card">
    <h3 class="section-title">Repayment Trail</h3>
    @if(!empty($payments))
        @php($runningPaid = 0)
        @php($totalRepayable = (float) ($credit['total_amount'] ?? 0))
        <div class="table-wrap" style="margin-top:18px;">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Date</th>
                        <th>Amount Paid</th>
                        <th>Total Paid</th>
                        <th>Remaining Balance</th>
                        <th>Method</th>
                        <th>Reference</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($payments as $index => $payment)
                        @php($runningPaid += (float) ($payment['amount'] ?? 0))
                        @php($remainingBalance = max(0, $totalRepayable - $runningPaid))
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>{{ $payment['payment_date'] ?? '-' }}</td>
                            <td>{{ $currencySymbol }}{{ number_format((float) ($payment['amount'] ?? 0), 2) }}</td>
                            <td><strong>{{ $currencySymbol }}{{ number_format($runningPaid, 2) }}</strong></td>
                            <td style="color: {{ $remainingBalance > 0 ? 'var(--rose, #e74c3c)' : 'var(--teal, #27ae60)' }}; font-weight: 600;">
                                {{ $currencySymbol }}{{ number_format($remainingBalance, 2) }}
                            </td>
                            <td>{{ ucwords(str_replace('_', ' ', (string) ($payment['payment_method'] ?? '-'))) }}</td>
                            <td>{{ $payment['reference'] ?? '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="empty-state" style="margin-top:18px;">No repayments have been posted yet.</div>
    @endif
</div>

<div class="card">
    <h3 class="section-title">Signatories</h3>
    <div class="form-grid two" style="margin-top:18px;">
        <div>
            <label>Lender signatory</label>
            <input value="Name: ____________________    Date: ____________________" disabled>
        </div>
        <div>
            <label>Borrower signatory</label>
            <input value="Name: ____________________    Date: ____________________" disabled>
        </div>
    </div>
</div>
@endsection
