{{--
    Shared shell for the branded error pages.
    Expects: $code, $message, $hint, $tone ('danger' | 'warning' | 'muted')
--}}
<!DOCTYPE html>
<html lang="uz" class="light-style" dir="ltr" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $code }} · {{ config('app.name', 'ALPHA') }}</title>

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

    <link rel="icon" type="image/png" href="{{ \App\Models\Centre::brandLogo() }}"/>
    <link href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@400;500;600;700&display=swap" rel="stylesheet"/>
    <link rel="stylesheet" href="{{ asset('assets/vendor/fonts/boxicons.css') }}"/>
    <link rel="stylesheet" href="{{ asset('assets/vendor/css/core.css') }}"/>
    <link rel="stylesheet" href="{{ asset('assets/vendor/css/theme-default.css') }}"/>
    <link rel="stylesheet" href="{{ asset('assets/css/theme.css') }}?v={{ filemtime(public_path('assets/css/theme.css')) }}"/>

    <style>
        html[data-theme] body.error-body {
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 1.5rem;
            text-align: center;
            background:
                radial-gradient(900px 520px at 50% -20%, var(--app-primary-soft), transparent 60%),
                var(--app-bg);
        }

        html[data-theme] .error-code {
            font-size: clamp(5rem, 18vw, 8.5rem);
            font-weight: 800;
            line-height: 1;
            letter-spacing: -0.05em;
            margin-bottom: .5rem;
        }

        html[data-theme] .error-logo {
            width: 130px;
            height: auto;
            margin-bottom: 2rem;
            filter: var(--app-logo-filter);
        }
    </style>
</head>

<body class="error-body">
<div>
    <img src="{{ \App\Models\Centre::brandLogo() }}" alt="{{ \App\Models\Centre::brandName() }}" class="error-logo">

    <div class="error-code text-{{ $tone }}">{{ $code }}</div>
    <h4 class="mb-2">{{ $message }}</h4>
    <p class="text-muted mb-4">{{ $hint }}</p>

    <div class="d-flex gap-2 justify-content-center flex-wrap">
        <a href="{{ url('/') }}" class="btn btn-primary">
            <i class="bx bx-home-alt me-1"></i> Bosh sahifaga qaytish
        </a>
        <button type="button" onclick="window.history.back()" class="btn btn-outline-secondary">
            <i class="bx bx-arrow-back me-1"></i> Ortga qaytish
        </button>
    </div>
</div>
</body>
</html>
