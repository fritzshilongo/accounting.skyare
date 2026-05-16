@extends('layouts.app')

@section('title', 'Dashboard - ' . ($company['company_name'] ?? config('app.name', 'Skyare Trading CC')))

@section('content')
<div class="hero-card">
    <h1 class="hero-title">Finance Dashboard</h1>
    <p class="hero-copy">Welcome to {{ $company['company_name'] ?? config('app.name', 'Skyare Trading CC') }}. Here is a live snapshot of invoices, revenue, and collection activity.</p>
</div>

<div class="metric-grid">
    <div class="metric-card navy">
        <div class="metric-label">Total Invoices</div>
        <div class="metric-value">{{ $stats['total_invoices'] ?? 0 }}</div>
        <div class="metric-meta">All issued invoices</div>
    </div>
    <div class="metric-card teal">
        <div class="metric-label">Revenue Received</div>
        <div class="metric-value">${{ number_format($stats['total_revenue'] ?? 0, 2) }}</div>
        <div class="metric-meta">Payments collected to date</div>
    </div>
    <div class="metric-card amber">
        <div class="metric-label">Outstanding Invoices</div>
        <div class="metric-value">{{ $stats['outstanding_invoices'] ?? 0 }}</div>
        <div class="metric-meta">Invoices with unpaid balance</div>
    </div>
    <div class="metric-card rose">
        <div class="metric-label">Outstanding Amount</div>
        <div class="metric-value">${{ number_format($stats['outstanding_amount'] ?? 0, 2) }}</div>
        <div class="metric-meta">Remaining balance after payments</div>
    </div>
    <div class="metric-card amber">
        <div class="metric-label">Overdue Invoices</div>
        <div class="metric-value">{{ $stats['overdue_invoices'] ?? 0 }}</div>
        <div class="metric-meta">Past due and not fully paid</div>
    </div>
    <div class="metric-card rose">
        <div class="metric-label">Overdue Amount</div>
        <div class="metric-value">${{ number_format($stats['overdue_amount'] ?? 0, 2) }}</div>
        <div class="metric-meta">Past due remaining balance</div>
    </div>
    <div class="metric-card navy">
        <div class="metric-label">Total Clients</div>
        <div class="metric-value">{{ $stats['total_clients'] ?? 0 }}</div>
        <div class="metric-meta">Active client records</div>
    </div>
    <div class="metric-card teal">
        <div class="metric-label">Products</div>
        <div class="metric-value">{{ $stats['total_products'] ?? 0 }}</div>
        <div class="metric-meta">Catalog inventory lines</div>
    </div>
</div>

{{-- Charts Row — collapsible with period toggle --}}
<div class="card" style="padding:0;overflow:hidden;">
    <div style="display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;padding:16px 20px;border-bottom:1px solid rgba(24,49,83,0.06);gap:10px;">
        <div style="display:flex;gap:8px;align-items:center;">
            <button class="dash-tab active" data-tab="revenue" onclick="dashTab(this)"><i class="fas fa-chart-area" style="margin-right:5px;"></i>Revenue vs Expenses</button>
            <button class="dash-tab" data-tab="status" onclick="dashTab(this)"><i class="fas fa-chart-pie" style="margin-right:5px;"></i>Invoice Status</button>
        </div>
        <div style="display:flex;gap:4px;" id="periodTabs">
            <button class="period-btn" data-months="3" onclick="setPeriod(3)">3M</button>
            <button class="period-btn" data-months="6" onclick="setPeriod(6)">6M</button>
            <button class="period-btn active" data-months="12" onclick="setPeriod(12)">1Y</button>
        </div>
    </div>
    <div style="padding:16px 20px;position:relative;height:260px;">
        <div id="panel-revenue" class="dash-panel active">
            <canvas id="revenueChart"></canvas>
        </div>
        <div id="panel-status" class="dash-panel" style="display:none;">
            <canvas id="statusChart"></canvas>
        </div>
    </div>
</div>

<style>
    .dash-tab {
        background: none !important; border: 0; padding: 8px 14px; font-size: 13px; font-weight: 600;
        color: var(--muted); border-radius: 8px; cursor: pointer; transition: all 0.15s;
    }
    .dash-tab:hover { background: rgba(24,49,83,0.04) !important; color: var(--ink); transform: none !important; box-shadow: none !important; }
    .dash-tab.active { background: var(--teal) !important; color: #fff !important; }
    .period-btn {
        background: none !important; border: 1px solid var(--line, rgba(24,49,83,0.08)) !important;
        padding: 5px 12px; font-size: 12px; font-weight: 700; border-radius: 6px; cursor: pointer;
        color: var(--muted); transition: all 0.15s;
    }
    .period-btn:hover { border-color: var(--teal) !important; color: var(--teal); transform: none !important; box-shadow: none !important; }
    .period-btn.active { background: var(--navy, #17324d) !important; color: #fff !important; border-color: var(--navy, #17324d) !important; }
    .dash-panel { position: absolute; inset: 16px 20px; }
    .dash-panel canvas { width: 100% !important; height: 100% !important; }
</style>

{{-- Top Clients --}}
@if(!empty($stats['top_clients']))
<div class="card">
    <h3 class="section-title" style="margin-bottom:18px;"><i class="fas fa-trophy" style="color:var(--amber);margin-right:8px;"></i>Top Clients by Revenue</h3>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Client</th>
                    <th>Revenue</th>
                    <th>Invoices</th>
                </tr>
            </thead>
            <tbody>
                @foreach($stats['top_clients'] as $i => $tc)
                    <tr>
                        <td><span class="badge {{ $i === 0 ? 'amber' : 'navy' }}">{{ $i + 1 }}</span></td>
                        <td><div class="row-title">{{ $tc['client_name'] ?? 'Unknown' }}</div></td>
                        <td style="font-weight:700;">${{ number_format($tc['total_revenue'] ?? 0, 2) }}</td>
                        <td>{{ $tc['invoice_count'] ?? 0 }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

<div class="card">
    <h3 class="section-title">Recent Invoices</h3>
    @if(!empty($stats['recent_invoices']))
        <div class="table-wrap" style="margin-top:18px;">
            <table>
                <thead>
                    <tr>
                        <th>Invoice #</th>
                        <th>Client</th>
                        <th>Date</th>
                        <th>Amount</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($stats['recent_invoices'] as $invoice)
                        <tr>
                            <td><a href="/invoices/{{ $invoice['invoice_id'] }}" class="row-title">{{ $invoice['invoice_no'] ?? '-' }}</a></td>
                            <td>{{ $invoice['client_name'] ?? '-' }}</td>
                            <td>{{ $invoice['issue_date'] ?? $invoice['created_at'] ?? '-' }}</td>
                            <td>${{ number_format((float) ($invoice['total'] ?? 0), 2) }}</td>
                            <td>
                                @php
                                    $st = strtolower($invoice['status'] ?? 'unpaid');
                                    $st = $st === 'partial' ? 'partial_paid' : ($st === 'finalized' ? 'finalised' : $st);
                                    $badgeClass = match($st) {
                                        'paid', 'finalised' => 'teal',
                                        'partial_paid' => 'amber',
                                        'overdue' => 'rose',
                                        'accepted', 'sent', 'viewed' => 'navy',
                                        default => '',
                                    };
                                @endphp
                                <span class="badge {{ $badgeClass }}">{{ ucwords(str_replace('_', ' ', $st)) }}</span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="empty-state" style="margin-top:18px;">No invoices yet.</div>
    @endif
</div>

<div class="card">
    <h3 class="section-title">Invoice Reminders</h3>
    @if(!empty($stats['invoice_reminders']))
        <div class="table-wrap" style="margin-top:18px;">
            <table>
                <thead>
                    <tr>
                        <th>Invoice</th>
                        <th>Client</th>
                        <th>Due Date</th>
                        <th>Status</th>
                        <th>Total</th>
                        <th>Paid</th>
                        <th>Remaining</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($stats['invoice_reminders'] as $r)
                        @php
                            $reminderType = strtolower((string) ($r['reminder_type'] ?? 'due_soon'));
                            $reminderBadge = $reminderType === 'overdue' ? 'rose' : ($reminderType === 'due_today' ? 'amber' : 'navy');
                            $statusValue = strtolower((string) ($r['status'] ?? 'draft'));
                            $statusValue = $statusValue === 'partial' ? 'partial_paid' : ($statusValue === 'finalized' ? 'finalised' : $statusValue);
                        @endphp
                        <tr>
                            <td><a href="/invoices/{{ $r['invoice_id'] }}" class="row-title">{{ $r['invoice_no'] ?? ('#' . $r['invoice_id']) }}</a></td>
                            <td>{{ $r['client_name'] ?? 'Unknown client' }}</td>
                            <td>{{ $r['due_date'] ?? '-' }}</td>
                            <td><span class="badge {{ $reminderBadge }}">{{ ucwords(str_replace('_', ' ', $reminderType)) }}</span></td>
                            <td>${{ number_format((float) ($r['total'] ?? 0), 2) }}</td>
                            <td>${{ number_format((float) ($r['paid'] ?? 0), 2) }}</td>
                            <td style="font-weight:700;color:var(--rose);">${{ number_format((float) ($r['balance'] ?? 0), 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="empty-state" style="margin-top:18px;">No due reminders right now. Fully paid/finalised invoices are automatically excluded.</div>
    @endif
</div>

<div class="card">
    <h3 class="section-title">Recent Payments</h3>
    @if(!empty($stats['recent_payments']))
        <div class="table-wrap" style="margin-top:18px;">
            <table>
                <thead>
                    <tr>
                        <th>Payment #</th>
                        <th>Invoice</th>
                        <th>Date</th>
                        <th>Amount</th>
                        <th>Method</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($stats['recent_payments'] as $payment)
                        <tr>
                            <td>{{ $payment['payment_id'] ?? '-' }}</td>
                            <td><a href="/invoices/{{ $payment['invoice_id'] ?? '' }}" class="row-title">{{ $payment['invoice_no'] ?? '-' }}</a></td>
                            <td>{{ $payment['payment_date'] ?? '-' }}</td>
                            <td style="font-weight:600; color:var(--teal);">${{ number_format((float) ($payment['amount'] ?? 0), 2) }}</td>
                            <td><span class="badge navy">{{ ucfirst($payment['method'] ?? 'Unknown') }}</span></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="empty-state" style="margin-top:18px;">No payments yet.</div>
    @endif
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
<script>
// Tab switching
function dashTab(btn) {
    document.querySelectorAll('.dash-tab').forEach(t => t.classList.remove('active'));
    btn.classList.add('active');
    var tab = btn.getAttribute('data-tab');
    document.querySelectorAll('.dash-panel').forEach(p => p.style.display = 'none');
    var panel = document.getElementById('panel-' + tab);
    if (panel) panel.style.display = 'block';
    // Show/hide period tabs (only relevant for revenue)
    document.getElementById('periodTabs').style.visibility = tab === 'revenue' ? 'visible' : 'hidden';
}

// Revenue chart data (full 12 months)
@php
    $monthlyRevenue = $stats['monthly_revenue'] ?? [];
    $monthlyExpenses = $stats['monthly_expenses'] ?? [];
    $months = [];
    for ($i = 11; $i >= 0; $i--) {
        $months[] = date('Y-m', strtotime("-{$i} months"));
    }
    $revMap = [];
    foreach ($monthlyRevenue as $r) { $revMap[$r['month']] = (float)$r['total']; }
    $expMap = [];
    foreach ($monthlyExpenses as $e) { $expMap[$e['month']] = (float)$e['total']; }
    $allLabels = array_map(fn($m) => date('M \'y', strtotime($m . '-01')), $months);
    $allRevData = array_map(fn($m) => $revMap[$m] ?? 0, $months);
    $allExpData = array_map(fn($m) => $expMap[$m] ?? 0, $months);
@endphp

var fullLabels = {!! json_encode($allLabels) !!};
var fullRevData = {!! json_encode($allRevData) !!};
var fullExpData = {!! json_encode($allExpData) !!};
var revenueChartInstance = null;

function buildRevenueChart(months) {
    var offset = fullLabels.length - months;
    var labels = fullLabels.slice(offset);
    var rev = fullRevData.slice(offset);
    var exp = fullExpData.slice(offset);

    if (revenueChartInstance) revenueChartInstance.destroy();

    var ctx = document.getElementById('revenueChart');
    if (!ctx) return;
    revenueChartInstance = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Revenue',
                    data: rev,
                    backgroundColor: 'rgba(18,128,122,0.7)',
                    borderRadius: 4,
                    barPercentage: 0.6,
                },
                {
                    label: 'Expenses',
                    data: exp,
                    backgroundColor: 'rgba(223,111,95,0.6)',
                    borderRadius: 4,
                    barPercentage: 0.6,
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, padding: 14, font: { size: 12 } } } },
            scales: {
                x: { grid: { display: false } },
                y: { beginAtZero: true, ticks: { callback: function(v) { return '$' + v.toLocaleString(); }, font: { size: 11 } } }
            }
        }
    });
}

function setPeriod(m) {
    document.querySelectorAll('.period-btn').forEach(b => b.classList.remove('active'));
    document.querySelector('.period-btn[data-months="' + m + '"]').classList.add('active');
    buildRevenueChart(m);
}

document.addEventListener('DOMContentLoaded', function() {
    buildRevenueChart(12);

    // --- Invoice Status Doughnut ---
    @php
        $statusCounts = $stats['invoice_status_counts'] ?? [];
        $statusLabels = array_map(fn($s) => ucfirst($s['status'] ?? 'Unknown'), $statusCounts);
        $statusData = array_map(fn($s) => (int)($s['count'] ?? 0), $statusCounts);
        $statusColors = [];
        foreach ($statusCounts as $s) {
            $st = strtolower($s['status'] ?? '');
            if ($st === 'paid') $statusColors[] = '#12807a';
            elseif ($st === 'finalised') $statusColors[] = '#2aa35a';
            elseif ($st === 'partial_paid') $statusColors[] = '#d79a1e';
            elseif ($st === 'accepted' || $st === 'pending' || $st === 'sent') $statusColors[] = '#17324d';
            elseif ($st === 'overdue') $statusColors[] = '#df6f5f';
            else $statusColors[] = '#17324d';
        }
    @endphp

    var statusCtx = document.getElementById('statusChart');
    if (statusCtx) {
        new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: {!! json_encode($statusLabels) !!},
                datasets: [{
                    data: {!! json_encode($statusData) !!},
                    backgroundColor: {!! json_encode($statusColors) !!},
                    borderWidth: 2,
                    borderColor: '#fff',
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom', labels: { boxWidth: 12, padding: 14, font: { size: 12 } } }
                }
            }
        });
    }
});
</script>
@endsection
