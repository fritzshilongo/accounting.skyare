<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - {{ config('app.name', 'Skyare Trading CC') }}</title>
    <link rel="stylesheet" href="/assets/css/legacy-style.css?v=2">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: Arial, sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); margin: 0; padding: 20px; min-height: 100vh; display: flex; flex-direction: column; align-items: center; justify-content: center; box-sizing: border-box; }
        .container { max-width: 540px; margin: 0 auto; background: white; padding: 40px; border-radius: 8px; }
        h2 { text-align: center; color: #333; }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; color: #555; }
        input, select { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; font-size: 14px; }
        .btn { width: 100%; padding: 12px; background: #667eea; color: white; border: none; cursor: pointer; border-radius: 4px; font-weight: bold; font-size: 15px; }
        .btn:hover { background: #5a6fd6; }
        .error { color: #d32f2f; padding: 10px; background: #ffebee; border-radius: 4px; margin: 10px 0; }
        .link { text-align: center; margin-top: 20px; }
        .link a { color: #667eea; text-decoration: none; }
        .plan-cards { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-top: 8px; }
        .plan-card { border: 2px solid #ddd; border-radius: 8px; padding: 14px 12px; cursor: pointer; text-align: center; transition: border-color 0.2s, box-shadow 0.2s; position: relative; }
        .plan-card:hover { border-color: #667eea; }
        .plan-card.selected { border-color: #667eea; box-shadow: 0 0 0 3px rgba(102,126,234,0.2); background: #f8f9ff; }
        .plan-card input[type="radio"] { position: absolute; opacity: 0; pointer-events: none; }
        .plan-name { font-weight: 700; color: #333; font-size: 15px; margin-bottom: 4px; }
        .plan-price { color: #667eea; font-weight: 700; font-size: 20px; }
        .plan-detail { color: #888; font-size: 12px; margin-top: 2px; }
        .plan-save { display: inline-block; background: #4caf50; color: #fff; font-size: 11px; padding: 2px 8px; border-radius: 10px; margin-top: 4px; }
        .section-title { font-size: 13px; text-transform: uppercase; letter-spacing: 1px; color: #999; margin: 24px 0 8px; border-top: 1px solid #eee; padding-top: 16px; }
        .free-trial-note { background: #e8f5e9; color: #2e7d32; padding: 10px 14px; border-radius: 6px; text-align: center; font-size: 13px; margin-bottom: 16px; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Create Company Account</h2>
        
        @if(!empty($errors))
            @foreach($errors as $error)
                <div class="error">{{ $error }}</div>
            @endforeach
        @endif

        <form method="POST" action="/register">
            <input type="hidden" name="_token" value="{{ $token }}">

            <div class="free-trial-note">
                <i class="fas fa-gift"></i> <strong>1 month FREE trial</strong> included with every plan &mdash; no payment required to start.
            </div>

            <div class="section-title">Company Details</div>

            <div class="form-group">
                <label for="company_name">Company Name</label>
                <input type="text" id="company_name" name="company_name" value="{{ $old['company_name'] ?? '' }}" required>
            </div>

            <div class="form-group">
                <label for="subdomain">Subdomain</label>
                <div style="display: flex; align-items: center;">
                    <input type="text" id="subdomain" name="subdomain" value="{{ $old['subdomain'] ?? '' }}" required style="flex: 1;">
                    <span style="margin-left: 6px; white-space: nowrap;">.{{ $base_domain ?? 'skyare.space' }}</span>
                </div>
            </div>

            <div class="section-title">Admin Account</div>

            <div class="form-group">
                <label for="full_name">Full Name</label>
                <input type="text" id="full_name" name="full_name" value="{{ $old['full_name'] ?? '' }}" required>
            </div>

            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" value="{{ $old['email'] ?? '' }}" required>
            </div>

            <div class="form-group">
                <label for="phone">Contact Phone Number</label>
                <input type="tel" id="phone" name="phone" value="{{ $old['phone'] ?? '' }}" placeholder="+264 81 000 0000" required>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>

            <div class="section-title">Choose Your License Plan</div>

            <div class="plan-cards" style="grid-template-columns: repeat(3, 1fr);">
                <label class="plan-card {{ ($old['license_plan'] ?? '') === '3months' ? 'selected' : '' }}" onclick="selectPlan(this)">
                    <input type="radio" name="license_plan" value="3months" {{ ($old['license_plan'] ?? '') === '3months' ? 'checked' : '' }}>
                    <div class="plan-name">3 Months</div>
                    <div class="plan-price">N$750</div>
                    <div class="plan-detail">N$250/mo</div>
                </label>
                <label class="plan-card {{ ($old['license_plan'] ?? '') === '6months' ? 'selected' : '' }}" onclick="selectPlan(this)">
                    <input type="radio" name="license_plan" value="6months" {{ ($old['license_plan'] ?? '') === '6months' ? 'checked' : '' }}>
                    <div class="plan-name">6 Months</div>
                    <div class="plan-price">N$1,450</div>
                    <div class="plan-detail">N$242/mo</div>
                    <div class="plan-save">Save N$50</div>
                </label>
                <label class="plan-card {{ ($old['license_plan'] ?? 'yearly') === 'yearly' ? 'selected' : '' }}" onclick="selectPlan(this)">
                    <input type="radio" name="license_plan" value="yearly" {{ ($old['license_plan'] ?? 'yearly') === 'yearly' ? 'checked' : '' }}>
                    <div class="plan-name">1 Year</div>
                    <div class="plan-price">N$2,850</div>
                    <div class="plan-detail">N$238/mo</div>
                    <div class="plan-save">Save N$150</div>
                </label>
            </div>

            <button type="submit" class="btn" style="margin-top: 24px;">Register &amp; Start Free Trial</button>
        </form>

        <div class="link">
            <p>Already have an account? <a href="/login">Login</a></p>
        </div>
    </div>

    @include('auth.partials.footer')

    <script>
    function selectPlan(el) {
        document.querySelectorAll('.plan-card').forEach(c => c.classList.remove('selected'));
        el.classList.add('selected');
        el.querySelector('input[type="radio"]').checked = true;
    }
    </script>
</body>
</html>
