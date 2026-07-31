@extends('template.master')

@section('title', 'Davomatim')
@section('subtitle', 'Qoldirilgan va kechikkan darslaringiz')

@section('content')

    @php
        $rateTone = $rate >= 90 ? 'success' : ($rate >= 75 ? 'warning' : 'danger');
    @endphp

    <div class="page-head justify-content-end">
        <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary">
            <i class="bx bx-arrow-back me-1"></i> Bosh sahifa
        </a>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-4">
            <div class="card stat-card h-100">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div class="min-w-0">
                        <div class="stat-label">Davomat</div>
                        <div class="stat-value text-{{ $rateTone }}">{{ $rate }}%</div>
                    </div>
                    <span class="stat-icon is-{{ $rateTone }}"><i class="bx bx-calendar-check"></i></span>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-4">
            <div class="card stat-card h-100">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div class="min-w-0">
                        <div class="stat-label">Kelmagan</div>
                        <div class="stat-value text-danger">{{ $absentCount }}</div>
                    </div>
                    <span class="stat-icon is-danger"><i class="bx bx-x-circle"></i></span>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-4">
            <div class="card stat-card h-100">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div class="min-w-0">
                        <div class="stat-label">Kechikkan</div>
                        <div class="stat-value text-warning">{{ $lateCount }}</div>
                    </div>
                    <span class="stat-icon is-warning"><i class="bx bx-time-five"></i></span>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        @if($attendances->isEmpty())
            <div class="empty-state">
                <i class="bx bx-check-circle"></i>
                <h6>Barcha darslarda qatnashgansiz</h6>
                <p class="mb-0">Sizda qoldirilgan yoki kechikkan dars qayd etilmagan. Shunday davom eting!</p>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                    <tr>
                        <th style="width: 3rem;">#</th>
                        <th>Sana</th>
                        <th>Guruh</th>
                        <th>Dars</th>
                        <th class="text-end">Holat</th>
                    </tr>
                    </thead>
                    <tbody id="myTable">
                    @foreach($attendances as $attendance)
                        <tr>
                            <td class="text-muted">
                                {{ $attendances->firstItem() + $loop->index }}
                            </td>
                            <td>{{ $attendance->created_at?->format('d.m.Y') ?? '—' }}</td>
                            <td>
                                <span class="badge bg-label-info">{{ $attendance->group?->name ?? '—' }}</span>
                            </td>
                            <td class="text-muted">{{ $attendance->lesson?->name ?? '—' }}</td>
                            <td class="text-end">
                                @if((int) $attendance->status === 2)
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

            @if($attendances->hasPages())
                <div class="card-footer d-flex justify-content-end">
                    {{ $attendances->links() }}
                </div>
            @endif
        @endif
    </div>

@endsection
