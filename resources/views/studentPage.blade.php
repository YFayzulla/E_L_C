@extends('template.master')

@section('title', 'Bosh sahifa')
@section('subtitle', 'O‘qishingiz bo‘yicha umumiy ko‘rinish')

@section('content')

    @php
        $rateTone = $attendance_rate >= 90 ? 'success' : ($attendance_rate >= 75 ? 'warning' : 'danger');
        $paid = $student->isPaidThisMonth();
    @endphp

    <div class="page-head">
        <div>
            <h4 class="mb-1">Assalomu alaykum, {{ $student->name }}</h4>
            <div class="page-sub">{{ now()->translatedFormat('d F Y') }}</div>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('attendance.index') }}" class="btn btn-outline-secondary">
                <i class="bx bx-calendar-check me-1"></i> Davomatim
            </a>
            <a href="{{ route('assessment.index') }}" class="btn btn-outline-secondary">
                <i class="bx bx-medal me-1"></i> Natijalarim
            </a>
        </div>
    </div>

    {{-- Summary tiles --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="card stat-card h-100">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <div class="stat-label">Davomat</div>
                        <div class="stat-value text-{{ $rateTone }}">{{ $attendance_rate }}%</div>
                    </div>
                    <span class="stat-icon is-{{ $rateTone }}"><i class="bx bx-calendar-check"></i></span>
                </div>
            </div>
        </div>

        <div class="col-6 col-lg-3">
            <div class="card stat-card h-100">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <div class="stat-label">Bu oy qoldirdim</div>
                        <div class="stat-value">{{ $absences_this_month }}</div>
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
                        <div class="stat-value">{{ $student->mark ?? '—' }}</div>
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

    {{-- O'zlashtirish: oylik dinamika va ko'rsatkich tarkibi --}}
    @include('partials.progress-card', [
        'progress' => $progress ?? [],
        'chartId'  => 'studentHomeProgress',
        'title'    => 'O‘zlashtirishim',
        'link'     => route('student.progress'),
    ])

    <div class="row g-4">

        {{-- Groups --}}
        <div class="col-lg-5">
            <div class="card h-100">
                <div class="card-header">Guruhlarim</div>
                <div class="card-body">
                    @forelse($student->groups as $group)
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
                                        <i class="bx bx-time me-1"></i> {{ $group->start_time }} – {{ $group->finish_time }}
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
                            <p class="mb-0">Siz hali guruhga biriktirilmagansiz.</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Test results --}}
        <div class="col-lg-7">
            <div class="card h-100">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <span>So‘nggi test natijalari</span>
                    <a href="{{ route('assessment.index') }}" class="btn btn-sm btn-outline-secondary">Barchasi</a>
                </div>
                @include('partials.test-results-table', ['tests' => $test_results, 'showGroup' => false])
            </div>
        </div>

        {{-- Absences --}}
        <div class="col-lg-7">
            <div class="card">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <span>Qoldirilgan darslar</span>
                    <a href="{{ route('attendance.index') }}" class="btn btn-sm btn-outline-secondary">Barchasi</a>
                </div>
                @if($absences->isEmpty())
                    <div class="empty-state">
                        <i class="bx bx-check-circle"></i>
                        <h6>Barcha darslarda qatnashgansiz</h6>
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
                            @foreach($absences as $item)
                                <tr>
                                    <td>{{ $item->created_at?->format('d.m.Y') }}</td>
                                    <td>{{ $item->group->name ?? '—' }}</td>
                                    <td class="text-muted">{{ $item->lesson->name ?? '—' }}</td>
                                    <td class="text-end">
                                        @if((int) $item->status === 2)
                                            <span class="badge bg-label-warning">Kechikdim</span>
                                        @else
                                            <span class="badge bg-label-danger">Kelmadim</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>

        {{-- Payments: faqat shu oyning holati. To'lov tarixi talabaga
             ko'rsatilmaydi — u admin ishi. --}}
        <div class="col-lg-5">
            <div class="card">
                <div class="card-header">To‘lovlarim</div>
                <div class="card-body py-2">
                    @include('partials.payment-status', ['student' => $student])
                </div>
            </div>
        </div>
    </div>

@endsection
