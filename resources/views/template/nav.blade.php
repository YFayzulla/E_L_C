@php
    $user = auth()->user();
    $roleName = $user?->getRoleNames()->first();
    $roleLabels = [
        'admin'   => 'Administrator',
        'user'    => "O'qituvchi",
        'student' => 'Talaba',
        'parent'  => 'Ota-ona',
    ];
    $roleLabel = $roleLabels[$roleName] ?? 'Foydalanuvchi';

    // $fallbackTitle / $fallbackSubtitle come from template.master.
@endphp

<nav class="layout-navbar container-xxl navbar navbar-expand-xl navbar-detached align-items-center bg-navbar-theme"
     id="layout-navbar">

    <div class="layout-menu-toggle navbar-nav align-items-xl-center me-3 me-xl-0 d-xl-none">
        <a class="nav-item nav-link px-0 me-xl-4" href="javascript:void(0)" aria-label="Menyuni ochish">
            <i class="bx bx-menu bx-sm"></i>
        </a>
    </div>

    <div class="navbar-nav-right d-flex align-items-center w-100 gap-2" id="navbar-collapse">

        {{-- Page heading --}}
        <div class="d-none d-md-block me-auto">
            <div class="fw-semibold lh-sm" style="font-size: 1.02rem; color: var(--app-heading);">
                @hasSection('title')@yield('title')@else{{ $fallbackTitle ?? config('app.name') }}@endif
            </div>
            @hasSection('subtitle')
                <div class="text-muted" style="font-size: .8rem;">@yield('subtitle')</div>
            @elseif($fallbackSubtitle)
                <div class="text-muted" style="font-size: .8rem;">{{ $fallbackSubtitle }}</div>
            @endif
        </div>

        {{-- Quick filter for the table on the current page --}}
        <div class="nav-item d-flex align-items-center flex-grow-1 flex-md-grow-0 ms-md-auto"
             style="max-width: 320px;">
            <div class="position-relative w-100">
                <i class="bx bx-search position-absolute text-muted"
                   style="left: .7rem; top: 50%; transform: translateY(-50%); pointer-events: none;"></i>
                <input type="search" id="myInput" class="form-control nav-search ps-5" placeholder="Qidirish…  ( / )"
                       aria-label="Qidirish"/>
            </div>
        </div>

        <ul class="navbar-nav flex-row align-items-center ms-auto gap-1">

            @role('admin')
            {{-- Payment history date filter --}}
            <li class="nav-item dropdown d-none d-md-block">
                <button class="btn-icon" type="button" data-bs-toggle="dropdown" data-bs-auto-close="outside"
                        aria-expanded="false" title="Sana bo'yicha filtr">
                    <i class="bx bx-calendar"></i>
                </button>
                <div class="dropdown-menu dropdown-menu-end p-3" style="min-width: 290px;">
                    <div class="dropdown-header px-0 pt-0">To'lovlar tarixi</div>
                    <form action="{{ route('student.search') }}" method="post">
                        @csrf
                        <div class="mb-2">
                            <label class="form-label" for="nav-start-date">Boshlanish sanasi</label>
                            <input type="date" id="nav-start-date" class="form-control" name="start_date"/>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="nav-end-date">Tugash sanasi</label>
                            <input type="date" id="nav-end-date" class="form-control" name="end_date"/>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bx bx-search-alt me-1"></i> Qidirish
                        </button>
                    </form>
                </div>
            </li>
            @endrole

            {{-- Light / dark switch --}}
            <li class="nav-item">
                <button type="button" class="theme-toggle" aria-label="Mavzuni almashtirish" aria-pressed="false">
                    <i class="bx bx-moon icon-moon"></i>
                    <i class="bx bx-sun icon-sun"></i>
                </button>
            </li>

            {{-- Account --}}
            <li class="nav-item navbar-dropdown dropdown-user dropdown">
                <a class="nav-link dropdown-toggle hide-arrow d-flex align-items-center gap-2 px-1"
                   href="javascript:void(0);" data-bs-toggle="dropdown" aria-expanded="false">
                    <div class="avatar avatar-online">
                        @if($user?->photo)
                            <img src="{{ asset('storage/' . $user->photo) }}" alt="" class="w-px-40 h-auto rounded-circle"/>
                        @else
                            <span class="avatar-initial rounded-circle bg-label-primary">
                                {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($user?->name ?? '?', 0, 2)) }}
                            </span>
                        @endif
                    </div>
                    <span class="d-none d-lg-block text-start lh-sm">
                        <span class="d-block fw-semibold" style="font-size: .875rem; color: var(--app-text);">
                            {{ \Illuminate\Support\Str::limit($user?->name, 18) }}
                        </span>
                        <span class="d-block text-muted" style="font-size: .75rem;">{{ $roleLabel }}</span>
                    </span>
                </a>

                <ul class="dropdown-menu dropdown-menu-end">
                    <li class="px-2 py-2">
                        <div class="d-flex align-items-center gap-2">
                            <div class="avatar">
                                @if($user?->photo)
                                    <img src="{{ asset('storage/' . $user->photo) }}" alt="" class="w-px-40 h-auto rounded-circle"/>
                                @else
                                    <span class="avatar-initial rounded-circle bg-label-primary">
                                        {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($user?->name ?? '?', 0, 2)) }}
                                    </span>
                                @endif
                            </div>
                            <div class="flex-grow-1">
                                <span class="fw-semibold d-block">{{ $user?->name }}</span>
                                <small class="text-muted">{{ $roleLabel }}</small>
                            </div>
                        </div>
                    </li>
                    <li><div class="dropdown-divider"></div></li>
                    <li>
                        <a class="dropdown-item" href="{{ route('profile.edit') }}">
                            <i class="bx bx-user me-2"></i><span class="align-middle">Mening profilim</span>
                        </a>
                    </li>
                    <li>
                        <button type="button" class="dropdown-item theme-toggle w-100 text-start d-flex align-items-center"
                                style="width: 100%; height: auto; justify-content: flex-start;">
                            <i class="bx bx-moon icon-moon me-2"></i>
                            <i class="bx bx-sun icon-sun me-2"></i>
                            <span class="align-middle">Mavzuni almashtirish</span>
                        </button>
                    </li>
                    <li><div class="dropdown-divider"></div></li>
                    <li>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="dropdown-item text-danger">
                                <i class="bx bx-power-off me-2"></i><span class="align-middle">Chiqish</span>
                            </button>
                        </form>
                    </li>
                </ul>
            </li>
        </ul>
    </div>
</nav>
