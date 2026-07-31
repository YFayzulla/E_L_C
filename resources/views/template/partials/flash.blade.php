@php
    $flashes = [];

    if (session('success')) {
        $flashes[] = ['type' => 'success', 'icon' => 'bx-check-circle', 'text' => session('success')];
    }
    if (session('error')) {
        $flashes[] = ['type' => 'danger', 'icon' => 'bx-error-circle', 'text' => session('error')];
    }
    if (session('warning')) {
        $flashes[] = ['type' => 'warning', 'icon' => 'bx-error', 'text' => session('warning')];
    }
    if (session('status') === 'profile-updated') {
        $flashes[] = ['type' => 'success', 'icon' => 'bx-check-circle', 'text' => "Profil ma'lumotlari yangilandi."];
    }
@endphp

@foreach($flashes as $flash)
    <div class="alert alert-{{ $flash['type'] }} alert-dismissible d-flex align-items-start gap-2 js-autohide"
         role="alert">
        <i class="bx {{ $flash['icon'] }} fs-5 lh-1 mt-1"></i>
        <div class="flex-grow-1">{{ $flash['text'] }}</div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Yopish"></button>
    </div>
@endforeach

@if ($errors->any())
    <div class="alert alert-danger alert-dismissible d-flex align-items-start gap-2" role="alert">
        <i class="bx bx-error-circle fs-5 lh-1 mt-1"></i>
        <div class="flex-grow-1">
            <div class="fw-semibold mb-1">Formani tekshiring</div>
            <ul class="mb-0 ps-3">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Yopish"></button>
    </div>
@endif

@if(count($flashes))
    @push('scripts')
        <script>
            setTimeout(function () {
                document.querySelectorAll('.js-autohide').forEach(function (el) {
                    if (window.bootstrap && bootstrap.Alert) {
                        bootstrap.Alert.getOrCreateInstance(el).close();
                    } else {
                        el.remove();
                    }
                });
            }, 6000);
        </script>
    @endpush
@endif
