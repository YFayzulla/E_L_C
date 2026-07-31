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

    {{-- Current status per child --}}
    <div class="row g-3 mb-4">
        @foreach($children as $child)
            @php $paid = $child->isPaidThisMonth(); @endphp
            <div class="col-md-6 col-xl-4">
                <div class="card h-100">
                    <div class="card-body d-flex align-items-center justify-content-between gap-3">
                        <div class="min-w-0">
                            <h6 class="mb-1 text-truncate">{{ $child->name }}</h6>
                            <span class="badge bg-label-{{ $paid ? 'success' : 'danger' }}">
                                {{ $paid ? 'To‘langan' : 'To‘lanmagan' }}
                            </span>
                            @if($child->deptStudent && $child->deptStudent->dept > 0)
                                <div class="text-danger fw-semibold mt-2" style="font-size: .875rem;">
                                    Qarz: {{ number_format($child->deptStudent->dept, 0, '.', ' ') }} so‘m
                                </div>
                            @endif
                        </div>
                        <span class="stat-icon is-{{ $paid ? 'success' : 'danger' }}">
                            <i class="bx bx-wallet"></i>
                        </span>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="card">
        <div class="card-header">To‘lovlar tarixi</div>
        @if($payments->isEmpty())
            <div class="empty-state">
                <i class="bx bx-receipt"></i>
                <h6>To‘lov yo‘q</h6>
                <p class="mb-0">Hozircha to‘lov qayd etilmagan.</p>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover" id="myTable">
                    <thead>
                    <tr>
                        <th>Sana</th>
                        <th>Farzand</th>
                        <th>Izoh</th>
                        <th class="text-end">Summa</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($payments as $payment)
                        <tr>
                            <td>{{ optional($payment->date ? \Carbon\Carbon::parse($payment->date) : $payment->created_at)->format('d.m.Y') }}</td>
                            <td class="fw-semibold">{{ $payment->user->name ?? $payment->name }}</td>
                            <td class="text-muted">{{ $payment->description ?: '—' }}</td>
                            <td class="text-end fw-semibold text-success">
                                {{ number_format($payment->payment, 0, '.', ' ') }} so‘m
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            <div class="card-footer d-flex justify-content-end">
                {{ $payments->links() }}
            </div>
        @endif
    </div>

@endsection
