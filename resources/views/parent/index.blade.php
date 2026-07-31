@extends('template.master')

@section('title', 'Farzandlarim')
@section('subtitle', 'Farzandlaringiz o‘qishi bo‘yicha umumiy ko‘rinish')

@section('content')

    <div class="page-head">
        <div>
            <h4 class="mb-1">Assalomu alaykum, {{ auth()->user()->name }}</h4>
            <div class="page-sub">
                {{ $children->count() }} ta farzand ro‘yxatga olingan · {{ now()->translatedFormat('d F Y') }}
            </div>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('parent.attendance') }}" class="btn btn-outline-secondary">
                <i class="bx bx-calendar-check me-1"></i> Davomat
            </a>
            <a href="{{ route('parent.payments') }}" class="btn btn-outline-secondary">
                <i class="bx bx-wallet me-1"></i> To‘lovlar
            </a>
        </div>
    </div>

    @if($children->isEmpty())
        <div class="card">
            <div class="empty-state">
                <i class="bx bx-user-x"></i>
                <h6>Farzand biriktirilmagan</h6>
                <p class="mb-0">Hisobingizga hali birorta talaba bog‘lanmagan.<br>
                    Iltimos, o‘quv markazi administratori bilan bog‘laning.</p>
            </div>
        </div>
    @else
        <div class="row g-4">
            @foreach($children as $child)
                @php
                    $rate = $child->attendance_rate;
                    $rateTone = $rate >= 90 ? 'success' : ($rate >= 75 ? 'warning' : 'danger');
                    $paid = $child->isPaidThisMonth();
                @endphp

                <div class="col-xl-4 col-md-6">
                    <div class="card h-100 card-hover">
                        <div class="card-body d-flex flex-column">

                            <div class="d-flex align-items-center gap-3 mb-3">
                                <div class="avatar avatar-lg">
                                    @if($child->photo)
                                        <img src="{{ asset('storage/' . $child->photo) }}" alt=""
                                             class="rounded-circle w-100 h-100" style="object-fit: cover;">
                                    @else
                                        <span class="avatar-initial rounded-circle bg-label-primary">
                                            {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($child->name, 0, 2)) }}
                                        </span>
                                    @endif
                                </div>
                                <div class="flex-grow-1 min-w-0">
                                    <h6 class="mb-0 text-truncate">{{ $child->name }}</h6>
                                    <small class="text-muted text-truncate d-block">
                                        {{ $child->groups->pluck('name')->implode(', ') ?: 'Guruhga biriktirilmagan' }}
                                    </small>
                                </div>
                            </div>

                            <div class="row g-2 mb-3">
                                <div class="col-4">
                                    <div class="p-2 rounded text-center" style="background: var(--app-surface-2);">
                                        <div class="fw-bold text-{{ $rateTone }}" style="font-size: 1.15rem;">{{ $rate }}%</div>
                                        <small class="text-muted" style="font-size: .7rem;">Davomat</small>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="p-2 rounded text-center" style="background: var(--app-surface-2);">
                                        <div class="fw-bold" style="font-size: 1.15rem;">{{ $child->absence_count }}</div>
                                        <small class="text-muted" style="font-size: .7rem;">Bu oy qoldirdi</small>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="p-2 rounded text-center" style="background: var(--app-surface-2);">
                                        <div class="fw-bold" style="font-size: 1.15rem;">{{ $child->mark ?? '—' }}</div>
                                        <small class="text-muted" style="font-size: .7rem;">Oxirgi ball</small>
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <span class="text-muted" style="font-size: .85rem;">Oylik to‘lov</span>
                                <span class="badge bg-label-{{ $paid ? 'success' : 'danger' }}">
                                    {{ $paid ? 'To‘langan' : 'To‘lanmagan' }}
                                </span>
                            </div>

                            @if($child->deptStudent && $child->deptStudent->dept > 0)
                                <div class="d-flex align-items-center justify-content-between mb-3">
                                    <span class="text-muted" style="font-size: .85rem;">Qarzdorlik</span>
                                    <span class="fw-semibold text-danger">
                                        {{ number_format($child->deptStudent->dept, 0, '.', ' ') }} so‘m
                                    </span>
                                </div>
                            @endif

                            <a href="{{ route('parent.child', $child->id) }}"
                               class="btn btn-primary w-100 mt-auto">
                                Batafsil ko‘rish <i class="bx bx-right-arrow-alt ms-1"></i>
                            </a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

@endsection
