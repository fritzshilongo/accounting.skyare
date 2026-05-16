@extends('layouts.app')

@section('title', 'Edit Client')

@section('content')
<section class="hero-card">
    <h1 class="hero-title">Edit Client</h1>
    <p class="hero-copy">Update client identity, billing information, and lifecycle status.</p>
</section>

<section class="form-card">
    <form method="POST" action="/clients/{{ $client->client_id }}" class="form-grid two">
        @csrf
        @method('PUT')
        <div>
            <label for="type">Type</label>
            <select id="type" name="type" required>
                <option value="company" {{ $client->type === 'company' ? 'selected' : '' }}>Company</option>
                <option value="individual" {{ $client->type === 'individual' ? 'selected' : '' }}>Individual</option>
            </select>
        </div>
        <div>
            <label for="status">Status</label>
            <select id="status" name="status" required>
                <option value="active" {{ $client->status === 'active' ? 'selected' : '' }}>Active</option>
                <option value="inactive" {{ $client->status === 'inactive' ? 'selected' : '' }}>Inactive</option>
                <option value="suspended" {{ $client->status === 'suspended' ? 'selected' : '' }}>Suspended</option>
            </select>
        </div>
        <div>
            <label for="name">Client name</label>
            <input id="name" name="name" value="{{ old('name', $client->name) }}" required>
        </div>
        <div>
            <label for="contact_person">Contact person</label>
            <input id="contact_person" name="contact_person" value="{{ old('contact_person', $client->contact_person) }}">
        </div>
        <div>
            <label for="email">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email', $client->email) }}">
        </div>
        <div>
            <label for="phone">Phone</label>
            <input id="phone" name="phone" value="{{ old('phone', $client->phone) }}">
        </div>
        <div>
            <label for="registration_number">Registration number</label>
            <input id="registration_number" name="registration_number" value="{{ old('registration_number', $client->registration_number) }}">
        </div>
        <div>
            <label for="vat_number">VAT number</label>
            <input id="vat_number" name="vat_number" value="{{ old('vat_number', $client->vat_number) }}">
        </div>
        <div>
            <label for="tax_number">Tax number</label>
            <input id="tax_number" name="tax_number" value="{{ old('tax_number', $client->tax_number) }}">
        </div>
        <div class="span-full">
            <label for="address">Address</label>
            <textarea id="address" name="address">{{ old('address', $client->address) }}</textarea>
        </div>
        <div class="toolbar-left span-full">
            <button type="submit" class="btn-primary">Save changes</button>
            <a href="/clients/{{ $client->client_id }}" class="btn btn-secondary">Back</a>
        </div>
    </form>
</section>
@endsection