@extends('layouts.app')

@section('title', 'Register Your Company')

@section('content')
<div class="card" style="max-width:480px;margin:40px auto;">
    <h2 class="section-title" style="margin-bottom:18px;">Register Your Company</h2>

    @if(session('success'))
        <div class="alert alert-success" style="background:#d4edda;color:#155724;padding:10px 14px;border-radius:4px;margin-bottom:16px;">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger" style="background:#f8d7da;color:#721c24;padding:10px 14px;border-radius:4px;margin-bottom:16px;">
            <ul style="margin:0;padding-left:18px;">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="/register-company">
        @csrf
        <div class="form-group">
            <label for="company_name">Company Name</label>
            <input type="text" name="company_name" id="company_name" value="{{ old('company_name') }}" placeholder="Example: Acme Trading" required>
            @error('company_name')<small style="color:#dc3545;">{{ $message }}</small>@enderror
        </div>
        <div class="form-group">
            <label for="admin_email">Admin Email</label>
            <input type="email" name="admin_email" id="admin_email" value="{{ old('admin_email') }}" placeholder="Example: admin@acme.co.za" required>
            @error('admin_email')<small style="color:#dc3545;">{{ $message }}</small>@enderror
        </div>
        <div class="form-group">
            <label for="subdomain">Desired Subdomain</label>
            <input type="text" name="subdomain" id="subdomain" value="{{ old('subdomain') }}" placeholder="Example: acme" pattern="[a-z0-9]{3,32}" title="3-32 lowercase letters or numbers" required>
            <small style="color:var(--muted);">Your app will be available at <span style="color:var(--teal);">subdomain.{{ config('app.base_domain', 'skyare.space') }}</span></small>
            @error('subdomain')<small style="color:#dc3545;">{{ $message }}</small>@enderror
        </div>
        <div class="form-group">
            <label for="admin_password">Admin Password <small style="color:var(--muted);">(optional — a password will be emailed if left blank)</small></label>
            <input type="password" name="admin_password" id="admin_password" placeholder="Minimum 8 characters" minlength="8">
        </div>
        <button type="submit" class="btn btn-primary" style="margin-top:18px;width:100%;">Register &amp; Start Free Trial</button>
    </form>
</div>
@endsection
