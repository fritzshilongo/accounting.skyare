<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accept Invitation - Skyare</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --page: #f6f1e8;
            --surface: rgba(255, 255, 255, 0.92);
            --ink: #183153;
            --muted: #66748a;
            --teal: #12807a;
            --amber: #d79a1e;
            --navy: #17324d;
            --radius-lg: 24px;
            --shadow: 0 24px 60px rgba(23, 50, 77, 0.12);
        }
        * { box-sizing: border-box; }
        html, body { margin: 0; min-height: 100vh; }
        body {
            font-family: "Trebuchet MS", "Segoe UI", sans-serif;
            color: var(--ink);
            background:
                radial-gradient(circle at top left, rgba(18, 128, 122, 0.14), transparent 28%),
                radial-gradient(circle at top right, rgba(215, 154, 30, 0.18), transparent 24%),
                linear-gradient(180deg, #fbf8f2 0%, var(--page) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }
        .invite-card {
            width: 100%;
            max-width: 480px;
            background: var(--surface);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow);
            padding: 40px;
        }
        .invite-logo {
            text-align: center;
            margin-bottom: 24px;
        }
        .invite-logo i {
            width: 56px; height: 56px;
            display: inline-grid; place-items: center;
            border-radius: 16px;
            background: linear-gradient(135deg, var(--amber), #f1c766);
            color: var(--navy); font-size: 24px;
        }
        .invite-title { text-align: center; font-size: 24px; font-weight: 700; margin: 0 0 8px; }
        .invite-sub { text-align: center; color: var(--muted); margin-bottom: 28px; }
        label {
            display: block; margin-bottom: 8px; font-size: 13px;
            text-transform: uppercase; letter-spacing: 0.08em; color: var(--muted); font-weight: 700;
        }
        input {
            width: 100%; border: 1px solid rgba(24,49,83,0.12); background: #fff;
            color: var(--ink); border-radius: 16px; padding: 14px 16px; font: inherit;
            margin-bottom: 16px;
        }
        input:focus { outline: none; border-color: rgba(18,128,122,0.55); box-shadow: 0 0 0 4px rgba(18,128,122,0.12); }
        button {
            width: 100%; appearance: none; border: 0; border-radius: 999px; padding: 14px;
            font: inherit; font-weight: 700; cursor: pointer;
            background: linear-gradient(135deg, var(--teal), #0d5f61); color: #fff;
            transition: transform 0.18s ease, box-shadow 0.18s ease;
        }
        button:hover { transform: translateY(-1px); box-shadow: 0 12px 20px rgba(24,49,83,0.16); }
        .error { color: #a3483b; background: #ffe7e1; padding: 12px 16px; border-radius: 12px; margin-bottom: 16px; font-weight: 700; font-size: 14px; }
    </style>
</head>
<body>
    <div class="invite-card">
        <div class="invite-logo"><i class="fas fa-chart-pie"></i></div>
        <h1 class="invite-title">Join {{ $invitation['company_name'] ?? 'Skyare' }}</h1>
        <p class="invite-sub">You've been invited to join as <strong>{{ ucfirst($invitation['role_key'] ?? 'user') }}</strong>. Create your account below.</p>

        @if($errors->any())
            <div class="error">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="/invite/accept">
            <input type="hidden" name="_token" value="{{ \App\Middleware\CsrfMiddleware::token() }}">
            <input type="hidden" name="token" value="{{ $token }}">

            <label for="full_name">Full Name</label>
            <input type="text" id="full_name" name="full_name" placeholder="Your full name" required value="{{ old('full_name') }}">

            <label for="email">Email</label>
            <input type="email" id="email" value="{{ $invitation['email'] ?? '' }}" disabled>

            <label for="password">Password</label>
            <input type="password" id="password" name="password" placeholder="Min. 8 characters" required minlength="8">

            <label for="password_confirmation">Confirm Password</label>
            <input type="password" id="password_confirmation" name="password_confirmation" placeholder="Repeat password" required>

            <button type="submit"><i class="fas fa-check" style="margin-right:8px;"></i>Create Account</button>
        </form>
    </div>
</body>
</html>
