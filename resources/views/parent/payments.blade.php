@extends('template.master')

@section('title', 'To‘lovlar')
@section('subtitle', 'Farzandlaringiz bo‘yicha to‘lov holati')

@section('content')

    <div class="page-head">
        <div class="page-sub">{{ now()->translatedFormat('F Y') }} holatiga ko‘ra</div>
        <a href="{{ route('parent.index') }}" class="btn btn-outline-secondary">
            <i class="bx bx-arrow-back me-1"></i> Farzandlarim
        </a>
    </div>

    {{-- Faqat shu oyning holati. To'lovlar tarixi ota-onaga ko'rsatilmaydi. --}}
    <div class="row g-3">
        @forelse($children as $child)
            <div class="col-md-6 col-xl-4">
                <div class="card h-100">
                    <div class="card-header d-flex align-items-center justify-content-between gap-2">
                        <span class="fw-semibold text-truncate">{{ $child->name }}</span>
                        <span class="stat-icon is-{{ $child->currentDebt() > 0 ? 'danger' : 'success' }}">
                            <i class="bx bx-wallet"></i>
                        </span>
                    </div>
                    <div class="card-body py-2">
                        @include('partials.payment-status', ['student' => $child])
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="card">
                    <div class="empty-state">
                        <i class="bx bx-user"></i>
                        <h6>Farzand biriktirilmagan</h6>
                        <p class="mb-0">O‘quv markazi administratoriga murojaat qiling.</p>
                    </div>
                </div>
            </div>
        @endforelse
    </div>

@endsection
