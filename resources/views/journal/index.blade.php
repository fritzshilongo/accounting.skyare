@extends('layouts.app')

@section('title', 'Journal Entries')

@section('content')
<div class="hero-card">
    <h1 class="hero-title">Journal Entries</h1>
    <p class="hero-copy">Capture balanced debit and credit postings for accurate bookkeeping and period close.</p>
</div>

<div class="card">
    <h3 class="section-title">New Entry</h3>
    <form method="POST" action="/journal-entries">
        <input type="hidden" name="_token" value="{{ \App\Middleware\CsrfMiddleware::token() }}">
        <div class="form-grid two" style="margin-top:18px;">
            <div>
                <label for="date">Date</label>
                <input type="date" id="date" name="date" required>
            </div>
            <div>
                <label for="reference">Reference</label>
                <input type="text" id="reference" name="reference" placeholder="Reference number" required>
            </div>
            <div class="span-full">
                <label for="description">Description</label>
                <textarea id="description" name="description" rows="3" placeholder="Entry description" required></textarea>
            </div>
            <div>
                <label for="debit_amount">Debit Amount</label>
                <input type="number" id="debit_amount" name="debit_amount" step="0.01" placeholder="0.00">
            </div>
            <div>
                <label for="credit_amount">Credit Amount</label>
                <input type="number" id="credit_amount" name="credit_amount" step="0.01" placeholder="0.00">
            </div>
            <div class="span-full">
                <button type="submit" class="btn btn-primary">Record Entry</button>
            </div>
        </div>
    </form>
</div>

<div class="card">
    <h3 class="section-title">Entries</h3>
    <form method="GET" action="/journal-entries" class="filter-bar" style="margin:18px 0;">
        <div>
            <label for="search">Search</label>
            <input id="search" name="search" value="{{ $search ?? '' }}" placeholder="Reference or description">
        </div>
        <div style="display:flex; gap:10px; align-items:end;">
            <button type="submit" class="btn-primary">Apply</button>
            <a href="/journal-entries" class="btn btn-ghost">Reset</a>
        </div>
    </form>
    @if($entries->count())
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Reference</th>
                        <th>Description</th>
                        <th>Debit</th>
                        <th>Credit</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($entries as $entry)
                        @php
                            $entryId = $entry['entry_id'] ?? $entry['id'] ?? null;
                        @endphp
                        <tr>
                            <td>{{ $entry['date'] ?? '-' }}</td>
                            <td>{{ $entry['reference'] ?? '-' }}</td>
                            <td>{{ $entry['description'] ?? '-' }}</td>
                            <td>${{ number_format($entry['debit_amount'] ?? 0, 2) }}</td>
                            <td>${{ number_format($entry['credit_amount'] ?? 0, 2) }}</td>
                            <td>
                                <div style="display:flex;gap:6px;">
                                    @if($entryId)
                                        <a href="/journal-entries/{{ $entryId }}/edit" class="btn btn-sm btn-ghost">Edit</a>
                                        <form method="POST" action="/journal-entries/{{ $entryId }}" style="display:inline;" onsubmit="return confirm('Delete this journal entry?');">
                                            <input type="hidden" name="_token" value="{{ \App\Middleware\CsrfMiddleware::token() }}">
                                            <input type="hidden" name="_method" value="DELETE">
                                            <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                        </form>
                                    @else
                                        <span class="muted">N/A</span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="pagination-wrap">{{ $entries->links() }}</div>
    @else
        <div class="empty-state" style="margin-top:18px;">No entries found.</div>
    @endif
</div>
@endsection
