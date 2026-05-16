@extends('layouts.app')

@section('title', 'Revenue Report')

@section('content')
<style>
    .rev-summary {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 14px;
    }
    .rev-stat {
        padding: 16px;
        border-radius: 14px;
        position: relative;
        overflow: hidden;
    }
    .rev-stat::after {
        content: "";
        position: absolute;
        right: -16px;
        bottom: -16px;
        width: 64px;
        height: 64px;
        border-radius: 50%;
        background: rgba(255,255,255,0.3);
    }
    .rev-stat .label { font-size: 11px; text-transform: uppercase; letter-spacing: 0.08em; color: var(--muted); }
    .rev-stat .val { font-size: 22px; font-weight: 800; margin-top: 4px; }
    .rev-stat .sub { font-size: 11px; color: var(--muted); margin-top: 2px; }
    .rev-stat.s-teal { background: linear-gradient(135deg, #dff4f2, #fff); }
    .rev-stat.s-navy { background: linear-gradient(135deg, #e4edf7, #fff); }
    .rev-stat.s-rose { background: linear-gradient(135deg, #ffe5df, #fff); }
    .rev-stat.s-amber { background: linear-gradient(135deg, #fff1cf, #fff); }
    .rev-chart-wrap { position: relative; width: 100%; max-height: 280px; }
    .rev-tbl { width: 100%; border-collapse: collapse; font-size: 13px; }
    .rev-tbl th { text-align: left; font-size: 11px; text-transform: uppercase; letter-spacing: 0.06em; color: var(--muted); padding: 8px 10px; border-bottom: 2px solid var(--line, rgba(24,49,83,0.08)); }
    .rev-tbl td { padding: 8px 10px; border-bottom: 1px solid rgba(24,49,83,0.04); }
    .rev-tbl .num { text-align: right; font-variant-numeric: tabular-nums; }
    .rev-bar { display: inline-block; height: 6px; border-radius: 3px; vertical-align: middle; margin-right: 6px; }
    @media (max-width: 640px) {
        .rev-summary { grid-template-columns: 1fr 1fr; }
        .rev-stat .val { font-size: 18px; }
        .rev-dual { grid-template-columns: 1fr !important; }
    }
</style>

<div class="hero-card" style="padding:20px 24px;">
    <div style="display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:12px;">
        <div>
            <h1 class="hero-title" style="font-size:22px;">Revenue vs Expenses</h1>
            <p class="hero-copy" style="margin:4px 0 0;font-size:13px;">{{ $from }} &mdash; {{ $to }}</p>
        </div>
        <div style="display:flex;gap:8px;flex-wrap:wrap;">
            <a href="/reports/revenue/export/csv?from={{ $from }}&to={{ $to }}" class="btn btn-ghost btn-sm"><i class="fas fa-file-csv" style="margin-right:4px;"></i>CSV</a>
            <a href="/reports/revenue/export/pdf?from={{ $from }}&to={{ $to }}" class="btn btn-ghost btn-sm"><i class="fas fa-file-pdf" style="margin-right:4px;"></i>PDF</a>
        </div>
    </div>
</div>

{{-- Compact Date Filter --}}
<form method="GET" action="/reports/revenue" style="display:flex;flex-wrap:wrap;gap:10px;align-items:end;margin-bottom:16px;">
    <input type="date" name="from" value="{{ $from }}" style="max-width:160px;">
    <input type="date" name="to" value="{{ $to }}" style="max-width:160px;">
    <button type="submit" class="btn btn-primary btn-sm">Filter</button>
</form>

{{-- 6 Metrics in one compact grid --}}
@php
    $margin = $collected > 0 ? round(($netProfit / $collected) * 100, 1) : 0;
    $collectionRate = $invoiced > 0 ? round(($collected / $invoiced) * 100, 1) : 0;
@endphp
<div class="rev-summary" style="margin-bottom:16px;">
    <div class="rev-stat s-teal">
        <div class="label">Cash Collected</div>
        <div class="val">${{ number_format($collected, 2) }}</div>
    </div>
    <div class="rev-stat s-navy">
        <div class="label">Total Invoiced</div>
        <div class="val">${{ number_format($invoiced, 2) }}</div>
    </div>
    <div class="rev-stat s-rose">
        <div class="label">Total Expenses</div>
        <div class="val">${{ number_format($expenses, 2) }}</div>
    </div>
    <div class="rev-stat {{ $netProfit >= 0 ? 's-teal' : 's-rose' }}">
        <div class="label">Net Profit</div>
        <div class="val">${{ number_format($netProfit, 2) }}</div>
    </div>
    <div class="rev-stat s-navy">
        <div class="label">Profit Margin</div>
        <div class="val">{{ $margin }}%</div>
    </div>
    <div class="rev-stat s-amber">
        <div class="label">Collection Rate</div>
        <div class="val">{{ $collectionRate }}%</div>
    </div>
</div>

{{-- Chart + Table side by side on desktop, stacked on mobile --}}
@if(!empty($monthlyRevenue) || !empty($monthlyExpenses))
@php
    $allMonths = collect($monthlyRevenue)->pluck('month')
        ->merge(collect($monthlyExpenses)->pluck('month'))
        ->unique()->sort()->values();
    $revMap = collect($monthlyRevenue)->keyBy('month');
    $expMap = collect($monthlyExpenses)->keyBy('month');
    $chartLabels = $allMonths->map(fn($m) => date('M Y', strtotime($m . '-01')))->values()->toJson();
    $chartRev = $allMonths->map(fn($m) => round((float)($revMap[$m]['collected'] ?? 0), 2))->values()->toJson();
    $chartExp = $allMonths->map(fn($m) => round((float)($expMap[$m]['spent'] ?? 0), 2))->values()->toJson();
    $maxVal = max(1, $allMonths->map(fn($m) => max((float)($revMap[$m]['collected'] ?? 0), (float)($expMap[$m]['spent'] ?? 0)))->max());
@endphp

<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;" class="rev-dual">
    {{-- Chart --}}
    <div class="card" style="padding:18px;">
        <h3 class="section-title" style="font-size:15px;margin-bottom:12px;">Monthly Trend</h3>
        <div class="rev-chart-wrap">
            <canvas id="revExpChart"></canvas>
        </div>
    </div>

    {{-- Compact Table --}}
    <div class="card" style="padding:18px;overflow-x:auto;">
        <h3 class="section-title" style="font-size:15px;margin-bottom:12px;">Monthly Breakdown</h3>
        <table class="rev-tbl">
            <thead>
                <tr>
                    <th>Month</th>
                    <th class="num">Revenue</th>
                    <th class="num">Expenses</th>
                    <th class="num">Net</th>
                </tr>
            </thead>
            <tbody>
                @foreach($allMonths as $month)
                    @php
                        $rev = (float) ($revMap[$month]['collected'] ?? 0);
                        $exp = (float) ($expMap[$month]['spent'] ?? 0);
                        $net = $rev - $exp;
                        $revPct = $maxVal > 0 ? round($rev / $maxVal * 100) : 0;
                        $expPct = $maxVal > 0 ? round($exp / $maxVal * 100) : 0;
                    @endphp
                    <tr>
                        <td style="white-space:nowrap;">{{ date('M \'y', strtotime($month . '-01')) }}</td>
                        <td class="num">
                            <span class="rev-bar" style="width:{{ $revPct }}%;max-width:60px;background:var(--teal);"></span>
                            ${{ number_format($rev, 0) }}
                        </td>
                        <td class="num">
                            <span class="rev-bar" style="width:{{ $expPct }}%;max-width:60px;background:var(--rose, #e74c3c);"></span>
                            ${{ number_format($exp, 0) }}
                        </td>
                        <td class="num" style="font-weight:700;color:{{ $net >= 0 ? 'var(--teal)' : 'var(--rose, #e74c3c)' }};">
                            ${{ number_format($net, 0) }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
<script>
new Chart(document.getElementById('revExpChart'), {
    type: 'bar',
    data: {
        labels: {!! $chartLabels !!},
        datasets: [
            {
                label: 'Revenue',
                data: {!! $chartRev !!},
                backgroundColor: 'rgba(13,148,136,0.7)',
                borderRadius: 4,
                barPercentage: 0.7,
            },
            {
                label: 'Expenses',
                data: {!! $chartExp !!},
                backgroundColor: 'rgba(225,29,72,0.55)',
                borderRadius: 4,
                barPercentage: 0.7,
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { position: 'bottom', labels: { boxWidth: 12, padding: 16, font: { size: 12 } } }
        },
        scales: {
            x: { grid: { display: false } },
            y: { beginAtZero: true, ticks: { callback: v => '$' + v.toLocaleString(), font: { size: 11 } } }
        }
    }
});
</script>
@endif

<div style="margin-top:16px;">
    <a href="/reports" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i> Back to Reports</a>
</div>
@endsection
