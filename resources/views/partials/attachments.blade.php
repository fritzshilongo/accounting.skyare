{{-- Inline attachment widget for entity detail pages --}}
{{-- Usage: @include('partials.attachments', ['attachableType' => 'invoice', 'attachableId' => $invoice['invoice_id'], 'attachments' => $attachments ?? []]) --}}
@php
    $attachableType = $attachableType ?? 'invoice';
    $attachableId = $attachableId ?? 0;
    $attachments = $attachments ?? [];
@endphp

<div class="card" style="margin-top:20px;">
    <div class="toolbar" style="margin-bottom:14px;">
        <h3 class="section-title"><i class="fas fa-paperclip" style="color:var(--teal);margin-right:8px;"></i>Attachments</h3>
    </div>

    {{-- Upload --}}
    <form method="POST" action="/attachments" enctype="multipart/form-data" style="display:flex;gap:12px;align-items:end;margin-bottom:18px;">
        <input type="hidden" name="_token" value="{{ \App\Middleware\CsrfMiddleware::token() }}">
        <input type="hidden" name="attachable_type" value="{{ $attachableType }}">
        <input type="hidden" name="attachable_id" value="{{ $attachableId }}">
        <div style="flex:1;">
            <input type="file" name="file" required style="padding:10px;">
        </div>
        <button type="submit" class="btn btn-ghost btn-sm"><i class="fas fa-upload" style="margin-right:4px;"></i>Attach</button>
    </form>

    {{-- List --}}
    @if(count($attachments) > 0)
        <div style="display:grid;gap:8px;">
            @foreach($attachments as $att)
                <div style="display:flex;align-items:center;gap:12px;padding:10px 14px;border-radius:12px;background:rgba(255,255,255,0.5);border:1px solid rgba(24,49,83,0.06);">
                    <i class="fas fa-file" style="color:var(--teal);"></i>
                    <div style="flex:1;">
                        <div style="font-weight:700;font-size:14px;">{{ $att['original_name'] ?? 'File' }}</div>
                        <div style="color:var(--muted);font-size:12px;">
                            @php
                                $bytes = (int)($att['size_bytes'] ?? 0);
                                if ($bytes >= 1048576) echo number_format($bytes / 1048576, 1) . ' MB';
                                elseif ($bytes >= 1024) echo number_format($bytes / 1024, 1) . ' KB';
                                else echo $bytes . ' B';
                            @endphp
                            · {{ isset($att['created_at']) ? date('M j, Y', strtotime($att['created_at'])) : '' }}
                        </div>
                    </div>
                    <a href="/attachments/{{ $att['attachment_id'] }}/download" class="btn btn-ghost btn-sm" title="Download"><i class="fas fa-download"></i></a>
                    <form method="POST" action="/attachments/{{ $att['attachment_id'] }}" style="display:inline;" onsubmit="return confirm('Delete?')">
                        <input type="hidden" name="_token" value="{{ \App\Middleware\CsrfMiddleware::token() }}">
                        <input type="hidden" name="_method" value="DELETE">
                        <button type="submit" class="btn btn-danger btn-sm" style="padding:8px 10px;" title="Delete"><i class="fas fa-times"></i></button>
                    </form>
                </div>
            @endforeach
        </div>
    @else
        <div style="color:var(--muted);font-size:14px;text-align:center;padding:12px;">No files attached yet.</div>
    @endif
</div>
