@extends('layouts.app')

@section('title', 'Settings')

@section('content')
<section class="hero-card">
    <div class="toolbar">
        <div>
            <h1 class="hero-title">Workspace Settings</h1>
            <p class="hero-copy">Control company identity, tax defaults, invoice numbering, banking information, and payment preferences from one place.</p>
        </div>
        @if($saved)
            <span class="badge teal">Changes saved</span>
        @endif

        @if($errors->any())
            <div class="form-card" style="margin-top: 16px; padding: 16px; border: 1px solid #f2dede; background: #fff1f0; color: #8a1f11; border-radius: 14px;">
                <strong>Unable to save settings:</strong>
                <ul style="margin: 8px 0 0 18px;">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if(!empty($missingBankColumns))
            <div class="form-card" style="margin-top: 16px; padding: 16px; border: 1px solid #c5d6eb; background: #eef5fb; color: #1b3b70; border-radius: 14px;">
                <strong>Note:</strong> The following bank fields cannot be saved because your database is missing columns:
                <ul style="margin: 8px 0 0 18px;">
                    @foreach($missingBankColumns as $column)
                        <li>{{ $column }}</li>
                    @endforeach
                </ul>
                <p style="margin-top: 8px;">Run the latest company migrations or add these columns to the <code>companies</code> table to store the new bank details.</p>
            </div>
        @endif
    </div>
</section>

<form method="POST" action="/settings" enctype="multipart/form-data" class="page-stack">
    <input type="hidden" name="_token" value="{{ \App\Middleware\CsrfMiddleware::token() }}">

    <div class="panel-grid">
        <section class="form-card">
            <h2 class="section-title">Company Identity</h2>
            <p class="section-copy">These details appear across invoices, client communications, and internal reports.</p>

            <div class="form-grid two" style="margin-top:18px;">
                <div>
                    <label for="company_name">Company name</label>
                    <input id="company_name" name="company_name" value="{{ old('company_name', $company['company_name'] ?? '') }}" required>
                </div>
                <div>
                    <label for="registration_number">Registration number</label>
                    <input id="registration_number" name="registration_number" value="{{ old('registration_number', $company['registration_number'] ?? '') }}">
                </div>
                <div>
                    <label for="email">Finance email</label>
                    <input id="email" type="email" name="email" value="{{ old('email', $company['email'] ?? '') }}">
                </div>
                <div>
                    <label for="phone">Phone</label>
                    <input id="phone" name="phone" value="{{ old('phone', $company['phone'] ?? '') }}">
                </div>
                <div class="span-full">
                    <label for="address">Address</label>
                    <input id="address" name="address" value="{{ old('address', $company['address'] ?? '') }}">
                </div>
                <div>
                    <label for="city">City</label>
                    <input id="city" name="city" value="{{ old('city', $company['city'] ?? '') }}">
                </div>
                <div>
                    <label for="province">Province / state</label>
                    <input id="province" name="province" value="{{ old('province', $company['province'] ?? '') }}">
                </div>
                <div>
                    <label for="postal_code">Postal code</label>
                    <input id="postal_code" name="postal_code" value="{{ old('postal_code', $company['postal_code'] ?? '') }}">
                </div>
                <div>
                    <label for="country">Country</label>
                    <input id="country" name="country" value="{{ old('country', $company['country'] ?? '') }}">
                </div>
                <div class="span-full">
                    <label for="logo">Company Logo</label>
                    @if(!empty($company['logo_data']))
                        <div style="margin-bottom:10px;">
                            <img src="{{ $company['logo_data'] }}" alt="Company Logo" style="max-height:80px; max-width:240px; border:1px solid rgba(24,49,83,0.12); border-radius:8px; padding:6px; background:#fff;">
                        </div>
                    @endif
                    <input id="logo" type="file" name="logo" accept="image/png,image/jpeg,image/gif,image/webp">
                    <small style="color:var(--ink-muted);font-size:12px;">PNG, JPG, GIF or WebP. Max 2MB. This logo will appear on invoices, estimates, and credit agreements.</small>
                </div>
            </div>
        </section>

        <section class="form-card">
            <h2 class="section-title">Tax & Billing Defaults</h2>
            <p class="section-copy">Set the finance defaults your invoicing team uses every day.</p>

            <div class="form-grid two" style="margin-top:18px;">
                <div>
                    <label for="tax_type">Tax type</label>
                    <input id="tax_type" name="tax_type" value="{{ old('tax_type', $settings['tax_settings']['tax_type'] ?? 'VAT') }}">
                </div>
                <div>
                    <label for="tax_rate">Tax rate (%)</label>
                    <input id="tax_rate" type="number" step="0.01" name="tax_rate" value="{{ old('tax_rate', $settings['tax_settings']['tax_rate'] ?? 10) }}">
                </div>
                <div>
                    <label for="tax_number">Tax number</label>
                    <input id="tax_number" name="tax_number" value="{{ old('tax_number', $company['tax_number'] ?? '') }}">
                </div>
                <div>
                    <label for="vat_number">VAT number</label>
                    <input id="vat_number" name="vat_number" value="{{ old('vat_number', $company['vat_number'] ?? '') }}">
                </div>
                <div>
                    <label for="invoice_prefix">Invoice prefix</label>
                    <input id="invoice_prefix" name="invoice_prefix" value="{{ old('invoice_prefix', $settings['invoice_settings']['invoice_prefix'] ?? 'INV-') }}">
                </div>
                <div>
                    <label for="next_invoice_number">Next invoice number</label>
                    <input id="next_invoice_number" type="number" name="next_invoice_number" value="{{ old('next_invoice_number', $settings['invoice_settings']['next_invoice_number'] ?? 1001) }}">
                </div>
                <div class="span-full">
                    <label for="default_payment_terms">Default payment terms (days)</label>
                    <input id="default_payment_terms" type="number" name="default_payment_terms" value="{{ old('default_payment_terms', $settings['invoice_settings']['default_payment_terms'] ?? 7) }}">
                </div>
            </div>
        </section>
    </div>

    <div class="panel-grid">
        <section class="form-card">
            <h2 class="section-title">Banking Details</h2>
            <p class="section-copy">Used for invoice footers and payment collection instructions.</p>

            <div class="form-grid two" style="margin-top:18px;">
                <div>
                    <label for="bank_name">Bank name</label>
                    <input id="bank_name" name="bank_name" value="{{ old('bank_name', $company['bank_name'] ?? '') }}">
                </div>
                <div>
                    <label for="bank_account_holder">Account holder</label>
                    <input id="bank_account_holder" name="bank_account_holder" value="{{ old('bank_account_holder', $company['bank_account_holder'] ?? '') }}">
                </div>
                <div>
                    <label for="bank_account_number">Account number</label>
                    <input id="bank_account_number" name="bank_account_number" value="{{ old('bank_account_number', $company['bank_account_number'] ?? '') }}">
                </div>
                <div>
                    <label for="bank_account_type">Account type</label>
                    <input id="bank_account_type" name="bank_account_type" value="{{ old('bank_account_type', $company['bank_account_type'] ?? '') }}">
                </div>
                <div>
                    <label for="bank_branch_code">Branch code</label>
                    <input id="bank_branch_code" name="bank_branch_code" value="{{ old('bank_branch_code', $company['bank_branch_code'] ?? '') }}">
                </div>
                <div>
                    <label for="bank_routing_number">Routing number</label>
                    <input id="bank_routing_number" name="bank_routing_number" value="{{ old('bank_routing_number', $company['bank_routing_number'] ?? '') }}">
                </div>
                <div>
                    <label for="bank_swift_code">SWIFT / BIC code</label>
                    <input id="bank_swift_code" name="bank_swift_code" value="{{ old('bank_swift_code', $company['bank_swift_code'] ?? '') }}">
                </div>
                <div>
                    <label for="bank_iban">IBAN</label>
                    <input id="bank_iban" name="bank_iban" value="{{ old('bank_iban', $company['bank_iban'] ?? '') }}">
                </div>
            </div>
        </section>

        <section class="form-card">
            <h2 class="section-title">Payment Preferences</h2>
            <p class="section-copy">Choose which payment rails appear throughout the system.</p>

            @php
                $selectedMethods = old('payment_methods', $settings['payment_methods'] ?? []);
                $availableMethods = ['bank_transfer', 'credit_card', 'check', 'cash', 'mobile_money'];
            @endphp

            <div class="form-grid two" style="margin-top:18px;">
                @foreach($availableMethods as $method)
                    <label style="display:flex; align-items:center; gap:12px; border:1px solid rgba(24,49,83,0.12); border-radius:16px; padding:14px 16px; margin:0; text-transform:none; letter-spacing:0; color:var(--ink);">
                        <input type="checkbox" name="payment_methods[]" value="{{ $method }}" {{ in_array($method, $selectedMethods, true) ? 'checked' : '' }} style="width:auto; margin:0;">
                        <span>{{ ucwords(str_replace('_', ' ', $method)) }}</span>
                    </label>
                @endforeach
            </div>

            <div style="margin-top:22px; display:flex; gap:12px; flex-wrap:wrap;">
                <button type="submit" class="btn-primary">Save workspace settings</button>
                <a href="/dashboard" class="btn-secondary btn">Back to dashboard</a>
                <a href="/settings/backups" class="btn btn-ghost" style="display:flex; align-items:center; gap:7px;">
                    <i class="fa fa-cloud-arrow-up"></i> Manage Backups
                </a>
            </div>
        </section>
    </div>
</form>
@endsection
