@extends('layouts.app')

@section('title', 'Edit Journal Entry')

@section('content')
<div class="hero-card">
    <h1 class="hero-title">Edit Journal Entry</h1>
    <p class="hero-copy">Update the entry details. Debit and credit must balance.</p>
</div>

<div class="card">
    @php
        $entryId = $entry['entry_id'] ?? $entry['id'] ?? null;
    @endphp
    <form method="POST" action="/journal-entries/{{ $entryId }}">
        <input type="hidden" name="_token" value="{{ \App\Middleware\CsrfMiddleware::token() }}">
        <input type="hidden" name="_method" value="PUT">
        <div class="form-grid two" style="margin-top:18px;">
            <div>
                <label for="date">Date</label>
                <input type="date" id="date" name="date" value="{{ $entry['date'] ?? '' }}" required>
            </div>
            <div>
                <label for="reference">Reference</label>
                <input type="text" id="reference" name="reference" value="{{ $entry['reference'] ?? '' }}" required>
            </div>
            <div class="span-full">
                <label for="description">Description</label>
                <textarea id="description" name="description" rows="3" required>{{ $entry['description'] ?? '' }}</textarea>
            </div>
            <div>
                <label for="debit_amount">Debit Amount</label>
                <input type="number" id="debit_amount" name="debit_amount" step="0.01" value="{{ $entry['debit_amount'] ?? '' }}" required>
            </div>
            <div>
                <label for="credit_amount">Credit Amount</label>
                <input type="number" id="credit_amount" name="credit_amount" step="0.01" value="{{ $entry['credit_amount'] ?? '' }}" required>
            </div>
            <div class="span-full" style="display:flex;gap:10px;">
                <button type="submit" class="btn btn-primary">Update Entry</button>
                <a href="/journal-entries" class="btn btn-secondary">Cancel</a>
            </div>
        </div>
    </form>
</div>
@endsection
