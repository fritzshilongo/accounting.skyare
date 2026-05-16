<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - {{ config('app.name', 'Skyare Trading CC') }}</title>
    <link rel="stylesheet" href="/assets/css/legacy-style.css?v=2">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: Arial, sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); margin: 0; padding: 20px; min-height: 100vh; display: flex; flex-direction: column; align-items: center; justify-content: center; box-sizing: border-box; }
        .container { max-width: 400px; margin: 0 auto; background: white; padding: 40px; border-radius: 8px; }
        h2 { text-align: center; color: #333; }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
        .btn { width: 100%; padding: 12px; background: #667eea; color: white; border: none; cursor: pointer; border-radius: 4px; font-weight: bold; }
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

        <form method="POST" action="/reset-password">
            <input type="hidden" name="_token" value="{{ $token }}">

            <input type="hidden" id="token" name="token" value="{{ $tokenValue ?? '' }}">

            <div class="form-group">
                <label for="new_password">New Password</label>
                <input type="password" id="new_password" name="new_password" required>
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
            </div>

            <button type="submit" class="btn">Reset Password</button>
        </form>

        <p style="text-align: center; margin-top: 20px;">
            <a href="/login" style="color: #667eea; text-decoration: none;">Back to Login</a>
        </p>
    </div>

    @include('auth.partials.footer')
</body>
</html>
