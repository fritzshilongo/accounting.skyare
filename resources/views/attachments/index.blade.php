@extends('layouts.app')

@section('title', 'Attachments - ' . ($company['company_name'] ?? 'Skyare'))

@section('content')
<div class="hero-card">
    <div class="toolbar">
        <div>
            <h1 class="hero-title">File Attachments</h1>
            <p class="hero-copy">View and manage files attached to invoices, estimates, expenses, and clients.</p>
        </div>
    </div>
</div>

{{-- Filter Bar --}}
<div class="card" style="padding:18px;">
    <form method="GET" action="/attachments" class="filter-bar" style="grid-template-columns: 1fr 1fr auto;">
        <div>
            <label for="type">Entity Type</label>
            <select id="type" name="type">
                <option value="">All Types</option>
                <option value="invoice" {{ ($filterType ?? '') === 'invoice' ? 'selected' : '' }}>Invoices</option>
                <option value="estimate" {{ ($filterType ?? '') === 'estimate' ? 'selected' : '' }}>Estimates</option>
                <option value="expense" {{ ($filterType ?? '') === 'expense' ? 'selected' : '' }}>Expenses</option>
                <option value="client" {{ ($filterType ?? '') === 'client' ? 'selected' : '' }}>Clients</option>
                <option value="recurring" {{ ($filterType ?? '') === 'recurring' ? 'selected' : '' }}>Recurring Invoices</option>
            </select>
        </div>
        <div>
            <label for="entity_id">Entity ID (optional)</label>
            <input type="number" id="entity_id" name="entity_id" value="{{ $filterEntityId ?? '' }}" placeholder="e.g. 42">
        </div>
        <div style="display:flex;align-items:flex-end;">
            <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-filter" style="margin-right:6px;"></i>Filter</button>
        </div>
    </form>
</div>

{{-- Upload Form --}}
<div class="form-card">
    <h3 class="section-title" style="margin-bottom:18px;"><i class="fas fa-cloud-upload-alt" style="color:var(--teal);margin-right:8px;"></i>Upload Attachment</h3>
    <form method="POST" action="/attachments" enctype="multipart/form-data">
        <input type="hidden" name="_token" value="{{ \App\Middleware\CsrfMiddleware::token() }}">
        <div class="form-grid three" style="align-items:end;">
            <div>
                <label for="attachable_type">Attach To</label>
                <select id="attachable_type" name="attachable_type" required>
                    <option value="invoice">Invoice</option>
                    <option value="estimate">Estimate</option>
                    <option value="expense">Expense</option>
                    <option value="client">Client</option>
                    <option value="recurring">Recurring Invoice</option>
                </select>
            </div>
            <div>
                <label for="attachable_id">Entity ID</label>
                <input type="number" id="attachable_id" name="attachable_id" required placeholder="e.g. 42" min="1">
            </div>
            <div>
                <label for="file">File (max 10MB)</label>
                <input type="file" id="file" name="file" required style="padding:12px;">
            </div>
        </div>
        <div style="margin-top:16px;">
            <button type="submit" class="btn btn-primary"><i class="fas fa-upload" style="margin-right:6px;"></i>Upload</button>
        </div>
    </form>
</div>

{{-- Attachments List --}}
<div class="card">
    <h3 class="section-title" style="margin-bottom:18px;">All Attachments</h3>
    @if(count($attachments ?? []) > 0)
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>File</th>
                        <th>Attached To</th>
                        <th>Size</th>
                        <th>Type</th>
                        <th>Uploaded</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($attachments as $att)
                        <tr>
                            <td>
                                <div class="row-title"><i class="fas fa-paperclip" style="color:var(--teal);margin-right:6px;"></i>{{ $att['original_name'] ?? 'Unknown' }}</div>
                            </td>
                            <td>
                                <span class="badge navy">{{ ucfirst($att['attachable_type'] ?? '-') }}</span>
                                #{{ $att['attachable_id'] ?? '-' }}
                            </td>
                            <td>
                                @php
                                    $bytes = (int)($att['size_bytes'] ?? 0);
                                    if ($bytes >= 1048576) $sizeStr = number_format($bytes / 1048576, 1) . ' MB';
                                    elseif ($bytes >= 1024) $sizeStr = number_format($bytes / 1024, 1) . ' KB';
                                    else $sizeStr = $bytes . ' B';
                                @endphp
                                {{ $sizeStr }}
                            </td>
                            <td><span style="color:var(--muted);font-size:13px;">{{ $att['mime_type'] ?? '-' }}</span></td>
                            <td>{{ isset($att['created_at']) ? date('M j, Y', strtotime($att['created_at'])) : '-' }}</td>
                            <td>
                                <div class="inline-actions">
                                    <a href="/attachments/{{ $att['attachment_id'] }}/download" class="btn btn-ghost btn-sm"><i class="fas fa-download"></i></a>
                                    <form method="POST" action="/attachments/{{ $att['attachment_id'] }}" style="display:inline;" onsubmit="return confirm('Delete this attachment?')">
                                        <input type="hidden" name="_token" value="{{ \App\Middleware\CsrfMiddleware::token() }}">
                                        <input type="hidden" name="_method" value="DELETE">
                                        <button type="submit" class="btn btn-danger btn-sm"><i class="fas fa-trash"></i></button>
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
            <i class="fas fa-paperclip" style="font-size:32px;color:var(--muted);margin-bottom:12px;display:block;"></i>
            No attachments found. Upload files using the form above.
        </div>
    @endif
</div>
@endsection
