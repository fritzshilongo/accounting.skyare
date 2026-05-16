@extends('layouts.app')

@section('title', 'Setup')

@section('content')
<div class="hero-card">
    <h1 class="hero-title">System Setup</h1>
    <p class="hero-copy">Validate infrastructure readiness and key database dependencies before go-live operations.</p>
</div>

<div class="card">
    <div class="toolbar-row">
        <h3 class="section-title">Health Check</h3>
        <div style="display:flex;gap:8px;">
            <button id="healthCheckBtn" onclick="runHealthCheck()" class="btn btn-primary btn-sm" type="button">Run Health Check</button>
            <button id="testEmailBtn" onclick="sendTestEmail()" class="btn btn-sm" type="button">Send Test Email</button>
        </div>
    </div>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <div id="healthCheckResult" class="panel-grid" style="margin-top:18px;"></div>
</div>

<script>
function runHealthCheck() {
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    fetch('/setup/health-check', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': token,
            'Accept': 'application/json',
        },
    })
        .then(r => {
            if (!r.ok) {
                throw new Error('Health check request failed with HTTP ' + r.status);
            }
            return r.json();
        })
        .then(data => {
            let html = '<div class="card"><h3 class="section-title">Check Results</h3>';
            html += '<p class="section-copy">Database: <strong>' + (data.database ? 'Healthy' : 'Unavailable') + '</strong></p>';
            if (data.tables) {
                html += '<p class="section-copy">Found Tables: ' + data.tables.found.join(', ') + '</p>';
                if (data.tables.missing.length > 0) {
                    html += '<p><span class="badge rose">Missing</span> ' + data.tables.missing.join(', ') + '</p>';
                }
            }
            if (data.mail) {
                html += '<p class="section-copy">SMTP Reachability: <strong>' + (data.mail.reachable ? 'Reachable' : 'Unavailable') + '</strong></p>';
                html += '<p class="section-copy">SMTP Endpoint: ' + (data.mail.host || 'n/a') + ':' + (data.mail.port || 'n/a') + ' (' + (data.mail.encryption || 'none') + ')</p>';
                if (data.mail.error) {
                    html += '<p><span class="badge rose">Mail Error</span> ' + data.mail.error + '</p>';
                }
            }
            html += '</div>';
            document.getElementById('healthCheckResult').innerHTML = html;
        })
        .catch(err => {
            document.getElementById('healthCheckResult').innerHTML =
                '<div class="card"><h3 class="section-title">Check Results</h3><p><span class="badge rose">Request Failed</span> ' + err.message + '</p></div>';
        });
}

function sendTestEmail() {
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    fetch('/setup/test-email', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': token,
            'Accept': 'application/json',
        },
    })
        .then(async r => {
            const data = await r.json();
            if (!r.ok) {
                throw new Error(data.error || ('Test email request failed with HTTP ' + r.status));
            }
            return data;
        })
        .then(data => {
            document.getElementById('healthCheckResult').innerHTML =
                '<div class="card"><h3 class="section-title">Test Email</h3>' +
                '<p class="section-copy"><strong>Sent:</strong> ' + (data.sent ? 'Yes' : 'No') + '</p>' +
                '<p class="section-copy"><strong>Recipient:</strong> ' + (data.to || 'n/a') + '</p>' +
                '<p class="section-copy"><strong>Mailer:</strong> ' + (data.mailer || 'n/a') + '</p>' +
                '</div>';
        })
        .catch(err => {
            document.getElementById('healthCheckResult').innerHTML =
                '<div class="card"><h3 class="section-title">Test Email</h3><p><span class="badge rose">Failed</span> ' + err.message + '</p></div>';
        });
}
</script>
@endsection
