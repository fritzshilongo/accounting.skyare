@extends('layouts.app')

@section('title', 'Database Backups')

@section('content')
<section class="hero-card">
    <div class="toolbar">
        <div>
            <h1 class="hero-title">Database Backups</h1>
            <p class="hero-copy">Create, restore, download, and manage point-in-time database snapshots. Backups are stored securely on the server and are never web-accessible.</p>
        </div>
        <div class="inline-actions">
            <a href="/settings" class="btn btn-ghost">Back to Settings</a>
            <form method="POST" action="/settings/backups" style="display:inline;">
                <input type="hidden" name="_token" value="{{ \App\Middleware\CsrfMiddleware::token() }}">
                <button type="submit" class="btn-primary" onclick="return confirm('Create a new database backup now?')">
                    <i class="fa fa-cloud-arrow-up" style="margin-right:6px;"></i>Push to Backup
                </button>
            </form>
        </div>
    </div>
</section>

@if(session('success'))
    <div class="alert-success" style="margin-bottom:16px; padding:14px 18px; background:#d1fae5; border:1px solid #6ee7b7; border-radius:10px; color:#065f46; font-size:14px;">
        <i class="fa fa-circle-check" style="margin-right:8px;"></i>{{ session('success') }}
    </div>
@endif

@if($errors->any())
    <div class="alert-error" style="margin-bottom:16px; padding:14px 18px; background:#fee2e2; border:1px solid #fca5a5; border-radius:10px; color:#991b1b; font-size:14px;">
        <i class="fa fa-circle-exclamation" style="margin-right:8px;"></i>{{ $errors->first() }}
    </div>
@endif

<section class="table-card">
    <div class="toolbar" style="margin-bottom:16px;">
        <h2 class="section-title" style="margin:0;">Saved Backups</h2>
        @if(!empty($backups))
            <form method="POST" action="/settings/backups/destroy-all">
                <input type="hidden" name="_token" value="{{ \App\Middleware\CsrfMiddleware::token() }}">
                <input type="hidden" name="_method" value="DELETE">
                <button type="submit" class="btn btn-sm btn-ghost" style="color:#dc2626;"
                    onclick="return confirm('Delete ALL backups? This cannot be undone.')">
                    <i class="fa fa-trash" style="margin-right:5px;"></i>Delete All Backups
                </button>
            </form>
        @endif
    </div>

    @if(!empty($backups))
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Filename</th>
                        <th>Size</th>
                        <th>Created</th>
                        <th style="text-align:right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($backups as $i => $backup)
                        <tr>
                            <td>
                                <div class="row-title" style="font-family:monospace;font-size:13px;">{{ $backup['filename'] }}</div>
                                @if($i === 0)
                                    <span class="badge teal" style="font-size:10px;">Latest</span>
                                @endif
                            </td>
                            <td>{{ $backup['size'] }}</td>
                            <td>{{ $backup['created_at'] }}</td>
                            <td>
                                <div class="inline-actions" style="justify-content:flex-end;">
                                    {{-- Download --}}
                                    <a href="/settings/backups/download/{{ urlencode($backup['filename']) }}"
                                       class="btn btn-sm btn-ghost" title="Download backup">
                                        <i class="fa fa-download"></i> Download
                                    </a>

                                    {{-- Restore --}}
                                    <form method="POST" action="/settings/backups/restore" style="display:inline;">
                                        <input type="hidden" name="_token" value="{{ \App\Middleware\CsrfMiddleware::token() }}">
                                        <input type="hidden" name="filename" value="{{ $backup['filename'] }}">
                                        <button type="submit" class="btn btn-sm btn-secondary"
                                            onclick="return confirm('Restore database from {{ $backup['filename'] }}?\n\nThis will OVERWRITE all current data. Make sure you have a recent backup first.')">
                                            <i class="fa fa-rotate-left"></i> Restore
                                        </button>
                                    </form>

                                    {{-- Delete --}}
                                    <form method="POST" action="/settings/backups/delete" style="display:inline;">
                                        <input type="hidden" name="_token" value="{{ \App\Middleware\CsrfMiddleware::token() }}">
                                        <input type="hidden" name="filename" value="{{ $backup['filename'] }}">
                                        <button type="submit" class="btn btn-sm btn-ghost" style="color:#dc2626;"
                                            onclick="return confirm('Delete this backup permanently?')">
                                            <i class="fa fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <p style="font-size:12px; color:var(--ink-muted); margin-top:12px;">
            <i class="fa fa-circle-info" style="margin-right:5px;"></i>
            Maximum {{ count($backups) }} of 20 backups stored. The server automatically creates a daily backup at midnight.
            Oldest backups are pruned automatically once the limit is reached.
        </p>
    @else
        <div class="empty-state">
            No backups found. Click <strong>Push to Backup</strong> above to create your first snapshot.
        </div>
    @endif
</section>

<section class="form-card" style="margin-top:24px;">
    <h2 class="section-title">Upload Backup File</h2>
    <p style="color:var(--ink-muted); font-size:14px; margin:8px 0 16px;">
        Upload a <code>.sql</code> dump file (e.g. from a previous export or another server). Maximum 100 MB.
    </p>
    <form method="POST" action="/settings/backups/upload" enctype="multipart/form-data">
        <input type="hidden" name="_token" value="{{ \App\Middleware\CsrfMiddleware::token() }}">
        <div style="display:flex; flex-wrap:wrap; gap:12px; align-items:flex-end;">
            <div style="flex:1; min-width:220px;">
                <label style="display:block; font-size:13px; font-weight:600; margin-bottom:6px;">SQL File</label>
                <input type="file" name="backup_file" accept=".sql" required
                    style="width:100%; padding:8px 12px; border:1px solid var(--border); border-radius:8px; font-size:13px; background:var(--surface);">
            </div>
            <div style="display:flex; align-items:center; gap:8px; padding-bottom:4px;">
                <input type="checkbox" name="restore_immediately" id="restore_immediately" value="1"
                    style="width:16px; height:16px; cursor:pointer;">
                <label for="restore_immediately" style="font-size:13px; cursor:pointer;">
                    Restore immediately after upload
                </label>
            </div>
            <div style="padding-bottom:4px;">
                <button type="submit" class="btn-primary"
                    onclick="return confirm(document.getElementById('restore_immediately').checked ? 'Upload and RESTORE this backup? This will overwrite all current data.' : 'Upload this backup file?')">
                    <i class="fa fa-upload" style="margin-right:6px;"></i>Upload Backup
                </button>
            </div>
        </div>
    </form>
</section>

<section class="form-card" style="margin-top:24px;">
    <h2 class="section-title">Automatic Scheduled Backups</h2>
    <p style="color:var(--ink-muted); font-size:14px; margin:8px 0 16px;">
        The system runs <code>db:backup</code> automatically every day at midnight via the server cron.
        To enable scheduled backups on your server, add this line to your crontab:
    </p>
    <pre style="background:rgba(24,49,83,0.05); padding:14px 18px; border-radius:10px; font-size:13px; overflow-x:auto; border:1px solid rgba(24,49,83,0.1);">* * * * * cd /path/to/skyare-laravel && php artisan schedule:run >> /dev/null 2>&1</pre>
    <p style="color:var(--ink-muted); font-size:13px; margin-top:10px;">
        Replace <code>/path/to/skyare-laravel</code> with your actual server path (e.g. <code>/home/sumbkqqz/skyare-laravel</code>).
    </p>
</section>
@endsection
