{{--
    Reminds a signed-in user to confirm the address on their account.
    Never blocks navigation — enforcement (when enabled) lives in the
    `verified.role` middleware, not here.
--}}
@auth
    @if(auth()->user()->needsEmailVerification())
        <div class="alert alert-warning alert-dismissible d-flex align-items-start gap-2" role="alert">
            <i class="bx bx-envelope fs-5 lh-1 mt-1"></i>
            <div class="flex-grow-1">
                <div class="fw-semibold mb-1">Pochta manzilingiz tasdiqlanmagan</div>
                <div class="mb-2" style="font-size: .875rem;">
                    <span dir="ltr">{{ auth()->user()->email }}</span> manziliga yuborilgan xatdagi
                    havolani bosing.
                </div>
                <form method="POST" action="{{ route('verification.send') }}" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-warning">
                        <i class="bx bx-mail-send me-1"></i> Xatni qayta yuborish
                    </button>
                </form>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Yopish"></button>
        </div>
    @endif
@endauth
