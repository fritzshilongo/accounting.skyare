@extends('layouts.app')

@section('title', 'Add Credit Customer')

@section('content')
<div class="hero-card">
    <h1 class="hero-title">Add Credit Customer</h1>
    <p class="hero-copy">Register a new customer for credit/loan services.</p>
</div>

@if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif

<div class="card" style="margin-top: 16px;">
    <form method="POST" action="/credit-customers">
        @csrf

        <div class="form-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
            <div class="form-group" style="grid-column: 1 / -1;">
                <label class="form-label">Customer Name <span style="color: var(--rose);">*</span></label>
                <input type="text" name="customer_name" value="{{ old('customer_name') }}" class="form-control" required>
                @error('customer_name') <span class="form-error">{{ $message }}</span> @enderror
            </div>

            <div class="form-group">
                <label class="form-label">Email Address</label>
                <input type="email" name="email" value="{{ old('email') }}" class="form-control" placeholder="email@example.com">
                @error('email') <span class="form-error">{{ $message }}</span> @enderror
            </div>

            <div class="form-group">
                <label class="form-label">Phone Number</label>
                <input type="text" name="phone" value="{{ old('phone') }}" class="form-control" placeholder="+27 000 0000">
                @error('phone') <span class="form-error">{{ $message }}</span> @enderror
            </div>

            <div class="form-group" style="grid-column: 1 / -1;">
                <label class="form-label">Street Address</label>
                <input type="text" name="address" value="{{ old('address') }}" class="form-control">
            </div>

            <div class="form-group">
                <label class="form-label">City</label>
                <input type="text" name="city" value="{{ old('city') }}" class="form-control">
            </div>

            <div class="form-group">
                <label class="form-label">Province / State</label>
                <input type="text" name="province" value="{{ old('province') }}" class="form-control">
            </div>

            <div class="form-group">
                <label class="form-label">Postal Code</label>
                <input type="text" name="postal_code" value="{{ old('postal_code') }}" class="form-control">
            </div>

            <div class="form-group">
                <label class="form-label">Country</label>
                <input type="text" name="country" value="{{ old('country', 'South Africa') }}" class="form-control">
            </div>

            <div class="form-group">
                <label class="form-label">Tax Number (VAT)</label>
                <input type="text" name="tax_number" value="{{ old('tax_number') }}" class="form-control" placeholder="e.g. 4123456789">
            </div>

            <div class="form-group">
                <label class="form-label">ID Number <span style="color: var(--ink-muted); font-size: 12px; font-weight: 400;">(National ID / Passport)</span></label>
                <input type="text" name="id_number" value="{{ old('id_number') }}" class="form-control" placeholder="e.g. 991231 5111 087">
                @error('id_number') <span class="form-error">{{ $message }}</span> @enderror
            </div>

            <div class="form-group" style="grid-column: 1 / -1;">
                <label class="form-label">Notes</label>
                <textarea name="notes" rows="3" class="form-control" placeholder="Any additional notes about this customer...">{{ old('notes') }}</textarea>
            </div>
        </div>

        <div class="toolbar-row" style="margin-top: 20px;">
            <a href="/credit-customers" class="btn btn-ghost"><i class="fas fa-arrow-left"></i> Back</a>
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Customer</button>
        </div>
    </form>
</div>
@endsection
