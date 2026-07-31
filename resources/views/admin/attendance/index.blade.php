@extends('template.master')

@section('title', 'Davomat')
@section('subtitle', 'Barcha guruhlar bo‘yicha oylik davomat')

@section('content')

    @php
        $monthLabel = \Carbon\Carbon::createFromDate($year, $month, 1)
            ->locale('uz_Latn')->translatedFormat('F Y');
        $avgTone = $averageRate === null
            ? 'info'
            : ($averageRate >= 90 ? 'success' : ($averageRate >= 75 ? 'warning' : 'danger'));

        // Oxirgi 24 oy. Bookmark qilingan eskiroq oy ham ro'yxatdan tushib qolmaydi.
        $monthOptions = collect(range(0, 23))->map(fn($i) => now()->startOfMonth()->subMonths($i));
        if (! $monthOptions->contains(fn($m) => $m->format('Y-m') === $date)) {
            $monthOptions->prepend(\Carbon\Carbon::createFromDate($year, $month, 1));
        }
    @endphp

    <div class="page-head">
        <div class="page-sub">{{ $monthLabel }} · {{ count($rows) }} ta guruh</div>
        <a href="{{ route('attendance.log') }}" class="btn btn-outline-secondary">
            <i class="bx bx-list-ul me-1"></i> Qoldirishlar jurnali
        </a>
    </div>

    {{-- Xulosa --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="card stat-card h-100">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div class="min-w-0">
                        <div class="stat-label">Jami darslar</div>
                        <div class="stat-value">{{ $totalLessons }}</div>
                    </div>
                    <span class="stat-icon is-info"><i class="bx bx-calendar"></i></span>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card stat-card h-100">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div class="min-w-0">
                        <div class="stat-label">Jami kelmagan</div>
                        <div class="stat-value text-danger">{{ $totalAbsent }}</div>
                    </div>
                    <span class="stat-icon is-danger"><i class="bx bx-x-circle"></i></span>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card stat-card h-100">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div class="min-w-0">
                        <div class="stat-label">Jami kechikkan</div>
                        <div class="stat-value text-warning">{{ $totalLate }}</div>
                    </div>
                    <span class="stat-icon is-warning"><i class="bx bx-time-five"></i></span>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card stat-card h-100">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div class="min-w-0">
                        <div class="stat-label">O‘rtacha davomat</div>
                        <div class="stat-value text-{{ $avgTone }}">
                            {{ $averageRate === null ? '—' : $averageRate . '%' }}
                        </div>
                    </div>
                    <span class="stat-icon is-{{ $avgTone }}"><i class="bx bx-calendar-check"></i></span>
                </div>
            </div>
        </div>
    </div>

    {{-- Filtrlar --}}
    <div class="filter-card">
        <form method="GET" action="{{ route('attendance.overview') }}" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label" for="date">Oy</label>
                <select id="date" name="date" class="form-select">
                    @foreach($monthOptions as $option)
                        <option value="{{ $option->format('Y-m') }}" @selected($option->format('Y-m') === $date)>
                            {{ $option->locale('uz_Latn')->translatedFormat('F Y') }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-3">
                <label class="form-label" for="group_id">Guruh</label>
                <select id="group_id" name="group_id" class="form-select">
                    <option value="">Barchasi</option>
                    @foreach($groupOptions as $option)
                        <option value="{{ $option->id }}" @selected($groupId === (int) $option->id)>{{ $option->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-3">
                <label class="form-label" for="teacher_id">O‘qituvchi</label>
                <select id="teacher_id" name="teacher_id" class="form-select">
                    <option value="">Barchasi</option>
                    @foreach($teacherOptions as $option)
                        <option value="{{ $option->id }}" @selected($teacherId === (int) $option->id)>{{ $option->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-2">
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary flex-grow-1">
                        <i class="bx bx-filter-alt me-1"></i> Filtr
                    </button>
                    <a href="{{ route('attendance.overview') }}" class="btn btn-outline-secondary" title="Tozalash">
                        <i class="bx bx-reset"></i>
                    </a>
                </div>
            </div>
        </form>
    </div>

    <div class="card">
        @if(empty($rows))
            <div class="empty-state">
                <i class="bx bx-calendar-x"></i>
                <h6>Guruh topilmadi</h6>
                <p class="mb-0">Tanlangan filtrlarga mos guruh yo‘q. Filtrlarni o‘zgartirib ko‘ring.</p>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                    <tr>
                        <th style="width: 3rem;">#</th>
                        <th>Guruh</th>
                        <th>O‘qituvchi(lar)</th>
                        <th class="text-center">Talabalar</th>
                        <th class="text-center">O‘tilgan darslar</th>
                        <th class="text-center">Kelmagan</th>
                        <th class="text-center">Kechikkan</th>
                        <th style="min-width: 9rem;">Davomat</th>
                        <th class="text-end">Amal</th>
                    </tr>
                    </thead>
                    <tbody id="myTable">
                    @foreach($rows as $row)
                        @php
                            $tone = $row['rate'] === null
                                ? 'secondary'
                                : ($row['rate'] >= 90 ? 'success' : ($row['rate'] >= 75 ? 'warning' : 'danger'));
                        @endphp
                        <tr>
                            <td class="text-muted">{{ $loop->iteration }}</td>
                            <td class="fw-semibold">{{ $row['group']->name }}</td>
                            <td class="text-muted">{{ $row['teachers'] ?: '— biriktirilmagan' }}</td>
                            <td class="text-center">{{ $row['members'] }}</td>
                            <td class="text-center">
                                <span class="badge bg-label-primary">{{ $row['lessons'] }}</span>
                            </td>
                            <td class="text-center">
                                @if($row['absent'] > 0)
                                    <span class="badge bg-label-danger">{{ $row['absent'] }}</span>
                                @else
                                    <span class="text-muted">0</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($row['late'] > 0)
                                    <span class="badge bg-label-warning">{{ $row['late'] }}</span>
                                @else
                                    <span class="text-muted">0</span>
                                @endif
                            </td>
                            <td>
                                @if($row['rate'] === null)
                                    <span class="text-muted">— dars yo‘q</span>
                                @else
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="fw-semibold text-{{ $tone }}" style="min-width: 2.75rem;">
                                            {{ $row['rate'] }}%
                                        </span>
                                        <div class="score-bar is-{{ $tone }} flex-grow-1">
                                            <span style="width: {{ $row['rate'] }}%;"></span>
                                        </div>
                                    </div>
                                @endif
                            </td>
                            <td class="text-end">
                                <a href="{{ route('group.attendance', ['id' => $row['group']->id, 'date' => $date]) }}"
                                   class="btn btn-sm btn-outline-secondary" title="Jadvalni ochish">
                                    <i class="bx bx-table"></i>
                                </a>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

@endsection
