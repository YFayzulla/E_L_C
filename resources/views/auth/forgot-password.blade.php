<x-guest-layout>

    <img src="{{ asset('logos/main.png') }}" alt="ALPHA o‘quv markazi" class="auth-logo">

    <h1 class="auth-title">Parolni unutdingizmi?</h1>
    <p class="auth-sub">Pochta manzilingizni kiriting — tiklash havolasini yuboramiz</p>

    <x-auth-session-status class="mb-3" :status="session('status')"/>

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

    <form method="POST" action="{{ route('password.email') }}" novalidate>
        @csrf

        <div class="mb-3">
            <label class="form-label" for="email">Pochta manzili</label>
            <div class="position-relative">
                <i class="bx bx-envelope position-absolute text-muted"
                   style="left: .75rem; top: 50%; transform: translateY(-50%); pointer-events: none;"></i>
                <input id="email" name="email" type="email" value="{{ old('email') }}"
                       class="form-control ps-5 @error('email') is-invalid @enderror"
                       placeholder="misol@pochta.uz" dir="ltr" required autofocus autocomplete="email">
            </div>
        </div>

        <button type="submit" class="btn btn-primary btn-lg w-100">
            <i class="bx bx-mail-send me-1"></i> Havolani yuborish
        </button>
    </form>

    <p class="text-center mt-3 mb-0">
        <a href="{{ route('login') }}" class="text-decoration-none">
            <i class="bx bx-chevron-left"></i> Kirish sahifasiga qaytish
        </a>
    </p>

    <p class="auth-foot mb-0">
        Hisobingizga pochta manzili biriktirilmagan bo‘lsa, parolni faqat o‘quv markazi
        administratori tiklay oladi.
    </p>

</x-guest-layout>
