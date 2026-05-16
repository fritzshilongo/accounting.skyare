@extends('layouts.app')

@section('title', 'Tax Rates - ' . ($company['company_name'] ?? 'Skyare'))

@section('content')
<div class="hero-card">
    <h1 class="hero-title">Tax Configuration</h1>
    <p class="hero-copy">Manage tax rates for your invoices, estimates, and recurring bills.</p>
</div>

{{-- Add Tax Rate Form --}}
<div class="form-card">
    <h3 class="section-title" style="margin-bottom:18px;"><i class="fas fa-plus-circle" style="color:var(--teal);margin-right:8px;"></i>Add Tax Rate</h3>
    <form method="POST" action="/tax-rates">
        <input type="hidden" name="_token" value="{{ \App\Middleware\CsrfMiddleware::token() }}">
        <div class="form-grid" style="grid-template-columns:1fr 120px 140px 120px auto;align-items:end;">
            <div>
                <label for="name">Name</label>
                <input type="text" id="name" name="name" placeholder="e.g. VAT, GST, Sales Tax" required value="{{ old('name') }}">
            </div>
            <div>
                <label for="rate">Rate</label>
                <input type="number" id="rate" name="rate" step="0.01" min="0" max="100" required placeholder="15.00" value="{{ old('rate') }}">
            </div>
            <div>
                <label for="type">Type</label>
                <select id="type" name="type">
                    <option value="percentage" {{ old('type', 'percentage') === 'percentage' ? 'selected' : '' }}>Percentage</option>
                    <option value="fixed" {{ old('type') === 'fixed' ? 'selected' : '' }}>Fixed Amount</option>
                </select>
            </div>
            <div style="display:flex;align-items:center;gap:8px;padding-bottom:2px;">
                <input type="checkbox" id="is_default" name="is_default" value="1" style="width:auto;" {{ old('is_default') ? 'checked' : '' }}>
                <label for="is_default" style="margin:0;text-transform:none;font-size:14px;">Default</label>
            </div>
            <div>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save" style="margin-right:6px;"></i>Add</button>
            </div>
        </div>
    </form>
</div>

{{-- Existing Tax Rates --}}
<div class="card">
    <h3 class="section-title" style="margin-bottom:18px;">Tax Rates</h3>
    @if(count($taxRates ?? []) > 0)
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Rate</th>
                        <th>Type</th>
                        <th>Default</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($taxRates as $rate)
                        <tr>
                            <td>
                                <form method="POST" action="/tax-rates/{{ $rate['tax_rate_id'] }}" id="editForm{{ $rate['tax_rate_id'] }}">
                                    <input type="hidden" name="_token" value="{{ \App\Middleware\CsrfMiddleware::token() }}">
                                    <input type="hidden" name="_method" value="PUT">
                                    <input type="text" name="name" value="{{ $rate['name'] }}" style="padding:8px 12px;border-radius:12px;" required>
                            </td>
                            <td>
                                    <input type="number" name="rate" value="{{ $rate['rate'] }}" step="0.01" min="0" max="100" style="padding:8px 12px;border-radius:12px;width:90px;" required>
                            </td>
                            <td>
                                    <select name="type" style="padding:8px 12px;border-radius:12px;width:120px;">
                                        <option value="percentage" {{ ($rate['type'] ?? 'percentage') === 'percentage' ? 'selected' : '' }}>Percentage</option>
                                        <option value="fixed" {{ ($rate['type'] ?? '') === 'fixed' ? 'selected' : '' }}>Fixed</option>
                                    </select>
                            </td>
                            <td>
                                    <input type="checkbox" name="is_default" value="1" style="width:auto;" {{ ($rate['is_default'] ?? 0) ? 'checked' : '' }}>
                                </form>
                            </td>
                            <td>
                                <span class="badge {{ ($rate['is_active'] ?? 1) ? 'teal' : 'rose' }}">
                                    {{ ($rate['is_active'] ?? 1) ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td>
                                <div class="inline-actions">
                                    <button type="submit" form="editForm{{ $rate['tax_rate_id'] }}" class="btn btn-ghost btn-sm">Save</button>
                                    <form method="POST" action="/tax-rates/{{ $rate['tax_rate_id'] }}/toggle" style="display:inline;">
                                        <input type="hidden" name="_token" value="{{ \App\Middleware\CsrfMiddleware::token() }}">
                                        <button type="submit" class="btn btn-sm {{ ($rate['is_active'] ?? 1) ? 'btn-secondary' : 'btn-primary' }}">
                                            {{ ($rate['is_active'] ?? 1) ? 'Disable' : 'Enable' }}
                                        </button>
                                    </form>
                                    <form method="POST" action="/tax-rates/{{ $rate['tax_rate_id'] }}" style="display:inline;" onsubmit="return confirm('Delete this tax rate?')">
                                        <input type="hidden" name="_token" value="{{ \App\Middleware\CsrfMiddleware::token() }}">
                                        <input type="hidden" name="_method" value="DELETE">
                                        <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="empty-state">
            <i class="fas fa-percent" style="font-size:32px;color:var(--muted);margin-bottom:12px;display:block;"></i>
            No tax rates configured. Add your first tax rate above.
        </div>
    @endif
</div>
@endsection
