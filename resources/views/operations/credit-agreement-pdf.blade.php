<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $credit['credit_no'] ?? 'Credit Agreement' }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #17324d; font-size: 12px; }
        .title { font-size: 22px; font-weight: 700; }
        .meta { color: #5d6d81; margin-top: 6px; }
        table { width: 100%; border-collapse: collapse; margin-top: 18px; }
        th, td { border: 1px solid #d5dde6; padding: 10px; }
        th { background: #edf4f8; text-align: left; }
        .section-title { margin-top: 24px; font-size: 14px; font-weight: 700; }
        .bank-box { margin-top: 10px; padding: 12px; border: 1px solid #d5dde6; background: #f8fafb; }
        .bank-box td { border: none; padding: 3px 14px 3px 0; font-size: 11px; }
        .sign-row { margin-top: 34px; }
        .sign-col { width: 48%; display: inline-block; }
    </style>
</head>
<body>
    @php($currencySymbol = $_SESSION['user']['currency_symbol'] ?? ($company['currency_symbol'] ?? 'N$'))
    @if(!empty($company['logo_data']))
        <img src="{{ $company['logo_data'] }}" alt="Logo" style="max-height:70px; max-width:200px; margin-bottom:10px;">
    @endif
    <div class="title">Credit Agreement {{ $credit['credit_no'] ?? '' }}</div>
    <div class="meta">Lender: {{ $company['company_name'] ?? 'Company' }}</div>
    <div class="meta">
        {{ $company['address'] ?? '' }} {{ $company['city'] ?? '' }} {{ $company['province'] ?? '' }} {{ $company['postal_code'] ?? '' }}<br>
        Phone: {{ $company['phone'] ?? '-' }} · Email: {{ $company['email'] ?? '-' }} · VAT: {{ $company['vat_number'] ?? '-' }}
    </div>

    <div class="section-title">Borrower Details</div>
    <div class="meta"><strong>{{ $credit['customer_name'] ?? '-' }}</strong></div>
    @if(!empty($credit['customer_phone']))<div class="meta">Phone: {{ $credit['customer_phone'] }}</div>@endif
    @if(!empty($credit['customer_email']))<div class="meta">Email: {{ $credit['customer_email'] }}</div>@endif
    @if(!empty($credit['customer_address']))<div class="meta">Address: {{ $credit['customer_address'] }}</div>@endif
    @if(!empty($credit['tax_number']))<div class="meta">Tax/Registration: {{ $credit['tax_number'] }}</div>@endif
    @if(!empty($credit['id_number']))<div class="meta">ID Number: {{ $credit['id_number'] }}</div>@endif
    <div class="meta">Issue date: {{ $credit['issue_date'] ?? '-' }} · Due date: {{ $credit['due_date'] ?? '-' }}</div>

    <table>
        <thead>
            <tr>
                <th>Principal</th>
                <th>Interest</th>
                <th>Total Repayable</th>
                <th>Amount Paid</th>
                <th>Amount Owed</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>{{ $currencySymbol }}{{ number_format((float) ($credit['amount'] ?? 0), 2) }}</td>
                <td>{{ number_format((float) ($credit['interest_percent'] ?? 0), 2) }}% {{ strtoupper((string) ($credit['interest_type'] ?? 'flat')) }}</td>
                <td>{{ $currencySymbol }}{{ number_format((float) ($credit['total_amount'] ?? 0), 2) }}</td>
                <td>{{ $currencySymbol }}{{ number_format((float) ($credit['amount_paid'] ?? 0), 2) }}</td>
                <td>{{ $currencySymbol }}{{ number_format((float) ($credit['outstanding'] ?? 0), 2) }}</td>
                <td>{{ $credit['status'] ?? 'ACTIVE' }}</td>
            </tr>
        </tbody>
    </table>

    <div class="section-title">Repayment Trail</div>
    @if(!empty($payments))
        @php($runningPaid = 0)
        @php($totalRepayable = (float) ($credit['total_amount'] ?? 0))
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
                        <td style="color: {{ $remainingBalance > 0 ? '#e74c3c' : '#27ae60' }}; font-weight: bold;">
                            {{ $currencySymbol }}{{ number_format($remainingBalance, 2) }}
                        </td>
                        <td>{{ ucwords(str_replace('_', ' ', (string) ($payment['payment_method'] ?? '-'))) }}</td>
                        <td>{{ $payment['reference'] ?? '-' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <div class="meta">No payments recorded yet.</div>
    @endif

    <div class="section-title">Loan Terms and Conditions</div>
    <div class="meta">1. Borrower shall repay all outstanding amounts by due date.</div>
    <div class="meta">2. Payments are applied to outstanding balance and tracked on every agreement printout.</div>
    <div class="meta">3. Payment amount cannot exceed amount owed.</div>
    <div class="meta">4. Agreement changes are reflected on subsequent printouts.</div>

    @if(!empty($credit['reason']))
        <div class="section-title">Additional Notes</div>
        <div class="meta">{{ $credit['reason'] }}</div>
    @endif

    <div class="section-title">Banking Details for Repayments</div>
    <div class="bank-box">
        <table>
            <tr>
                <td><strong>Bank:</strong> {{ $company['bank_name'] ?? '-' }}</td>
                <td><strong>Account Name:</strong> {{ $company['bank_account_holder'] ?? '-' }}</td>
            </tr>
            <tr>
                <td><strong>Account No:</strong> {{ $company['bank_account_number'] ?? '-' }}</td>
                <td><strong>Account Type:</strong> {{ $company['bank_account_type'] ?? '-' }}</td>
            </tr>
            <tr>
                <td><strong>Branch Code:</strong> {{ $company['bank_branch_code'] ?? '-' }}</td>
                <td><strong>Routing No:</strong> {{ $company['bank_routing_number'] ?? '-' }}</td>
            </tr>
            <tr>
                <td><strong>SWIFT / BIC:</strong> {{ $company['bank_swift_code'] ?? '-' }}</td>
                <td><strong>IBAN:</strong> {{ $company['bank_iban'] ?? '-' }}</td>
            </tr>
        </table>
    </div>

    <div class="section-title">Signatories</div>
    <div class="sign-row">
        <div class="sign-col">Lender Signatory: ____________________</div>
        <div class="sign-col" style="text-align:right;">Borrower Signatory: ____________________</div>
    </div>
</body>
</html>
