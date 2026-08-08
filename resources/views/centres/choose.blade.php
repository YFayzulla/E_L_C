<x-guest-layout>

    <img src="{{ asset('logos/main.png') }}" alt="" class="auth-logo">

    <h1 class="auth-title">Qaysi markazga kirasiz?</h1>
    <p class="auth-sub">
        Siz bir nechta o‘quv markazida ishlaysiz. Birini tanlang — istalgan
        paytda shu sahifaga qaytib, boshqasiga o‘tishingiz mumkin.
    </p>

    @if($centres->isEmpty())

        <div class="alert alert-warning mb-3">
            <h6 class="alert-heading mb-1">Hech qaysi markazga biriktirilmagansiz</h6>
            <p class="mb-0" style="font-size: .86rem;">
                Hisobingiz bor, lekin hali birorta o‘quv markaziga qo‘shilmagansiz.
                Markaz administratori bilan bog‘laning.
            </p>
        </div>

    @else

        <div class="d-grid gap-2 mb-3">
            @foreach($centres as $centre)
                {{-- Boshqa hostga o'tish: route() joriy hostdan URL quradi,
                     shuning uchun markazning o'z manzili aniq beriladi. --}}
                <a href="{{ $centre->url('/') }}"
                   class="card card-hover text-decoration-none p-3 d-flex flex-row align-items-center gap-3">
                    @if($centre->logoUrl())
                        <img src="{{ $centre->logoUrl() }}" alt=""
                             style="width: 40px; height: 40px; object-fit: contain; flex-shrink: 0;">
                    @else
                        <span class="avatar avatar-sm flex-shrink-0">
                            <span class="avatar-initial rounded-circle bg-label-primary">
                                {{ mb_strtoupper(mb_substr($centre->name, 0, 1)) }}
                            </span>
                        </span>
                    @endif

                    <span class="flex-grow-1 text-start" style="min-width: 0;">
                        <span class="d-block fw-semibold text-truncate">{{ $centre->name }}</span>
                        <span class="d-block text-muted text-truncate" style="font-size: .78rem;">
                            {{ $centre->host() }}
                        </span>
                    </span>

                    <i class="bx bx-chevron-right text-muted flex-shrink-0"></i>
                </a>
            @endforeach
        </div>

    @endif

    @if($suspended->isNotEmpty())
        <div class="alert alert-secondary" style="font-size: .82rem;">
            <i class="bx bx-pause-circle me-1"></i>
            Vaqtincha ochiq emas:
            {{ $suspended->map(fn($c) => $c->name . ' (' . $c->statusLabel() . ')')->implode(', ') }}
        </div>
    @endif

    <form method="POST" action="{{ route('logout') }}" class="text-center mt-3">
        @csrf
        <button type="submit" class="btn btn-link text-muted p-0" style="font-size: .84rem;">
            Boshqa hisob bilan kirish
        </button>
    </form>

</x-guest-layout>
