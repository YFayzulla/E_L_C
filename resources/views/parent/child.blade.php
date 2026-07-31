@extends('template.master')

@section('title', $child->name)
@section('subtitle', 'Farzandingiz haqida to‘liq ma’lumot')

@section('content')

    @php
        $rateTone = $attendanceRate >= 90 ? 'success' : ($attendanceRate >= 75 ? 'warning' : 'danger');
        $paid = $child->isPaidThisMonth();
    @endphp

    <div class="page-head">
        <div class="d-flex align-items-center gap-3">
            <a href="{{ route('parent.index') }}" class="btn-icon" title="Orqaga">
                <i class="bx bx-arrow-back"></i>
            </a>
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
            <div>
                <h4 class="mb-1">{{ $child->name }}</h4>
                <div class="page-sub">
                    {{ $child->groups->pluck('name')->implode(', ') ?: 'Guruhga biriktirilmagan' }}
                </div>
            </div>
        </div>
    </div>

    {{-- Summary tiles --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="card stat-card h-100">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <div class="stat-label">Davomat</div>
                        <div class="stat-value text-{{ $rateTone }}">{{ $attendanceRate }}%</div>
                    </div>
                    <span class="stat-icon is-{{ $rateTone === 'danger' ? 'danger' : ($rateTone === 'warning' ? 'warning' : 'success') }}">
                        <i class="bx bx-calendar-check"></i>
                    </span>
                </div>
            </div>
        </div>

        <div class="col-6 col-lg-3">
            <div class="card stat-card h-100">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <div class="stat-label">Qoldirgan darslar</div>
                        <div class="stat-value">{{ $attendances->total() }}</div>
                    </div>
                    <span class="stat-icon is-warning"><i class="bx bx-time-five"></i></span>
                </div>
            </div>
        </div>

        <div class="col-6 col-lg-3">
            <div class="card stat-card h-100">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <div class="stat-label">Oxirgi ball</div>
                        <div class="stat-value">{{ $child->mark ?? '—' }}</div>
                    </div>
                    <span class="stat-icon is-info"><i class="bx bx-medal"></i></span>
                </div>
            </div>
        </div>

        <div class="col-6 col-lg-3">
            <div class="card stat-card h-100">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <div class="stat-label">To‘lov holati</div>
                        <div class="stat-value text-{{ $paid ? 'success' : 'danger' }}" style="font-size: 1.15rem;">
                            {{ $paid ? 'To‘langan' : 'To‘lanmagan' }}
                        </div>
                    </div>
                    <span class="stat-icon is-{{ $paid ? 'success' : 'danger' }}"><i class="bx bx-wallet"></i></span>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">

        {{-- Groups & teachers --}}
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header">Guruhlar va o‘qituvchilar</div>
                <div class="card-body">
                    @forelse($child->groups as $group)
                        <div class="d-flex align-items-start gap-3 {{ $loop->last ? '' : 'mb-3 pb-3 border-bottom' }}">
                            <span class="stat-icon"><i class="bx bx-group"></i></span>
                            <div class="flex-grow-1 min-w-0">
                                <h6 class="mb-1">{{ $group->name }}</h6>
                                <div class="text-muted" style="font-size: .82rem;">
                                    <i class="bx bx-user-voice me-1"></i>
                                    {{ $group->teachers->pluck('name')->implode(', ') ?: 'Biriktirilmagan' }}
                                </div>
                                @if($group->start_time || $group->finish_time)
                                    <div class="text-muted" style="font-size: .82rem;">
                                        <i class="bx bx-time me-1"></i>
                                        {{ $group->start_time }} – {{ $group->finish_time }}
                                    </div>
                                @endif
                                @if($group->room)
                                    <div class="text-muted" style="font-size: .82rem;">
                                        <i class="bx bx-door-open me-1"></i> {{ $group->room->room }}-xona
                                    </div>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="empty-state py-4">
                            <i class="bx bx-group"></i>
                            <h6>Guruh yo‘q</h6>
                            <p class="mb-0">Talaba hali guruhga biriktirilmagan.</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Test results --}}
        <div class="col-lg-8">
            <div class="card h-100">
                <div class="card-header">Test natijalari</div>
                @if($testResults->isEmpty())
                    <div class="empty-state">
                        <i class="bx bx-clipboard"></i>
                        <h6>Natija yo‘q</h6>
                        <p class="mb-0">Hozircha test natijalari kiritilmagan.</p>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                            <tr>
                                <th>Test</th>
                                <th>Maqsad</th>
                                <th>Ball</th>
                                <th class="text-end">Sana</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($testResults as $result)
                                @php $mark = (int) $result->get_mark; @endphp
                                <tr>
                                    <td>{{ $result->test_name ?? 'Test' }}</td>
                                    <td class="text-muted">{{ $result->for_what ?: '—' }}</td>
                                    <td>
                                        <span class="badge bg-label-{{ $mark >= 80 ? 'success' : ($mark >= 60 ? 'warning' : 'danger') }}">
                                            {{ $result->get_mark }}
                                        </span>
                                    </td>
                                    <td class="text-end text-muted">{{ $result->created_at?->format('d.m.Y') }}</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>

        {{-- Attendance --}}
        <div class="col-lg-7">
            <div class="card">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <span>Qoldirilgan darslar</span>
                    <span class="badge bg-label-warning">{{ $attendances->total() }}</span>
                </div>
                @if($attendances->isEmpty())
                    <div class="empty-state">
                        <i class="bx bx-check-circle"></i>
                        <h6>Barcha darslarda qatnashgan</h6>
                        <p class="mb-0">Qoldirilgan dars qayd etilmagan.</p>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                            <tr>
                                <th>Sana</th>
                                <th>Guruh</th>
                                <th>Dars</th>
                                <th class="text-end">Holat</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($attendances as $item)
                                <tr>
                                    <td>{{ $item->created_at?->format('d.m.Y') }}</td>
                                    <td>{{ $item->group->name ?? '—' }}</td>
                                    <td class="text-muted">{{ $item->lesson->name ?? '—' }}</td>
                                    <td class="text-end">
                                        @if((int) $item->status === 2)
                                            <span class="badge bg-label-warning">Kechikdi</span>
                                        @else
                                            <span class="badge bg-label-danger">Kelmadi</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="card-footer d-flex justify-content-end">
                        {{ $attendances->links() }}
                    </div>
                @endif
            </div>
        </div>

        {{-- Payments --}}
        <div class="col-lg-5">
            <div class="card">
                <div class="card-header">So‘nggi to‘lovlar</div>
                @if($payments->isEmpty())
                    <div class="empty-state">
                        <i class="bx bx-receipt"></i>
                        <h6>To‘lov yo‘q</h6>
                        <p class="mb-0">Hozircha to‘lov qayd etilmagan.</p>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                            <tr>
                                <th>Sana</th>
                                <th class="text-end">Summa</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($payments as $payment)
                                <tr>
                                    <td>
                                        {{ optional($payment->date ? \Carbon\Carbon::parse($payment->date) : $payment->created_at)->format('d.m.Y') }}
                                        @if($payment->description)
                                            <div class="text-muted" style="font-size: .78rem;">{{ $payment->description }}</div>
                                        @endif
                                    </td>
                                    <td class="text-end fw-semibold text-success">
                                        {{ number_format($payment->payment, 0, '.', ' ') }} so‘m
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>

@endsection
