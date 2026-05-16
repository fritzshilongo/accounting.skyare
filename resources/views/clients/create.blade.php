@extends('layouts.app')

@section('title', 'Create Client')

@section('content')
<section class="hero-card">
    <h1 class="hero-title">Add Client</h1>
    <p class="hero-copy">Create a clean client record with identity, tax, and contact details.</p>
</section>

<section class="form-card">
    <form method="POST" action="/clients" class="form-grid two">
        @csrf
        <div>
            <label for="type">Type</label>
            <select id="type" name="type" required>
                <option value="company">Company</option>
                <option value="individual">Individual</option>
            </select>
        </div>
        <div>
            <label for="name">Client name</label>
            <input id="name" name="name" value="{{ old('name') }}" required>
        </div>
        <div>
            <label for="contact_person">Contact person</label>
            <input id="contact_person" name="contact_person" value="{{ old('contact_person') }}">
        </div>
        <div>
            <label for="email">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}">
        </div>
        <div>
            <label for="phone">Phone</label>
            <input id="phone" name="phone" value="{{ old('phone') }}">
        </div>
        <div>
            <label for="registration_number">Registration number</label>
            <input id="registration_number" name="registration_number" value="{{ old('registration_number') }}">
        </div>
        <div>
            <label for="vat_number">VAT number</label>
            <input id="vat_number" name="vat_number" value="{{ old('vat_number') }}">
        </div>
        <div>
            <label for="tax_number">Tax number</label>
            <input id="tax_number" name="tax_number" value="{{ old('tax_number') }}">
        </div>
        <div class="span-full">
            <label for="address">Address</label>
            <textarea id="address" name="address">{{ old('address') }}</textarea>
        </div>
        <div class="toolbar-left span-full">
            <button type="submit" class="btn-primary">Create client</button>
            <a href="/clients" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</section>
@endsection