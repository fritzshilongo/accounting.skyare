<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - {{ config('app.name', 'Skyare Trading CC') }}</title>
    <link rel="stylesheet" href="/assets/css/legacy-style.css?v=2">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: Arial, sans-serif; background: radial-gradient(circle at top left, rgba(255,255,255,0.18), transparent 35%), linear-gradient(135deg, #667eea 0%, #764ba2 100%); margin: 0; padding: 20px; min-height: 100vh; display: flex; flex-direction: column; align-items: center; justify-content: center; box-sizing: border-box; }
        .container { max-width: 420px; margin: 0 auto; background: rgba(255,255,255,0.97); padding: 34px; border-radius: 20px; box-shadow: 0 18px 40px rgba(15, 23, 42, 0.18); border: 1px solid rgba(102, 126, 234, 0.16); }
        h2 { text-align: center; color: #1f2937; margin-bottom: 24px; }
        .form-group { margin-bottom: 18px; }
        label { display: block; margin-bottom: 8px; font-weight: 700; color: #334155; }
        input, select { width: 100%; padding: 14px 16px; border: 1px solid #d1d5db; border-radius: 12px; box-sizing: border-box; background: #f8fafc; transition: border-color 0.2s ease, box-shadow 0.2s ease; }
        select { appearance: none; -webkit-appearance: none; -moz-appearance: none; background-image: linear-gradient(45deg, transparent 50%, #667eea 50%), linear-gradient(135deg, #667eea 50%, transparent 50%); background-position: calc(100% - 22px) center, calc(100% - 14px) center; background-size: 8px 8px, 8px 8px; background-repeat: no-repeat; padding-right: 38px; }
        input:focus, select:focus { outline: none; border-color: #667eea; box-shadow: 0 0 12px rgba(102, 126, 234, 0.18); }
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
        .btn { width: 100%; padding: 14px; background: #4f46e5; color: white; border: none; cursor: pointer; border-radius: 12px; font-weight: 700; transition: transform 0.2s ease, background 0.2s ease; }
        .btn:hover { background: #4338ca; transform: translateY(-1px); }
        .note { margin-bottom: 16px; color: #475569; font-size: 13px; line-height: 1.6; }
        .error { color: #d32f2f; padding: 10px; background: #ffebee; border-radius: 4px; }
        .success { color: #388e3c; padding: 10px; background: #e8f5e9; border-radius: 4px; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Reset Password</h2>
        
        @if(!empty($errors))
            @foreach($errors as $error)
                <div class="error">{{ $error }}</div>
            @endforeach
        @endif

        @if($success)
            <div class="success">{{ $success }}</div>
        @endif

        <form method="POST" action="/forgot-password">
            <input type="hidden" name="_token" value="{{ $token }}">

            @if(!empty($showCompanySelect) && !empty($companies))
                <div class="form-group">
                    <div class="company-switch-header">
                        <i class="fa-solid fa-building"></i>
                        <span>Switch company</span>
                    </div>
                    <label for="company_id">Company</label>
                    <p class="note">Choose the company workspace that owns your user account.</p>
                    <select id="company_id" name="company_id" required>
                        <option value="">Select a company</option>
                        @foreach($companies as $c)
                            <option value="{{ $c['company_id'] }}" {{ ($selectedCompanyId ?? 0) === (int) $c['company_id'] ? 'selected' : '' }}>🏢 {{ $c['company_name'] }} ({{ $c['subdomain'] ?? '' }})</option>
                        @endforeach
                    </select>
                </div>
            @endif

            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" value="{{ $email ?? '' }}" required>
            </div>

            <button type="submit" class="btn">Send Reset Link</button>
        </form>

        <p style="text-align: center; margin-top: 20px;">
            <a href="/login" style="color: #667eea; text-decoration: none;">Back to Login</a>
        </p>
    </div>

    @include('auth.partials.footer')
</body>
</html>
