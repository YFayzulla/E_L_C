{{--
    Shown on every page while an admin is signed in as somebody else, so it is
    never possible to forget you are looking at another person's account and
    mistake their data for your own.
--}}
@if(session()->has(\App\Http\Controllers\ImpersonationController::SESSION_KEY))
    @php
        $impersonator = \App\Models\User::find(
            session(\App\Http\Controllers\ImpersonationController::SESSION_KEY)
        );
    @endphp

    <div class="alert alert-warning d-flex align-items-center gap-2 mb-4" role="status">
        <i class="bx bx-user-check fs-5 lh-1"></i>
        <div class="flex-grow-1">
            <strong>{{ auth()->user()->name }}</strong> hisobida ishlayapsiz
            @if($impersonator)
                <span class="text-muted">({{ $impersonator->name }} sifatida kirgansiz)</span>
            @endif
            — bu yerdagi barcha amallar shu foydalanuvchi nomidan bajariladi.
        </div>
        <form method="POST" action="{{ route('impersonate.stop') }}" class="flex-shrink-0">
            @csrf
            <button type="submit" class="btn btn-sm btn-warning">
                <i class="bx bx-log-out me-1"></i> O‘z hisobimga qaytish
            </button>
        </form>
    </div>
@endif
