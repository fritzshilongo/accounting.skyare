@extends('layouts.app')

@section('title', 'Edit Expense')

@section('content')
<div class="hero-card">
    <h1 class="hero-title">Edit Expense</h1>
    <p class="hero-copy">Update the expense details below.</p>
</div>

<div class="card">
    @php
        $expenseId = $expense['expense_id'] ?? $expense['id'] ?? null;
    @endphp
    <form method="POST" action="/expenses/{{ $expenseId }}">
        <input type="hidden" name="_token" value="{{ \App\Middleware\CsrfMiddleware::token() }}">
        <input type="hidden" name="_method" value="PUT">
        <div class="form-grid two" style="margin-top:18px;">
            <div>
                <label for="date">Date</label>
                <input type="date" id="date" name="date" value="{{ $expense['date'] ?? '' }}" required>
            </div>
            <div>
                <label for="category">Category</label>
                <select id="category" name="category" required>
                    <option value="">Select Category</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat }}" {{ ($expense['category'] ?? '') === $cat ? 'selected' : '' }}>{{ $cat }}</option>
                    @endforeach
                </select>
            </div>
            <div class="span-full">
                <label for="description">Description</label>
                <input type="text" id="description" name="description" value="{{ $expense['description'] ?? '' }}" required>
            </div>
            <div>
                <label for="amount">Amount</label>
                <input type="number" id="amount" name="amount" step="0.01" value="{{ $expense['amount'] ?? '' }}" required>
            </div>
            <div class="span-full" style="display:flex;gap:10px;">
                <button type="submit" class="btn btn-primary">Update Expense</button>
                <a href="/expenses" class="btn btn-secondary">Cancel</a>
            </div>
        </div>
    </form>
</div>
@endsection
