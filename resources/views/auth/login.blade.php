<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - {{ $company['company_name'] ?? config('app.name', 'Skyare Trading CC') }}</title>
    <link rel="stylesheet" href="/assets/css/legacy-style.css?v=2">
    <link rel="stylesheet" href="/assets/css/app.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .login-container {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 24px;
            background: radial-gradient(circle at top left, rgba(255,255,255,0.18), transparent 35%), linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .login-form {
            background: rgba(255,255,255,0.96);
            padding: 32px;
            border-radius: 20px;
            box-shadow: 0 18px 40px rgba(15, 23, 42, 0.18);
            width: 100%;
            max-width: 380px;
            border: 1px solid rgba(102, 126, 234, 0.16);
        }
        .login-form h2 {
            text-align: center;
            margin-bottom: 30px;
            color: #333;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #555;
            font-weight: bold;
        }
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px 14px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
            background: #fbfbff;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }
        .form-group select {
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
            background-image: linear-gradient(45deg, transparent 50%, #667eea 50%), linear-gradient(135deg, #667eea 50%, transparent 50%);
            background-position: calc(100% - 20px) center, calc(100% - 14px) center;
            background-size: 8px 8px, 8px 8px;
            background-repeat: no-repeat;
            padding-right: 34px;
        }
        .company-switch-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 12px;
            color: #334155;
            font-size: 14px;
            font-weight: 700;
        }
        .company-switch-header i {
            color: #4f46e5;
            font-size: 16px;
        }
        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 10px rgba(102, 126, 234, 0.2);
        }
        .note {
            margin-bottom: 16px;
            color: #4a5568;
            font-size: 13px;
            line-height: 1.5;
        }
        .btn {
            width: 100%;
            padding: 14px;
            background: #4f46e5;
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            transition: background 0.2s ease, transform 0.2s ease;
        }
        .btn:hover {
            background: #4338ca;
            transform: translateY(-1px);
        }
        .error {
            color: #d32f2f;
            margin-bottom: 20px;
            padding: 10px;
            background: #ffebee;
            border-radius: 4px;
        }
        .links {
            text-align: center;
            margin-top: 22px;
            display: flex;
            justify-content: center;
            gap: 16px;
            flex-wrap: wrap;
        }
        .links a {
            color: #4f46e5;
            text-decoration: none;
            font-size: 14px;
        }
        .links a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-form">
            <h2>{{ $company['company_name'] ?? config('app.name', 'Skyare Trading CC') }}</h2>
            
            @if(isset($error))
                <div class="error">{{ $error }}</div>
            @endif

            <form method="POST" action="/login">
                <input type="hidden" name="_token" value="{{ $token }}">

                @if(!empty($showCompanySelect) && !empty($companies))
                    <div class="form-group">
                        <div class="company-switch-header">
                            <i class="fa-solid fa-building"></i>
                            <span>Switch company</span>
                        </div>
                        <label for="company_id">Company</label>
                        <select id="company_id" name="company_id" required data-base-domain="{{ config('app.base_domain', 'skyare.space') }}" onchange="var option=this.options[this.selectedIndex]; var subdomain=option.dataset.subdomain; var baseDomain=this.dataset.baseDomain; if (subdomain && baseDomain) { window.location.href = window.location.protocol + '//' + subdomain + '.' + baseDomain + '/login'; }">
                            <option value="">Select a company</option>
                            @foreach($companies as $c)
                                <option value="{{ $c['company_id'] }}" data-subdomain="{{ $c['subdomain'] ?? '' }}" data-company-name="{{ strtolower($c['company_name'] ?? '') }}" data-subdomain-match="{{ strtolower($c['subdomain'] ?? '') }}" {{ ($selectedCompanyId ?? 0) === (int) $c['company_id'] ? 'selected' : '' }}>🏢 {{ $c['company_name'] }} ({{ $c['subdomain'] ?? '' }})</option>
                            @endforeach
                        </select>
                    </div>
                @elseif(!empty($isIssuerHost))
                    <div class="form-group">
                        <div class="company-switch-header">
                            <i class="fa-solid fa-shield-halved"></i>
                            <span>License issuer login</span>
                        </div>
                        <p class="note">This workspace is the license issuer tenant. Company switching is disabled here.</p>
                    </div>
                @endif

                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" value="{{ $email ?? '' }}" required>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>

                <button type="submit" class="btn">Login</button>
            </form>


            <div class="links">
                <a href="/register">Register</a>
                <a href="/forgot-password">Forgot Password?</a>
                @if(!empty($baseDomain) && empty($isBaseDomainHost))
                    <a href="//{{ $baseDomain }}/login">Back to tenant selection</a>
                @endif
            </div>
        </div>

        @include('auth.partials.footer')
    </div>
    <script>
    </script>
</body>
</html>
