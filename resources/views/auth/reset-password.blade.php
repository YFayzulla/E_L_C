<x-guest-layout>

    <img src="{{ asset('logos/main.png') }}" alt="ALPHA o‘quv markazi" class="auth-logo">

    <h1 class="auth-title">Yangi parol</h1>
    <p class="auth-sub">Hisobingiz uchun yangi parol o‘rnating</p>

    @if (session('error'))
        <div class="alert alert-danger d-flex align-items-start gap-2 py-2" role="alert">
            <i class="bx bx-error-circle fs-5 lh-1 mt-1"></i>
            <div>{{ session('error') }}</div>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger d-flex align-items-start gap-2 py-2" role="alert">
            <i class="bx bx-error-circle fs-5 lh-1 mt-1"></i>
            <div>{{ $errors->first() }}</div>
        </div>
    @endif

    <form method="POST" action="{{ route('password.store') }}" novalidate>
        @csrf

        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <div class="mb-3">
            <label class="form-label" for="email">Pochta manzili</label>
            <div class="position-relative">
                <i class="bx bx-envelope position-absolute text-muted"
                   style="left: .75rem; top: 50%; transform: translateY(-50%); pointer-events: none;"></i>
                <input id="email" name="email" type="email" value="{{ old('email', $request->email) }}"
                       class="form-control ps-5 @error('email') is-invalid @enderror"
                       placeholder="misol@pochta.uz" dir="ltr" required autofocus autocomplete="email">
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label" for="password">Yangi parol</label>
            <div class="position-relative">
                <i class="bx bx-lock-alt position-absolute text-muted"
                   style="left: .75rem; top: 50%; transform: translateY(-50%); pointer-events: none;"></i>
                <input id="password" name="password" type="password"
                       class="form-control ps-5 @error('password') is-invalid @enderror"
                       placeholder="••••••••" required autocomplete="new-password">
            </div>
        </div>

        <div class="mb-4">
            <label class="form-label" for="password_confirmation">Parolni takrorlang</label>
            <div class="position-relative">
                <i class="bx bx-lock-alt position-absolute text-muted"
                   style="left: .75rem; top: 50%; transform: translateY(-50%); pointer-events: none;"></i>
                <input id="password_confirmation" name="password_confirmation" type="password"
                       class="form-control ps-5 @error('password_confirmation') is-invalid @enderror"
                       placeholder="••••••••" required autocomplete="new-password">
            </div>
        </div>

        <button type="submit" class="btn btn-primary btn-lg w-100">
            Parolni saqlash <i class="bx bx-right-arrow-alt ms-1"></i>
        </button>
    </form>

    <p class="text-center mt-3 mb-0">
        <a href="{{ route('login') }}" class="text-decoration-none">
            <i class="bx bx-chevron-left"></i> Kirish sahifasiga qaytish
        </a>
    </p>

</x-guest-layout>
