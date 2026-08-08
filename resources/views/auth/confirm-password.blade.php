<x-guest-layout>

    <img src="{{ \App\Models\Centre::brandLogo() }}" alt="{{ \App\Models\Centre::brandName() }}" class="auth-logo">

    <h1 class="auth-title">Parolni tasdiqlang</h1>
    <p class="auth-sub">Bu bo‘lim himoyalangan — davom etish uchun parolingizni kiriting</p>

    @if ($errors->any())
        <div class="alert alert-danger d-flex align-items-start gap-2 py-2" role="alert">
            <i class="bx bx-error-circle fs-5 lh-1 mt-1"></i>
            <div>{{ $errors->first() }}</div>
        </div>
    @endif

    <form method="POST" action="{{ route('password.confirm') }}" novalidate>
        @csrf

        <div class="mb-4">
            <label class="form-label" for="password">Parol</label>
            <div class="position-relative">
                <i class="bx bx-lock-alt position-absolute text-muted"
                   style="left: .75rem; top: 50%; transform: translateY(-50%); pointer-events: none;"></i>
                <input id="password" name="password" type="password"
                       class="form-control ps-5 pe-5 @error('password') is-invalid @enderror"
                       placeholder="••••••••" required autofocus autocomplete="current-password">
                <button type="button" id="togglePassword" class="btn-icon position-absolute"
                        style="right: .25rem; top: 50%; transform: translateY(-50%);"
                        aria-label="Parolni ko‘rsatish">
                    <i class="bx bx-show" id="togglePasswordIcon"></i>
                </button>
            </div>
        </div>

        <button type="submit" class="btn btn-primary btn-lg w-100">
            Tasdiqlash <i class="bx bx-right-arrow-alt ms-1"></i>
        </button>
    </form>

    <script>
        (function () {
            var btn = document.getElementById('togglePassword');
            var field = document.getElementById('password');
            var icon = document.getElementById('togglePasswordIcon');

            btn.addEventListener('click', function () {
                var hidden = field.type === 'password';
                field.type = hidden ? 'text' : 'password';
                icon.className = hidden ? 'bx bx-hide' : 'bx bx-show';
                btn.setAttribute('aria-label', hidden ? 'Parolni yashirish' : 'Parolni ko‘rsatish');
                field.focus();
            });
        })();
    </script>

</x-guest-layout>
