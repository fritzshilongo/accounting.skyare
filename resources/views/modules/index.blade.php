@extends('layouts.app')

@section('title', 'Modules')

@section('content')
<div class="hero-card">
    <h1 class="hero-title">Available Modules</h1>
    <p class="hero-copy">Launch domain-specific tools for billing, inventory, reporting, and compliance workflows.</p>
</div>

<div class="stats-grid">
    @foreach($modules as $module)
        <a href="{{ $module['route'] }}" class="module-card card">
            <div class="module-icon">
                    <i class="fas {{ $module['icon'] }}"></i>
            </div>
            <h3 class="section-title">{{ $module['name'] }}</h3>
            <p class="section-copy">{{ $module['description'] }}</p>
        </a>
    @endforeach
</div>

<style>
    .module-card {
        display: block;
        text-align: center;
        transition: transform 0.24s ease, box-shadow 0.24s ease;
    }

    .module-card:hover {
        transform: translateY(-6px);
        box-shadow: 0 20px 30px rgba(23, 50, 77, 0.18);
    }

    .module-icon {
        font-size: 32px;
        margin-bottom: 12px;
        color: var(--teal);
    }
</style>
@endsection
