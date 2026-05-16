<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Estimate #{{ $estimate->estimate_id }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #17324d; font-size: 12px; }
        .header { margin-bottom: 22px; }
        .title { font-size: 24px; font-weight: 700; }
        .meta { color: #5d6d81; margin-top: 6px; }
        table { width: 100%; border-collapse: collapse; margin-top: 18px; }
        th, td { border: 1px solid #d5dde6; padding: 10px; }
        th { background: #edf4f8; text-align: left; }
        .totals { margin-top: 20px; width: 320px; margin-left: auto; }
        .totals td { border: none; padding: 6px 0; }
        .grand { font-size: 15px; font-weight: 700; }
        .section-title { margin-top: 24px; font-size: 14px; font-weight: 700; }
        .company-grid { width: 100%; margin-top: 12px; border-collapse: collapse; }
        .company-grid td { border: none; vertical-align: top; padding: 0 0 6px 0; }
        .right { text-align: right; }
        .bank-box { margin-top: 22px; padding: 12px; border: 1px solid #d5dde6; background: #f8fafb; }
        .bank-box td { border: none; padding: 3px 14px 3px 0; font-size: 11px; }
        .sign-row { margin-top: 32px; }
        .sign-col { width: 48%; display: inline-block; }
    </style>
</head>
<body>
    @php($currencySymbol = $_SESSION['user']['currency_symbol'] ?? ($company['currency_symbol'] ?? 'N$'))
    <div class="header">
        @if(!empty($company['logo_data']))
            <img src="{{ $company['logo_data'] }}" alt="Logo" style="max-height:70px; max-width:200px; margin-bottom:10px;">
        @endif
        <div class="title">Estimate #{{ $estimate->estimate_id }}</div>
        <div class="meta">Issue date: {{ $estimate->estimate_date }} · Expiry date: {{ $estimate->expiry_date }}</div>

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

        @php($client = $estimate->client)
        <div class="section-title" style="margin-top:16px;">Prepared For</div>
        <table class="company-grid">
            <tr>
                <td>
                    <strong>{{ $client->name ?? $estimate->client_name ?? ('Client #' . ($estimate->customer_id ?? '-')) }}</strong><br>
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
            <tr>
                <td>{{ $estimate->product->description ?? $estimate->product->name ?? 'Quoted item' }}</td>
                <td>{{ number_format((float) ($estimate->quantity ?? 1), 2) }}</td>
                <td>{{ $currencySymbol }}{{ number_format((float) ($estimate->unit_price ?? 0), 2) }}</td>
                <td>{{ $currencySymbol }}{{ number_format((float) ($estimate->amount ?? 0), 2) }}</td>
            </tr>
        </tbody>
    </table>

    <table class="totals">
        <tr><td>Tax</td><td style="text-align:right;">{{ $currencySymbol }}{{ number_format((float) ($estimate->tax_amount ?? 0), 2) }}</td></tr>
        <tr class="grand"><td>Total</td><td style="text-align:right;">{{ $currencySymbol }}{{ number_format((float) ($estimate->total ?? $estimate->amount ?? 0), 2) }}</td></tr>
    </table>

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

    <div class="section-title">Approval Signatories</div>
    <div class="meta">This estimate is valid until expiry date and subject to final client approval.</div>
    <div class="sign-row">
        <div class="sign-col">Authorized Signatory: ____________________</div>
        <div class="sign-col" style="text-align:right;">Client Approval Signatory: ____________________</div>
    </div>
</body>
</html>
