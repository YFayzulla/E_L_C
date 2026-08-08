<x-guest-layout>

    <img src="{{ \App\Models\Centre::brandLogo() }}" alt="{{ \App\Models\Centre::brandName() }}" class="auth-logo">

    <h1 class="auth-title">Pochtangizni tasdiqlang</h1>
    <p class="auth-sub">Hisobingizni faollashtirish uchun oxirgi qadam</p>

    @if (session('success'))
        <div class="alert alert-success d-flex align-items-start gap-2 py-2" role="status">
            <i class="bx bx-check-circle fs-5 lh-1 mt-1"></i>
            <div>{{ session('success') }}</div>
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger d-flex align-items-start gap-2 py-2" role="alert">
            <i class="bx bx-error-circle fs-5 lh-1 mt-1"></i>
            <div>{{ session('error') }}</div>
        </div>
    @endif

    <x-auth-session-status class="mb-3" :status="session('status')"/>

    <div class="d-flex align-items-center gap-2 p-3 mb-3"
         style="background: var(--app-surface-2); border: 1px solid var(--app-border); border-radius: var(--app-radius);">
        <i class="bx bx-envelope fs-4 lh-1" style="color: var(--app-primary);"></i>
        <div class="min-w-0">
            <div class="text-muted" style="font-size: .75rem;">Xat yuborilgan manzil</div>
            <div class="fw-semibold text-truncate" dir="ltr">{{ auth()->user()->email }}</div>
        </div>
    </div>

    <p class="mb-4" style="font-size: .875rem; color: var(--app-text-muted);">
        Ushbu manzilga tasdiqlash havolasi yuborildi. Xatni oching va
        <strong>Tasdiqlash</strong> tugmasini bosing. Xat ko‘rinmasa, «Spam» papkasini ham
        tekshiring.
    </p>

    <form method="POST" action="{{ route('verification.send') }}" class="mb-2">
        @csrf
        <button type="submit" class="btn btn-primary btn-lg w-100">
            <i class="bx bx-mail-send me-1"></i> Xatni qayta yuborish
        </button>
    </form>

    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit" class="btn btn-outline-secondary w-100">
            <i class="bx bx-log-out me-1"></i> Chiqish
        </button>
    </form>

    <p class="auth-foot mb-0">
        Manzil noto‘g‘ri kiritilganmi? O‘quv markazi administratori bilan bog‘laning.
    </p>

</x-guest-layout>
