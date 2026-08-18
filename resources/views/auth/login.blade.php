<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>SynCore — Sign In</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="auth-body">
<div class="auth-split">
    <div class="auth-illustration">
        <div class="auth-graphic">
            <svg viewBox="0 0 340 300" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="Illustration of meeting participants connected around a central meeting icon">
                <g stroke="#ffffff55" stroke-width="1.5" stroke-dasharray="3 4">
                    <line x1="170" y1="150" x2="170" y2="72"/>
                    <line x1="170" y1="150" x2="100" y2="105"/>
                    <line x1="170" y1="150" x2="240" y2="105"/>
                    <line x1="170" y1="150" x2="95" y2="195"/>
                    <line x1="170" y1="150" x2="245" y2="195"/>
                    <line x1="170" y1="150" x2="170" y2="230"/>
                </g>
                <circle cx="170" cy="150" r="66" fill="#ffffff12"/>
                <g fill="#ffffff26">
                    <rect x="150" y="130" width="40" height="40" rx="11"/>
                </g>
                <g fill="#fff">
                    <rect x="159" y="141" width="22" height="18" rx="3" fill="none" stroke="#fff" stroke-width="1.8"/>
                    <line x1="159" y1="147" x2="181" y2="147" stroke="#fff" stroke-width="1.8"/>
                </g>
                <g>
                    <circle cx="170" cy="72" r="22" fill="#ffffff26"/>
                    <circle cx="180" cy="63" r="4.5" fill="#22c55e"/>
                    <path d="M170 66a6 6 0 100-12 6 6 0 000 12zm0 2c-5 0-10 2.6-10 6.6V78h20v-3.4c0-4-5-6.6-10-6.6z" fill="#fff"/>
                </g>
                <g>
                    <circle cx="100" cy="105" r="22" fill="#ffffff26"/>
                    <circle cx="110" cy="96" r="4.5" fill="#22c55e"/>
                    <path d="M100 99a6 6 0 100-12 6 6 0 000 12zm0 2c-5 0-10 2.6-10 6.6V111h20v-3.4c0-4-5-6.6-10-6.6z" fill="#fff"/>
                </g>
                <g>
                    <circle cx="240" cy="105" r="22" fill="#ffffff26"/>
                    <circle cx="250" cy="96" r="4.5" fill="#22c55e"/>
                    <path d="M240 99a6 6 0 100-12 6 6 0 000 12zm0 2c-5 0-10 2.6-10 6.6V111h20v-3.4c0-4-5-6.6-10-6.6z" fill="#fff"/>
                </g>
                <g>
                    <circle cx="95" cy="195" r="22" fill="#ffffff26"/>
                    <circle cx="85" cy="186" r="4.5" fill="#ef4444"/>
                    <path d="M95 189a6 6 0 100-12 6 6 0 000 12zm0 2c-5 0-10 2.6-10 6.6V201h20v-3.4c0-4-5-6.6-10-6.6z" fill="#fff"/>
                </g>
                <g>
                    <circle cx="245" cy="195" r="22" fill="#ffffff26"/>
                    <circle cx="255" cy="186" r="4.5" fill="#f59e0b"/>
                    <path d="M245 189a6 6 0 100-12 6 6 0 000 12zm0 2c-5 0-10 2.6-10 6.6V201h20v-3.4c0-4-5-6.6-10-6.6z" fill="#fff"/>
                </g>
                <g>
                    <circle cx="170" cy="230" r="22" fill="#ffffff26"/>
                    <circle cx="180" cy="221" r="4.5" fill="#22c55e"/>
                    <path d="M170 224a6 6 0 100-12 6 6 0 000 12zm0 2c-5 0-10 2.6-10 6.6V236h20v-3.4c0-4-5-6.6-10-6.6z" fill="#fff"/>
                </g>
                <g>
                    <rect x="18" y="248" width="86" height="40" rx="9" fill="#ffffff22" stroke="#ffffff40"/>
                    <text x="30" y="264" fill="#dbe6ff" font-size="9" font-family="DM Sans, Arial, sans-serif">Present</text>
                    <text x="30" y="279" fill="#fff" font-size="13" font-weight="700" font-family="DM Sans, Arial, sans-serif">15 / 20</text>
                    <rect x="30" y="282" width="60" height="3" rx="1.5" fill="#ffffff33"/>
                    <rect x="30" y="282" width="45" height="3" rx="1.5" fill="#22c55e"/>
                </g>
            </svg>
        </div>
        <div class="auth-copy">
            <h2>Smarter Meeting Attendance</h2>
            <p>Track participation, verify attendance with biometrics, and manage your organization's meetings — all in one place.</p>
            <div class="auth-stats">
                <div class="auth-stat"><b>2,400+</b><span>Meetings</span></div>
                <div class="auth-stat"><b>98.2%</b><span>Accuracy</span></div>
                <div class="auth-stat"><b>320</b><span>Users</span></div>
            </div>
        </div>
    </div>

    <div class="auth-form-panel">
        <div class="auth-brand">
            <div class="brand-mark">▪▪</div>
            <div>
                <div class="brand-name">SynCore</div>
                <small>Attendance System</small>
            </div>
        </div>

        <h1 class="auth-title">Welcome back</h1>
        <p class="auth-subtitle">Sign in to your account to continue.</p>

        @if ($errors->any())
            <div class="auth-alert" role="alert">{{ $errors->first() }}</div>
        @endif

        @if (session('status'))
            <div class="auth-alert auth-alert-success" role="status">{{ session('status') }}</div>
        @endif

        <div class="auth-tabs" role="tablist" aria-label="Sign in as">
            <button type="button" class="auth-tab active" data-auth-tab="admin" role="tab" aria-selected="true">Administrator</button>
            <button type="button" class="auth-tab" data-auth-tab="employee" role="tab" aria-selected="false">Employee</button>
        </div>

        <form action="{{ route('login.attempt') }}" method="POST" class="auth-form" novalidate>
            @csrf
            <input type="hidden" name="portal" id="auth-portal" value="admin">

            <div class="field">
                <label for="email">Email address</label>
                <input
                    id="email"
                    class="input @error('email') input-error @enderror"
                    type="email"
                    name="email"
                    value="{{ old('email') }}"
                    placeholder="sarah.chen@syncore.co"
                    autocomplete="username"
                    autofocus
                    required
                >
            </div>

            <div class="field">
                <div class="auth-field-row">
                    <label for="password">Password</label>
                    <button class="auth-link" type="button" data-toast="Contact your administrator to reset your password.">Forgot?</button>
                </div>
                <div class="auth-password-wrap">
                    <input
                        id="password"
                        class="input @error('password') input-error @enderror"
                        type="password"
                        name="password"
                        placeholder="••••••••"
                        autocomplete="current-password"
                        required
                    >
                    <button class="auth-password-toggle" type="button" data-toggle-password="password" aria-label="Show password">
                        <svg viewBox="0 0 24 24"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                    </button>
                </div>
            </div>

            <div class="auth-row">
                <label class="auth-remember">
                    <input type="checkbox" name="remember">
                    <span>Remember me for 30 days</span>
                </label>
            </div>

            <button class="button auth-submit" type="submit" data-loading-label="Signing in…">Sign In</button>
        </form>

        <p class="auth-footer">Having trouble? <button class="auth-link" type="button" data-toast="Contact your IT administrator for support.">Contact IT Support</button></p>
    </div>
</div>

<button class="auth-help" type="button" data-toast="Need help? Reach out to IT Support." aria-label="Help">?</button>
<div class="toast" id="app-toast" role="status"></div>
</body>
</html>