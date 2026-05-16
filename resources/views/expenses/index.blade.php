@extends('layouts.app')

@section('title', 'Expenses')

@section('content')
<div class="hero-card">
    <h1 class="hero-title">Expenses</h1>
    <p class="hero-copy">Track cash outflow across categories with clear visibility into expense totals and status.</p>
</div>

<div class="metric-grid">
    <div class="metric-card rose">
        <div class="metric-label">Total Expenses</div>
        <div class="metric-value">${{ number_format($total ?? 0, 2) }}</div>
        <div class="metric-meta">All recorded expense transactions</div>
    </div>
</div>

<div class="card">
    <h3 class="section-title">Search &amp; Filter</h3>
    <form method="GET" action="/expenses" class="form-grid three" style="margin-top:12px;">
        <div>
            <input type="text" name="search" value="{{ $search ?? '' }}" placeholder="Search description…">
        </div>
        <div>
            <select name="category">
                <option value="">All Categories</option>
                @foreach($categories as $cat)
                    <option value="{{ $cat }}" {{ ($selectedCategory ?? '') === $cat ? 'selected' : '' }}>{{ $cat }}</option>
                @endforeach
            </select>
        </div>
        <div style="display:flex;gap:8px;align-items:flex-end;">
            <button type="submit" class="btn btn-primary btn-sm">Apply</button>
            <a href="/expenses" class="btn btn-ghost btn-sm">Reset</a>
        </div>
    </form>
</div>

<div class="card">
    <h3 class="section-title">Add New Expense</h3>
    <form method="POST" action="/expenses">
        <input type="hidden" name="_token" value="{{ \App\Middleware\CsrfMiddleware::token() }}">
        <div class="form-grid two" style="margin-top:18px;">
            <div>
                <label for="date">Date</label>
                <input type="date" id="date" name="date" required>
            </div>
            <div>
                <label for="category">Category</label>
                <select id="category" name="category" required>
                    <option value="">Select Category</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat }}">{{ $cat }}</option>
                    @endforeach
                </select>
            </div>
            <div class="span-full">
                <label for="description">Description</label>
                <input type="text" id="description" name="description" placeholder="Expense description" required>
            </div>
            <div>
                <label for="amount">Amount</label>
                <input type="number" id="amount" name="amount" step="0.01" placeholder="0.00" required>
            </div>
            <div style="display:flex; align-items:flex-end;">
                <button type="submit" class="btn btn-primary">Add Expense</button>
            </div>
        </div>
    </form>
</div>

<div class="card">
    <h3 class="section-title">Expense List</h3>
    @if($expenses->count())
        <div class="table-wrap" style="margin-top:18px;">
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Category</th>
                        <th>Description</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($expenses as $expense)
                        @php
                            $expenseId = $expense['expense_id'] ?? $expense['id'] ?? null;
                        @endphp
                        <tr>
                            <td>{{ $expense['date'] ?? '-' }}</td>
                            <td>{{ $expense['category'] ?? '-' }}</td>
                            <td>{{ $expense['description'] ?? '-' }}</td>
                            <td>${{ number_format($expense['amount'] ?? 0, 2) }}</td>
                            <td><span class="badge amber">{{ $expense['status'] ?? 'recorded' }}</span></td>
                            <td>
                                @if($expenseId)
                                    <a href="/expenses/{{ $expenseId }}/edit" class="btn btn-sm btn-ghost">Edit</a>
                                    <form method="POST" action="/expenses/{{ $expenseId }}" style="display:inline;" onsubmit="return confirm('Delete this expense?')">
                                        <input type="hidden" name="_token" value="{{ \App\Middleware\CsrfMiddleware::token() }}">
                                        <input type="hidden" name="_method" value="DELETE">
                                        <button type="submit" class="btn btn-sm btn-ghost" style="color:var(--rose);">Delete</button>
                                    </form>
                                @else
                                    <span class="muted">N/A</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div style="margin-top:16px;">{{ $expenses->links() }}</div>
    @else
        <div class="empty-state" style="margin-top:18px;">No expenses recorded yet.</div>
    @endif
</div>
@endsection
