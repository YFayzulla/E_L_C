<!DOCTYPE html>
<html lang="uz" class="light-style" dir="ltr" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'ALPHA') }}</title>

    {{-- Theme before first paint — same contract as the app shell. --}}
    <script>
        (function () {
            try {
                var saved = localStorage.getItem('alpha-theme');
                var theme = saved || (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
                var el = document.documentElement;
                el.setAttribute('data-theme', theme);
                el.setAttribute('data-bs-theme', theme);
                el.classList.toggle('dark-style', theme === 'dark');
                el.classList.toggle('light-style', theme !== 'dark');
            } catch (e) {
            }
        })();
    </script>

    <link rel="icon" type="image/png" href="{{ asset('logos/main.png') }}"/>

    <link rel="preconnect" href="https://fonts.googleapis.com"/>
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin/>
    <link href="https://fonts.googleapis.com/css2?family=Public+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700&display=swap"
          rel="stylesheet"/>

    <link rel="stylesheet" href="{{ asset('assets/vendor/fonts/boxicons.css') }}"/>
    <link rel="stylesheet" href="{{ asset('assets/vendor/css/core.css') }}"/>
    <link rel="stylesheet" href="{{ asset('assets/vendor/css/theme-default.css') }}"/>
    <link rel="stylesheet" href="{{ asset('assets/css/theme.css') }}?v={{ filemtime(public_path('assets/css/theme.css')) }}"/>

    <style>
        html[data-theme] body.auth-body {
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 1.5rem;
            background:
                radial-gradient(1100px 600px at 12% -10%, var(--app-primary-soft), transparent 60%),
                radial-gradient(900px 520px at 105% 110%, var(--app-info-soft), transparent 55%),
                var(--app-bg);
        }

        html[data-theme] .auth-card {
            width: 100%;
            max-width: 26rem;
            background: var(--app-surface);
            border: 1px solid var(--app-border);
            border-radius: var(--app-radius-lg);
            box-shadow: var(--app-shadow-lg);
            padding: 2.25rem 2rem;
        }

        html[data-theme] .auth-logo {
            display: block;
            width: 160px;
            max-width: 60%;
            height: auto;
            margin: 0 auto 1.25rem;
            filter: var(--app-logo-filter);
        }

        html[data-theme] .auth-title {
            text-align: center;
            font-size: 1.25rem;
            margin-bottom: .25rem;
        }

        html[data-theme] .auth-sub {
            text-align: center;
            color: var(--app-text-muted);
            font-size: .875rem;
            margin-bottom: 1.75rem;
        }

        html[data-theme] .auth-foot {
            text-align: center;
            color: var(--app-text-subtle);
            font-size: .78rem;
            margin-top: 1.5rem;
        }

        html[data-theme] .auth-theme-toggle {
            position: fixed;
            top: 1rem;
            right: 1rem;
            background: var(--app-surface);
            border: 1px solid var(--app-border);
            box-shadow: var(--app-shadow-xs);
        }
    </style>
</head>

<body class="auth-body">

<button type="button" class="theme-toggle auth-theme-toggle" aria-label="Mavzuni almashtirish" aria-pressed="false">
    <i class="bx bx-moon icon-moon"></i>
    <i class="bx bx-sun icon-sun"></i>
</button>

<main class="auth-card">
    {{ $slot }}
</main>

<script src="{{ asset('assets/js/theme.js') }}?v={{ filemtime(public_path('assets/js/theme.js')) }}"></script>
</body>
</html>
