<x-guest-layout>

    <img src="{{ asset('logos/main.png') }}" alt="ALPHA o'quv markazi" class="auth-logo">

    <h1 class="auth-title">Xush kelibsiz</h1>
    <p class="auth-sub">Davom etish uchun hisobingizga kiring</p>

    <x-auth-session-status class="mb-3" :status="session('status')"/>

    @if ($errors->any())
        <div class="alert alert-danger d-flex align-items-start gap-2 py-2" role="alert">
            <i class="bx bx-error-circle fs-5 lh-1 mt-1"></i>
            <div>{{ $errors->first() }}</div>
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}" novalidate>
        @csrf

        <div class="mb-3">
            <label class="form-label" for="login">Ism yoki telefon raqami</label>
            <div class="position-relative">
                <i class="bx bx-user position-absolute text-muted"
                   style="left: .75rem; top: 50%; transform: translateY(-50%); pointer-events: none;"></i>
                <input id="login" name="login" type="text" value="{{ old('login') }}"
                       class="form-control ps-5 @error('login') is-invalid @enderror"
                       placeholder="901234567" required autofocus autocomplete="username">
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label" for="password">Parol</label>
            <div class="position-relative">
                <i class="bx bx-lock-alt position-absolute text-muted"
                   style="left: .75rem; top: 50%; transform: translateY(-50%); pointer-events: none;"></i>
                <input id="password" name="password" type="password"
                       class="form-control ps-5 pe-5 @error('password') is-invalid @enderror"
                       placeholder="••••••••" required autocomplete="current-password">
                <button type="button" id="togglePassword" class="btn-icon position-absolute"
                        style="right: .25rem; top: 50%; transform: translateY(-50%);"
                        aria-label="Parolni ko'rsatish">
                    <i class="bx bx-show" id="togglePasswordIcon"></i>
                </button>
            </div>
        </div>

        <div class="form-check mb-4">
            <input class="form-check-input" type="checkbox" id="remember" name="remember"
                   {{ old('remember') ? 'checked' : '' }}>
            <label class="form-check-label" for="remember">Meni eslab qol</label>
        </div>

        <button type="submit" class="btn btn-primary btn-lg w-100">
            Kirish <i class="bx bx-right-arrow-alt ms-1"></i>
        </button>
    </form>

    <p class="auth-foot mb-0">
        Parolni unutdingizmi? O'quv markazi administratori bilan bog'laning.
    </p>

    <script>
        (function () {
            var btn = document.getElementById('togglePassword');
            var field = document.getElementById('password');
            var icon = document.getElementById('togglePasswordIcon');

            btn.addEventListener('click', function () {
                var hidden = field.type === 'password';
                field.type = hidden ? 'text' : 'password';
                icon.className = hidden ? 'bx bx-hide' : 'bx bx-show';
                btn.setAttribute('aria-label', hidden ? 'Parolni yashirish' : 'Parolni ko\'rsatish');
                field.focus();
            });
        })();
    </script>

</x-guest-layout>
