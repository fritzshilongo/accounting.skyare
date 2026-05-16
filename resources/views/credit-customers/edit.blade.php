@extends('layouts.app')

@section('title', 'Edit — ' . $customer['customer_name'])

@section('content')
<div class="hero-card">
    <h1 class="hero-title">Edit Customer</h1>
    <p class="hero-copy">Update details for <strong>{{ $customer['customer_name'] }}</strong>.</p>
</div>

@if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif

<div class="card" style="margin-top: 16px;">
    <form method="POST" action="/credit-customers/{{ $customer['customer_id'] }}">
        @csrf
        @method('PUT')

        <div class="form-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
            <div class="form-group" style="grid-column: 1 / -1;">
                <label class="form-label">Customer Name <span style="color: var(--rose);">*</span></label>
                <input type="text" name="customer_name" value="{{ old('customer_name', $customer['customer_name']) }}" class="form-control" required>
                @error('customer_name') <span class="form-error">{{ $message }}</span> @enderror
            </div>

            <div class="form-group">
                <label class="form-label">Email Address</label>
                <input type="email" name="email" value="{{ old('email', $customer['email']) }}" class="form-control">
                @error('email') <span class="form-error">{{ $message }}</span> @enderror
            </div>

            <div class="form-group">
                <label class="form-label">Phone Number</label>
                <input type="text" name="phone" value="{{ old('phone', $customer['phone']) }}" class="form-control">
                @error('phone') <span class="form-error">{{ $message }}</span> @enderror
            </div>

            <div class="form-group" style="grid-column: 1 / -1;">
                <label class="form-label">Street Address</label>
                <input type="text" name="address" value="{{ old('address', $customer['address']) }}" class="form-control">
            </div>

            <div class="form-group">
                <label class="form-label">City</label>
                <input type="text" name="city" value="{{ old('city', $customer['city']) }}" class="form-control">
            </div>

            <div class="form-group">
                <label class="form-label">Province / State</label>
                <input type="text" name="province" value="{{ old('province', $customer['province']) }}" class="form-control">
            </div>

            <div class="form-group">
                <label class="form-label">Postal Code</label>
                <input type="text" name="postal_code" value="{{ old('postal_code', $customer['postal_code']) }}" class="form-control">
            </div>

            <div class="form-group">
                <label class="form-label">Country</label>
                <input type="text" name="country" value="{{ old('country', $customer['country']) }}" class="form-control">
            </div>

            <div class="form-group">
                <label class="form-label">Tax Number (VAT)</label>
                <input type="text" name="tax_number" value="{{ old('tax_number', $customer['tax_number']) }}" class="form-control">
            </div>

            <div class="form-group">
                <label class="form-label">ID Number <span style="color: var(--ink-muted); font-size: 12px; font-weight: 400;">(National ID / Passport)</span></label>
                <input type="text" name="id_number" value="{{ old('id_number', $customer['id_number'] ?? '') }}" class="form-control" placeholder="e.g. 991231 5111 087">
                @error('id_number') <span class="form-error">{{ $message }}</span> @enderror
            </div>

            <div class="form-group">
                <label class="form-label">Status</label>
                <select name="status" class="form-control">
                    <option value="active" {{ ($customer['status'] ?? 'active') === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ ($customer['status'] ?? '') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>

            <div class="form-group" style="grid-column: 1 / -1;">
                <label class="form-label">Notes</label>
                <textarea name="notes" rows="3" class="form-control">{{ old('notes', $customer['notes']) }}</textarea>
            </div>
        </div>

        <div class="toolbar-row" style="margin-top: 20px;">
            <a href="/credit-customers/{{ $customer['customer_id'] }}" class="btn btn-ghost"><i class="fas fa-arrow-left"></i> Cancel</a>
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Update Customer</button>
        </div>
    </form>
</div>
@endsection
