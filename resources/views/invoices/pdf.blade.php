<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $invoice->invoice_no }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #17324d; font-size: 12px; }
        .header { margin-bottom: 24px; }
        .title { font-size: 26px; font-weight: 700; }
        .meta { color: #5d6d81; margin-top: 6px; }
        .company-grid { width: 100%; margin-top: 12px; }
        .company-grid td { border: none; vertical-align: top; padding: 0 0 6px 0; }
        .right { text-align: right; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 10px; border: 1px solid #d5dde6; }
        th { background: #edf4f8; text-align: left; }
        .totals { margin-top: 24px; width: 320px; margin-left: auto; }
        .totals td { border: none; padding: 6px 0; }
        .totals .grand { font-size: 16px; font-weight: 700; }
        .section-title { margin-top: 26px; margin-bottom: 8px; font-size: 14px; font-weight: 700; }
        .sign-row { margin-top: 32px; width: 100%; }
        .sign-col { width: 48%; display: inline-block; }
        .bank-box { margin-top: 10px; padding: 12px; border: 1px solid #d5dde6; background: #f8fafb; }
        .bank-box td { border: none; padding: 3px 14px 3px 0; font-size: 11px; }
    </style>
</head>
<body>
    @php
        $currencySymbol = $_SESSION['user']['currency_symbol'] ?? ($company['currency_symbol'] ?? 'N$');
    @endphp
    <div class="header">
        @if(!empty($company['logo_data']))
            <img src="{{ $company['logo_data'] }}" alt="Logo" style="max-height:70px; max-width:200px; margin-bottom:10px;">
        @endif
        <div class="title">Invoice {{ $invoice->invoice_no }}</div>
        <div class="meta">Issue date: {{ $invoice->issue_date }} · Due date: {{ $invoice->due_date }}</div>

        <table class="company-grid">
            <tr>
                <td>
                    <strong>{{ $company['company_name'] ?? 'Company' }}</strong><br>
                    {{ $company['address'] ?? '' }} {{ $company['city'] ?? '' }} {{ $company['province'] ?? '' }}<br>
                    {{ $company['postal_code'] ?? '' }} {{ $company['country'] ?? '' }}<br>
                    Phone: {{ $company['phone'] ?? '-' }} · Email: {{ $company['email'] ?? '-' }}
                </td>
                <td class="right">
                    Reg No: {{ $company['registration_number'] ?? '-' }}<br>
                    VAT: {{ $company['vat_number'] ?? '-' }}<br>
                    Tax: {{ $company['tax_number'] ?? '-' }}
                </td>
            </tr>
        </table>

        @php
            $client = $invoice->client;
        @endphp
        <div class="section-title" style="margin-top:16px;">Bill To</div>
        <table class="company-grid">
            <tr>
                <td>
                    <strong>{{ $client->name ?? $invoice->client_name ?? 'Unknown client' }}</strong><br>
                    @if($client)
                        @if($client->type === 'company' && $client->registration_number)Reg: {{ $client->registration_number }}<br>@endif
                        @if($client->vat_number)VAT: {{ $client->vat_number }}<br>@endif
                        @if($client->tax_number)Tax No: {{ $client->tax_number }}<br>@endif
                        @if($client->address){{ $client->address }}<br>@endif
                        @if($client->phone)Phone: {{ $client->phone }}<br>@endif
                        @if($client->email)Email: {{ $client->email }}<br>@endif
                        @if($client->contact_person)Contact: {{ $client->contact_person }}<br>@endif
                    @endif
                </td>
                <td class="right">
                    @if($client && $client->type)Type: {{ ucfirst($client->type) }}@endif
                </td>
            </tr>
        </table>
    </div>

    <table>
        <thead>
            <tr>
                <th>Description</th>
                <th>Quantity</th>
                <th>Unit Price</th>
                <th>Line Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->items as $item)
                <tr>
                    <td>{{ $item->description ?? $item->product?->name ?? 'Item' }}</td>
                    <td>{{ number_format($item->quantity, 2) }}</td>
                    <td>{{ $currencySymbol }}{{ number_format($item->unit_price, 2) }}</td>
                    <td>{{ $currencySymbol }}{{ number_format($item->line_total, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals">
        <tr><td>Sub Total</td><td style="text-align:right;">{{ $currencySymbol }}{{ number_format((float) (($invoice->total ?: $invoice->amount) - ($invoice->tax_amount ?: 0)), 2) }}</td></tr>
        <tr><td>Tax</td><td style="text-align:right;">{{ $currencySymbol }}{{ number_format($invoice->tax_amount ?: 0, 2) }}</td></tr>
        <tr class="grand"><td>Total</td><td style="text-align:right;">{{ $currencySymbol }}{{ number_format($invoice->total ?: $invoice->amount, 2) }}</td></tr>
        <tr><td>Amount Paid</td><td style="text-align:right;">{{ $currencySymbol }}{{ number_format((float) $invoice->paid_amount, 2) }}</td></tr>
        <tr><td>Amount Owed</td><td style="text-align:right;">{{ $currencySymbol }}{{ number_format((float) $invoice->balance, 2) }}</td></tr>
    </table>

    <div class="section-title">Payment Trail</div>
    @if(($payments ?? collect())->count())
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Method</th>
                    <th>Amount Paid</th>
                </tr>
            </thead>
            <tbody>
                @foreach($payments as $payment)
                    <tr>
                        <td>{{ $payment->payment_date }}</td>
                        <td>{{ ucwords(str_replace('_', ' ', (string) $payment->method)) }}</td>
                        <td>{{ $currencySymbol }}{{ number_format((float) $payment->amount, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <div class="meta">No payments posted yet.</div>
    @endif

    <div class="section-title">Banking Details</div>
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

    <div class="section-title">Terms and Signatories</div>
    <div class="meta">Payment amount cannot exceed amount owed. Any revised invoice values reflect the current company profile at print time.</div>
    <div class="sign-row">
        <div class="sign-col">Authorized Signatory: ____________________</div>
        <div class="sign-col" style="text-align:right;">Client Signatory: ____________________</div>
    </div>
</body>
</html>