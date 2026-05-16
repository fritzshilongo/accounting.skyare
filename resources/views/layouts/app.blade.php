<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @php
        $defaultTitle = config('app.name', 'Skyare Trading CC');
        $defaultDescription = 'Skyare Trading CC is a technology company providing web hosting, custom application design, software development and IoT services.';
        $metaTitle = $metaTitle ?? $defaultTitle;
        $metaDescription = $metaDescription ?? $defaultDescription;
    @endphp
    <title>@yield('title', $metaTitle)</title>
    <meta name="description" content="{{ $metaDescription }}">
    <meta name="keywords" content="Skyare Trading CC, web hosting, application design, software development, IoT, technology services, cloud solutions, digital services">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="{{ url()->current() }}">
    <meta property="og:type" content="website">
    <meta property="og:title" content="@yield('meta_title', $metaTitle)">
    <meta property="og:description" content="@yield('meta_description', $metaDescription)">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:site_name" content="{{ $defaultTitle }}">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="@yield('meta_title', $metaTitle)">
    <meta name="twitter:description" content="@yield('meta_description', $metaDescription)">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --page: #f6f1e8;
            --surface: rgba(255, 255, 255, 0.92);
            --surface-strong: #ffffff;
            --ink: #183153;
            --muted: #66748a;
            --line: rgba(24, 49, 83, 0.1);
            --navy: #17324d;
            --teal: #12807a;
            --teal-soft: #e3f4f1;
            --amber: #d79a1e;
            --amber-soft: #fff1cf;
            --rose: #df6f5f;
            --rose-soft: #ffe3dc;
            --shadow: 0 24px 60px rgba(23, 50, 77, 0.12);
            --shadow-soft: 0 12px 30px rgba(23, 50, 77, 0.08);
            --radius-lg: 24px;
            --radius-md: 18px;
            --radius-sm: 12px;
        }

        * { box-sizing: border-box; }
        html, body { margin: 0; min-height: 100%; }
        body {
            font-family: "Trebuchet MS", "Segoe UI", sans-serif;
            color: var(--ink);
            background:
                radial-gradient(circle at top left, rgba(18, 128, 122, 0.14), transparent 28%),
                radial-gradient(circle at top right, rgba(215, 154, 30, 0.18), transparent 24%),
                linear-gradient(180deg, #fbf8f2 0%, var(--page) 100%);
        }

        a { color: inherit; text-decoration: none; }

        .shell {
            display: grid;
            grid-template-columns: 280px minmax(0, 1fr);
            min-height: 100vh;
        }

        .sidebar {
            position: sticky;
            top: 0;
            height: 100vh;
            padding: 24px 20px;
            overflow-y: auto;
            overflow-x: hidden;
            background:
                linear-gradient(180deg, rgba(18, 128, 122, 0.18), transparent 24%),
                linear-gradient(180deg, #17324d 0%, #12283d 100%);
            color: #f6f7fb;
            box-shadow: inset -1px 0 0 rgba(255, 255, 255, 0.06);
        }

        .brand {
            padding: 18px 18px 24px;
            border-radius: var(--radius-md);
            background: linear-gradient(135deg, rgba(255,255,255,0.12), rgba(255,255,255,0.04));
            border: 1px solid rgba(255,255,255,0.08);
            box-shadow: var(--shadow-soft);
            margin-bottom: 26px;
        }

        .brand-kicker {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.22em;
            color: rgba(255,255,255,0.68);
            margin-bottom: 10px;
        }

        .brand-title {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 24px;
            font-weight: 700;
        }

        .brand-title i {
            width: 44px;
            height: 44px;
            display: inline-grid;
            place-items: center;
            border-radius: 14px;
            background: linear-gradient(135deg, var(--amber), #f1c766);
            color: var(--navy);
        }

        .brand-caption {
            margin-top: 12px;
            color: rgba(255,255,255,0.72);
            font-size: 14px;
            line-height: 1.5;
        }

        .nav-group-title {
            margin: 18px 14px 8px;
            color: rgba(255,255,255,0.52);
            text-transform: uppercase;
            letter-spacing: 0.14em;
            font-size: 11px;
        }

        .nav-list {
            list-style: none;
            margin: 0;
            padding: 0;
            display: grid;
            gap: 6px;
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 13px 14px;
            border-radius: 14px;
            color: rgba(255,255,255,0.88);
            transition: transform 0.18s ease, background 0.18s ease, color 0.18s ease;
        }

        .nav-link i {
            width: 18px;
            text-align: center;
            color: rgba(255,255,255,0.72);
        }

        .nav-link:hover,
        .nav-link.is-active {
            background: linear-gradient(90deg, rgba(255,255,255,0.16), rgba(255,255,255,0.05));
            transform: translateX(4px);
            color: #fff;
        }

        .nav-link:hover i,
        .nav-link.is-active i {
            color: #f8c85b;
        }

        .content {
            padding: 24px;
        }

        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 18px;
            margin-bottom: 22px;
            padding: 18px 22px;
            border-radius: var(--radius-lg);
            background: rgba(255,255,255,0.7);
            border: 1px solid rgba(255,255,255,0.7);
            backdrop-filter: blur(14px);
            box-shadow: var(--shadow-soft);
        }

        .topbar-eyebrow {
            text-transform: uppercase;
            letter-spacing: 0.18em;
            font-size: 11px;
            color: var(--muted);
            margin-bottom: 6px;
        }

        .topbar-title {
            font-size: 24px;
            font-weight: 700;
        }

        .topbar-meta {
            color: var(--muted);
            font-size: 14px;
        }

        .user-pill-wrap {
            position: relative;
        }

        .user-pill {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 10px 12px 10px 18px;
            border-radius: 999px;
            background: var(--surface-strong);
            border: 1px solid var(--line);
            box-shadow: var(--shadow-soft);
            cursor: pointer;
            transition: border-color 0.15s;
        }
        .user-pill:hover { border-color: var(--teal); }

        .user-dropdown {
            display: none;
            position: absolute;
            right: 0;
            top: calc(100% + 8px);
            min-width: 220px;
            background: #fff;
            border-radius: 14px;
            border: 1px solid var(--line);
            box-shadow: 0 12px 36px rgba(24,49,83,0.13);
            z-index: 999;
            overflow: hidden;
        }
        .user-dropdown.open { display: block; }
        .user-dropdown a, .user-dropdown button {
            display: flex;
            align-items: center;
            gap: 10px;
            width: 100%;
            padding: 11px 18px;
            font-size: 14px;
            color: var(--ink);
            text-decoration: none;
            background: none;
            border: none;
            cursor: pointer;
            transition: background 0.1s;
        }
        .user-dropdown a:hover, .user-dropdown button:hover { background: rgba(24,49,83,0.04); }
        .user-dropdown a i, .user-dropdown button i { width: 18px; text-align: center; color: var(--ink-muted); }
        .user-dropdown .dd-divider { height: 1px; background: var(--line); margin: 4px 0; }
        .user-dropdown .dd-logout:hover { background: rgba(231,76,60,0.08); color: var(--rose, #e74c3c); }
        .user-dropdown .dd-logout:hover i { color: var(--rose, #e74c3c); }

        .user-avatar {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            display: grid;
            place-items: center;
            background: linear-gradient(135deg, var(--teal), #1ea9a0);
            color: white;
            font-weight: 700;
        }

        .page-stack {
            display: grid;
            gap: 20px;
        }

        .hero-card,
        .card,
        .table-card,
        .form-card,
        .metric-card {
            background: var(--surface);
            border: 1px solid rgba(255,255,255,0.8);
            box-shadow: var(--shadow-soft);
            border-radius: var(--radius-lg);
        }

        .hero-card,
        .card,
        .table-card,
        .form-card { padding: 24px; }

        .hero-card {
            background:
                radial-gradient(circle at right top, rgba(215,154,30,0.18), transparent 22%),
                linear-gradient(135deg, rgba(18,128,122,0.12), rgba(255,255,255,0.95) 52%);
        }

        .hero-title,
        .section-title {
            margin: 0;
            font-size: 28px;
            line-height: 1.1;
        }

        .hero-copy,
        .section-copy,
        .muted { color: var(--muted); }

        .toolbar,
        .toolbar-row {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            align-items: center;
            justify-content: space-between;
        }

        .toolbar-left,
        .toolbar-right,
        .inline-actions { display: flex; flex-wrap: wrap; gap: 10px; align-items: center; }

        .stats-grid,
        .metric-grid,
        .panel-grid {
            display: grid;
            gap: 18px;
        }

        .stats-grid,
        .metric-grid { grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); }
        .panel-grid { grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); }

        .metric-card {
            padding: 20px;
            position: relative;
            overflow: hidden;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            cursor: default;
        }

        .metric-card:hover {
            transform: translateY(-4px) scale(1.02);
            box-shadow: 0 8px 28px rgba(24,49,83,0.13);
        }

        .metric-card::after {
            content: "";
            position: absolute;
            inset: auto -24px -24px auto;
            width: 96px;
            height: 96px;
            border-radius: 50%;
            background: rgba(255,255,255,0.32);
        }

        .metric-card.teal { background: linear-gradient(135deg, #dff4f2, #ffffff); }
        .metric-card.amber { background: linear-gradient(135deg, #fff1cf, #ffffff); }
        .metric-card.rose { background: linear-gradient(135deg, #ffe5df, #ffffff); }
        .metric-card.navy { background: linear-gradient(135deg, #e4edf7, #ffffff); }

        .metric-label {
            color: var(--muted);
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            margin-bottom: 10px;
        }

        .metric-value {
            font-size: 30px;
            font-weight: 700;
            margin-bottom: 6px;
        }

        .metric-meta { color: var(--muted); font-size: 13px; }

        .btn,
        button,
        input[type="submit"] {
            appearance: none;
            border: 0;
            border-radius: 999px;
            padding: 12px 18px;
            font: inherit;
            font-weight: 700;
            cursor: pointer;
            transition: transform 0.18s ease, box-shadow 0.18s ease, opacity 0.18s ease;
        }

        .btn:hover,
        button:hover,
        input[type="submit"]:hover {
            transform: translateY(-2px) scale(1.03);
            box-shadow: 0 8px 24px rgba(24, 49, 83, 0.18);
        }
        .btn:active,
        button:active,
        input[type="submit"]:active {
            transform: translateY(0) scale(0.98);
            box-shadow: 0 2px 8px rgba(24, 49, 83, 0.1);
        }

        .btn-primary:hover,
        button:hover,
        input[type="submit"]:hover {
            background: linear-gradient(135deg, #0d5f61, var(--teal));
        }
        .btn-secondary:hover { background: #e4ebf1; }
        .btn-accent:hover { background: linear-gradient(135deg, #bb7e13, var(--amber)); }
        .btn-danger:hover { background: linear-gradient(135deg, #c85849, var(--rose)); }
        .btn-ghost:hover { background: rgba(24,49,83,0.06); }

        .btn-primary,
        button,
        input[type="submit"] {
            background: linear-gradient(135deg, var(--teal), #0d5f61);
            color: #fff;
        }

        .btn-secondary { background: #eff4f8; color: var(--ink); border: 1px solid var(--line); }
        .btn-accent { background: linear-gradient(135deg, var(--amber), #bb7e13); color: var(--navy); }
        .btn-danger { background: linear-gradient(135deg, var(--rose), #c85849); color: white; }
        .btn-ghost { background: rgba(255,255,255,0.75); color: var(--ink); border: 1px solid var(--line); }
        .btn-sm { padding: 9px 14px; font-size: 13px; }

        .filter-bar,
        .form-grid {
            display: grid;
            gap: 16px;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        }

        .form-grid.two { grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); }
        .form-grid.three { grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); }
        .span-2 { grid-column: span 2; }
        .span-full { grid-column: 1 / -1; }

        label {
            display: block;
            margin-bottom: 8px;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: var(--muted);
            font-weight: 700;
        }

        input,
        select,
        textarea {
            width: 100%;
            padding: 12px 14px;
            border: 1px solid var(--line);
            border-radius: var(--radius-sm);
            font: inherit;
            color: var(--ink);
            background: #fff;
            transition: border-color 0.15s, box-shadow 0.15s;
        }
        input:focus,
        select:focus,
        textarea:focus {
            outline: none;
            border-color: var(--teal);
            box-shadow: 0 0 0 3px rgba(18,128,122,0.12);
        }

        .table-wrap { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        thead th {
            padding: 12px 14px;
            text-align: left;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: var(--muted);
            border-bottom: 2px solid var(--line);
            background: rgba(255,255,255,0.6);
        }
        tbody td {
            padding: 13px 14px;
            border-bottom: 1px solid rgba(24,49,83,0.06);
            font-size: 14px;
            vertical-align: middle;
        }
        tbody tr:last-child td { border-bottom: 0; }
        tbody tr:hover td { background: rgba(18,128,122,0.04); }

        .row-title { font-weight: 700; }
        .row-subtitle { font-size: 12px; color: var(--muted); }

        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.04em;
        }
        .badge.teal  { background: var(--teal-soft);  color: var(--teal); }
        .badge.amber { background: var(--amber-soft); color: var(--amber); }
        .badge.rose  { background: var(--rose-soft);  color: var(--rose); }
        .badge.navy  { background: #e4edf7; color: var(--navy); }

        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: var(--muted);
            font-size: 15px;
        }

        .pagination-wrap {
            margin-top: 18px;
            display: flex;
            justify-content: center;
        }
        .pagination-wrap nav { display: flex; gap: 6px; flex-wrap: wrap; justify-content: center; }
        .pagination-wrap span[aria-current="page"] > span,
        .pagination-wrap a {
            display: inline-block;
            padding: 8px 12px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            border: 1px solid var(--line);
            background: #fff;
            color: var(--ink);
        }
        .pagination-wrap span[aria-current="page"] > span {
            background: var(--teal);
            color: #fff;
            border-color: var(--teal);
        }

        /* ── Mobile hamburger button (hidden on desktop) ── */
        .mobile-menu-btn {
            display: none;
            align-items: center;
            justify-content: center;
            width: 42px;
            height: 42px;
            border-radius: 12px;
            background: var(--surface-strong);
            border: 1px solid var(--line);
            color: var(--ink);
            font-size: 20px;
            cursor: pointer;
            flex-shrink: 0;
            box-shadow: var(--shadow-soft);
            transition: background 0.15s;
            padding: 0;
        }
        .mobile-menu-btn:hover {
            background: var(--teal-soft);
            transform: none;
            box-shadow: var(--shadow-soft);
        }

        /* ── Sidebar overlay ── */
        .sidebar-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(23, 50, 77, 0.45);
            z-index: 998;
            backdrop-filter: blur(2px);
        }
        .sidebar-overlay.open { display: block; }

        /* ── Responsive: tablets and phones ── */
        @media (max-width: 768px) {
            .shell { grid-template-columns: 1fr; }

            .mobile-menu-btn { display: flex; }

            .sidebar {
                position: fixed;
                top: 0;
                left: 0;
                width: 280px;
                height: 100vh;
                height: 100dvh;
                z-index: 999;
                transform: translateX(-100%);
                transition: transform 0.28s cubic-bezier(0.4, 0, 0.2, 1);
                overflow-y: auto;
                -webkit-overflow-scrolling: touch;
                padding-bottom: 40px;
            }
            .sidebar.open {
                transform: translateX(0);
            }

            .content { padding: 16px 12px; }

            .topbar {
                padding: 14px 14px;
                border-radius: var(--radius-md);
                gap: 12px;
            }
            .topbar-title { font-size: 18px; }
            .topbar-eyebrow { font-size: 10px; }

            .hero-card,
            .card,
            .table-card,
            .form-card { padding: 16px; border-radius: var(--radius-md); }

            .hero-title,
            .section-title { font-size: 22px; }

            .stats-grid,
            .metric-grid { grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 12px; }

            .panel-grid { grid-template-columns: 1fr; }

            .filter-bar,
            .form-grid { grid-template-columns: 1fr !important; }
            .form-grid.two,
            .form-grid.three { grid-template-columns: 1fr !important; }
            .span-2 { grid-column: span 1 !important; }

            .toolbar,
            .toolbar-row { flex-direction: column; align-items: stretch; gap: 10px; }
            .toolbar-left,
            .toolbar-right,
            .inline-actions { justify-content: center; flex-wrap: wrap; }

            .metric-value { font-size: 22px; }

            .table-wrap { overflow-x: auto; -webkit-overflow-scrolling: touch; }
            table { min-width: 600px; }

            .user-pill {
                padding: 8px 10px;
                gap: 8px;
            }
            .user-pill > div:nth-child(2) { display: none; }
            .user-pill .fa-chevron-down { display: none; }
        }

        @media (max-width: 480px) {
            .stats-grid,
            .metric-grid { grid-template-columns: 1fr 1fr; gap: 10px; }

            .metric-card { padding: 14px; }
            .metric-value { font-size: 20px; }
            .metric-label { font-size: 11px; }

            .hero-title,
            .section-title { font-size: 19px; }

            .btn,
            button,
            input[type="submit"] { padding: 10px 14px; font-size: 13px; }

            thead th { padding: 10px 10px; font-size: 11px; }
            tbody td { padding: 10px 10px; font-size: 13px; }
        }
    </style>
</head>
<body>
<div class="shell">
    {{-- ── Sidebar ── --}}
    <nav class="sidebar">
        <div class="brand">
            <div class="brand-kicker">Accounting Platform</div>
            <div class="brand-title">
                <i class="fas fa-chart-line"></i>
                <span>Skyare</span>
            </div>
            <div class="brand-caption">Financial management for growing businesses</div>
        </div>

        @php
            $currentPath = request()->path();
            $nav = function(string $href, string $icon, string $label) use ($currentPath): string {
                $normalizedHref = '/' . trim($href, '/');
                $normalizedCurrent = '/' . trim($currentPath, '/');
                $active = ($normalizedHref === $normalizedCurrent || str_starts_with($normalizedCurrent . '/', $normalizedHref . '/')) ? ' is-active' : '';
                return '<a href="' . e($href) . '" class="nav-link' . $active . '"><i class="fas ' . $icon . '"></i> ' . $label . '</a>';
            };
        @endphp

        @php
            $host = request()->getHost();
            $baseDomain = config('app.base_domain', 'skyare.space');
            $issuerSubdomain = trim((string) config('app.license_issuer_subdomain', 'www'));
            $isIssuerTenant = false;

            if ($host === $baseDomain) {
                $isIssuerTenant = $issuerSubdomain === 'www';
            } elseif (str_ends_with($host, '.' . $baseDomain)) {
                $subdomain = substr($host, 0, -strlen('.' . $baseDomain));
                $isIssuerTenant = $subdomain === $issuerSubdomain;
            }
        @endphp

        @if($isIssuerTenant)
            <div class="nav-group-title">Admin</div>
            <ul class="nav-list">
                <li>{!! $nav('/dashboard',       'fa-gauge-high',     'Dashboard') !!}</li>
                <li>{!! $nav('/settings/license', 'fa-shield-halved',  'Licensing') !!}</li>
                <li>{!! $nav('/audit',           'fa-shield-halved',  'Audit Trail') !!}</li>
                <li>{!! $nav('/users',           'fa-user-group',     'Users') !!}</li>
                <li>{!! $nav('/settings',        'fa-gear',           'Settings') !!}</li>
                <li>{!! $nav('/settings/backups','fa-database',       'Backups') !!}</li>
            </ul>
        @else
            <div class="nav-group-title">Main</div>
            <ul class="nav-list">
                <li>{!! $nav('/dashboard',       'fa-gauge-high',     'Dashboard') !!}</li>
                <li>{!! $nav('/module-hub',      'fa-table-cells-large', 'Modules') !!}</li>
                <li>{!! $nav('/invoices',        'fa-file-invoice',   'Invoices') !!}</li>
                <li>{!! $nav('/estimates',       'fa-file-lines',     'Estimates') !!}</li>
                <li>{!! $nav('/payments',        'fa-credit-card',    'Payments') !!}</li>
            </ul>

            <div class="nav-group-title">Contacts &amp; Products</div>
            <ul class="nav-list">
                <li>{!! $nav('/clients',         'fa-users',          'Clients') !!}</li>
                <li>{!! $nav('/products',        'fa-box',            'Products') !!}</li>
                <li>{!! $nav('/inventory',       'fa-warehouse',      'Inventory') !!}</li>
            </ul>

            <div class="nav-group-title">Finance</div>
            <ul class="nav-list">
                <li>{!! $nav('/expenses',           'fa-receipt',        'Expenses') !!}</li>
                <li>{!! $nav('/journal-entries',    'fa-book',           'Journal') !!}</li>
                <li>{!! $nav('/credit-management',  'fa-hand-holding-dollar', 'Credit Management') !!}</li>
                <li>{!! $nav('/credit-customers',   'fa-address-card',   'Credit Customers') !!}</li>
            </ul>

            <div class="nav-group-title">Reports</div>
            <ul class="nav-list">
                <li>{!! $nav('/reports',         'fa-chart-bar',      'Reports') !!}</li>
                <li>{!! $nav('/sales',           'fa-trending-up',    'Sales Overview') !!}</li>
                <li>{!! $nav('/audit',           'fa-shield-halved',  'Audit Trail') !!}</li>
            </ul>

            <div class="nav-group-title">Admin</div>
            <ul class="nav-list">
                <li>{!! $nav('/profile',             'fa-id-badge',       'Profile') !!}</li>
                <li>{!! $nav('/users',               'fa-user-group',     'Users') !!}</li>
                <li>{!! $nav('/recurring-invoices',  'fa-rotate',         'Recurring') !!}</li>
                <li>{!! $nav('/tax-rates',           'fa-percent',        'Tax Rates') !!}</li>
                <li>{!! $nav('/import',              'fa-file-import',    'Import') !!}</li>
                <li>{!! $nav('/settings',            'fa-gear',           'Settings') !!}</li>
                <li>{!! $nav('/settings/license',    'fa-shield-halved',  'Licensing') !!}</li>
                <li>{!! $nav('/settings/backups',    'fa-database',       'Backups') !!}</li>
            </ul>
        @endif
    </nav>

    {{-- ── Mobile overlay ── --}}
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    {{-- ── Main content ── --}}
    <div class="content">
        {{-- Topbar --}}
        <div class="topbar">
            <button class="mobile-menu-btn" id="mobileMenuBtn" aria-label="Open menu">
                <i class="fas fa-bars"></i>
            </button>
            <div style="min-width:0;flex:1;">
                <div class="topbar-eyebrow">{{ config('app.name', 'Skyare Trading CC') }}</div>
                <div class="topbar-title">@yield('title', 'Dashboard')</div>
            </div>
            <div class="user-pill-wrap">
                <div class="user-pill" onclick="this.nextElementSibling.classList.toggle('open')" style="cursor:pointer;">
                    <div class="user-avatar">
                        {{ strtoupper(substr($_SESSION['user']['name'] ?? $_SESSION['user']['username'] ?? 'U', 0, 1)) }}
                    </div>
                    <div>
                        <div style="font-weight:700;font-size:14px;">{{ $_SESSION['user']['name'] ?? $_SESSION['user']['username'] ?? 'User' }}</div>
                        <div style="font-size:12px;color:var(--muted);">{{ $_SESSION['company']['company_name'] ?? '' }}</div>
                    </div>
                    <i class="fas fa-chevron-down" style="color:var(--muted);font-size:12px;margin-left:4px;"></i>
                </div>
                <div class="user-dropdown">
                    <a href="/profile"><i class="fas fa-user"></i> Profile</a>
                    <a href="/notifications"><i class="fas fa-bell"></i> Notifications</a>
                    <a href="/settings"><i class="fas fa-gear"></i> Settings</a>
                    <a href="/settings/license"><i class="fas fa-shield-halved"></i> Licensing</a>
                    <a href="/settings/backups"><i class="fas fa-database"></i> Backups</a>
                    <div class="dd-divider"></div>
                    <form method="POST" action="/logout" style="margin:0;">
                        @csrf
                        <button type="submit" class="dd-logout"><i class="fas fa-right-from-bracket"></i> Logout</button>
                    </form>
                </div>
            </div>
        </div>

        {{-- Flash messages --}}
        @include('partials.alerts')

        {{-- Page content --}}
        <div class="page-stack">
            @yield('content')
        </div>
    </div>
</div>

<script>
    // Close user dropdown when clicking outside
    document.addEventListener('click', function(e) {
        document.querySelectorAll('.user-dropdown.open').forEach(function(dd) {
            if (!dd.previousElementSibling.contains(e.target)) {
                dd.classList.remove('open');
            }
        });
    });

    // Mobile sidebar drawer toggle
    (function() {
        var btn = document.getElementById('mobileMenuBtn');
        var sidebar = document.querySelector('.sidebar');
        var overlay = document.getElementById('sidebarOverlay');
        if (!btn || !sidebar || !overlay) return;

        function openDrawer() {
            sidebar.classList.add('open');
            overlay.classList.add('open');
            document.body.style.overflow = 'hidden';
        }
        function closeDrawer() {
            sidebar.classList.remove('open');
            overlay.classList.remove('open');
            document.body.style.overflow = '';
        }

        btn.addEventListener('click', function(e) {
            e.stopPropagation();
            if (sidebar.classList.contains('open')) { closeDrawer(); } else { openDrawer(); }
        });

        overlay.addEventListener('click', closeDrawer);

        // Close drawer when a nav link is tapped
        sidebar.querySelectorAll('.nav-link').forEach(function(link) {
            link.addEventListener('click', closeDrawer);
        });
    })();
</script>
@stack('scripts')
</body>
</html>

 