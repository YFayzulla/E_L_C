@extends('template.master')

@section('title', 'Boshqaruv paneli')
@section('subtitle', now()->translatedFormat('d F Y'))

@section('content')

    {{-- ================================ ADMIN ================================ --}}
    @role('admin')

    {{-- Heading lives in the top bar; this row only carries actions. --}}
    <div class="page-head justify-content-end">
        <a href="{{ route('student.create') }}" class="btn btn-primary">
            <i class="bx bx-plus me-1"></i> Yangi talaba
        </a>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-6 col-xl-3">
            <div class="card stat-card h-100 card-hover">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <div class="stat-label">Talabalar</div>
                        <div class="stat-value">{{ $number_of_students }}</div>
                        <small class="text-muted">Jami faol</small>
                    </div>
                    <span class="stat-icon"><i class="bx bx-user-voice"></i></span>
                </div>
            </div>
        </div>

        <div class="col-6 col-xl-3">
            <div class="card stat-card h-100 card-hover">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <div class="stat-label">Bugungi tushum</div>
                        <div class="stat-value">{{ number_format($daily_income, 0, '.', ' ') }}</div>
                        <small class="text-muted">so‘m</small>
                    </div>
                    <span class="stat-icon is-success"><i class="bx bx-trending-up"></i></span>
                </div>
            </div>
        </div>

        <div class="col-6 col-xl-3">
            <div class="card stat-card h-100 card-hover">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <div class="stat-label">Bugun kelmadi</div>
                        <div class="stat-value">{{ count($today_attendances) }}</div>
                        <small class="text-muted">talaba</small>
                    </div>
                    <span class="stat-icon is-warning"><i class="bx bx-user-x"></i></span>
                </div>
            </div>
        </div>

        <div class="col-6 col-xl-3">
            <div class="card stat-card h-100 card-hover">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <div class="stat-label">Qarzdorlar</div>
                        <div class="stat-value text-danger">{{ count($debtor_students) }}</div>
                        <small class="text-muted">talaba</small>
                    </div>
                    <span class="stat-icon is-danger"><i class="bx bx-error-circle"></i></span>
                </div>
            </div>
        </div>
    </div>

    {{-- ===================== HAFTALIK KO'RSATKICHLAR ===================== --}}
    @php
        $maxMarks  = max(1, $week_max_marks);
        $maxIncome = max(1, $week_max_income);
        $weekRate  = $week_lessons > 0
            ? max(0, min(100, (int) round(100 - ($week_absent / max(1, $week_lessons * max(1, $number_of_students ?: 1))) * 100)))
            : null;
    @endphp

    <div class="row g-4 mb-4">
        <div class="col-xl-7">
            <div class="card h-100">
                <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <span>Haftalik davomat</span>
                    <div class="d-flex align-items-center gap-3" style="font-size: .8rem;">
                        <span class="text-muted">
                            <i class="bx bx-book-open me-1"></i>{{ $week_lessons }} dars
                        </span>
                        <span class="text-danger">
                            <i class="bx bx-user-x me-1"></i>{{ $week_absent }} kelmadi
                        </span>
                        <span class="text-warning">
                            <i class="bx bx-time-five me-1"></i>{{ $week_late }} kechikdi
                        </span>
                    </div>
                </div>

                <div class="card-body">
                    @if($week_lessons === 0 && $week_absent === 0)
                        <div class="empty-state py-4">
                            <i class="bx bx-calendar-x"></i>
                            <h6>Bu haftada dars yo‘q</h6>
                            <p class="mb-0">So‘nggi 7 kunda davomat qayd etilmagan.</p>
                        </div>
                    @else
                        <div class="d-flex align-items-end justify-content-between gap-2"
                             style="height: 170px;">
                            @foreach($week as $day)
                                @php
                                    $marks = $day['absent'] + $day['late'];
                                    $h = $marks > 0 ? max(6, (int) round($marks / $maxMarks * 130)) : 3;
                                @endphp
                                <div class="d-flex flex-column align-items-center flex-grow-1"
                                     style="min-width: 0;"
                                     title="{{ $day['short'] }} — {{ $day['lessons'] }} dars, {{ $day['absent'] }} kelmadi, {{ $day['late'] }} kechikdi">
                                    <div class="fw-semibold mb-1" style="font-size: .78rem;">
                                        {{ $marks > 0 ? $marks : '' }}
                                    </div>
                                    <div class="w-100 d-flex justify-content-center align-items-end"
                                         style="height: 130px;">
                                        <div style="width: 60%; max-width: 34px; height: {{ $h }}px;
                                                    border-radius: 6px 6px 0 0;
                                                    background: {{ $marks > 0 ? 'var(--app-danger)' : 'var(--app-surface-3)' }};
                                                    opacity: {{ $marks > 0 ? 1 : .6 }};"></div>
                                    </div>
                                    <div class="text-muted mt-2 text-truncate w-100 text-center"
                                         style="font-size: .72rem;">{{ $day['label'] }}</div>
                                    <div class="text-muted text-truncate w-100 text-center"
                                         style="font-size: .68rem;">{{ $day['short'] }}</div>
                                </div>
                            @endforeach
                        </div>
                        <p class="text-muted mb-0 mt-3" style="font-size: .78rem;">
                            <i class="bx bx-info-circle me-1"></i>
                            Ustunlar — kunlik kelmagan va kechikkanlar soni. Kelganlar alohida
                            yozilmaydi, shuning uchun past ustun yaxshi belgi.
                        </p>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-xl-5">
            <div class="card h-100">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <span>Haftalik tushum</span>
                    <span class="badge bg-label-success">
                        {{ number_format($week_income, 0, '.', ' ') }} so‘m
                    </span>
                </div>

                <div class="card-body">
                    @if($week_income === 0)
                        <div class="empty-state py-4">
                            <i class="bx bx-receipt"></i>
                            <h6>Tushum yo‘q</h6>
                            <p class="mb-0">So‘nggi 7 kunda to‘lov qayd etilmagan.</p>
                        </div>
                    @else
                        @foreach($week as $day)
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <span class="text-muted flex-shrink-0"
                                      style="width: 3.4rem; font-size: .76rem;">{{ $day['short'] }}</span>
                                <div class="score-bar is-success flex-grow-1">
                                    <span style="width: {{ $day['income'] > 0 ? max(3, (int) round($day['income'] / $maxIncome * 100)) : 0 }}%;"></span>
                                </div>
                                <span class="flex-shrink-0 text-end fw-semibold"
                                      style="width: 6.5rem; font-size: .78rem;">
                                    {{ $day['income'] > 0 ? number_format($day['income'], 0, '.', ' ') : '—' }}
                                </span>
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Guruh holati --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-xl-3">
            <div class="card stat-card h-100">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <div class="stat-label">Faol guruhlar</div>
                        <div class="stat-value">{{ $active_groups }}</div>
                    </div>
                    <span class="stat-icon"><i class="bx bx-group"></i></span>
                </div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="card stat-card h-100">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <div class="stat-label">Tugagan guruhlar</div>
                        <div class="stat-value">{{ $finished_groups }}</div>
                    </div>
                    <span class="stat-icon is-info"><i class="bx bx-flag"></i></span>
                </div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="card stat-card h-100">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <div class="stat-label">Bitirganlar</div>
                        <div class="stat-value">{{ $graduates }}</div>
                    </div>
                    <span class="stat-icon is-success"><i class="bx bx-award"></i></span>
                </div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="card stat-card h-100">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <div class="stat-label">Ota-onalar</div>
                        <div class="stat-value">{{ $number_of_parents }}</div>
                    </div>
                    <span class="stat-icon is-warning"><i class="bx bx-home-heart"></i></span>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">

        {{-- Teachers --}}
        <div class="col-xl-8">
            <div class="card h-100">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <span>O‘qituvchilar</span>
                    <a href="{{ route('teacher.index') }}" class="btn btn-sm btn-outline-secondary">Barchasi</a>
                </div>

                @if($teacher_panel->isEmpty())
                    <div class="empty-state">
                        <i class="bx bx-chalkboard"></i>
                        <h6>O‘qituvchi yo‘q</h6>
                        <p class="mb-0">Hozircha o‘qituvchi qo‘shilmagan.</p>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                            <tr>
                                <th>O‘qituvchi</th>
                                <th>Guruhlar</th>
                                <th>Talabalar</th>
                                <th class="text-end">Oylik</th>
                            </tr>
                            </thead>
                            {{-- $teacher_panel is prebuilt from grouped aggregates.
                                 Calling teacherHasGroup()/teacherHasStudents()/teacherPayment()
                                 here would fire three queries PER ROW. --}}
                            <tbody>
                            @foreach($teacher_panel as $teacher)
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="avatar avatar-sm">
                                                @if($teacher['photo'])
                                                    <img src="{{ asset('storage/' . $teacher['photo']) }}" alt=""
                                                         class="rounded-circle w-100 h-100" style="object-fit: cover;">
                                                @else
                                                    <span class="avatar-initial rounded-circle bg-label-primary">
                                                        {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($teacher['name'], 0, 2)) }}
                                                    </span>
                                                @endif
                                            </div>
                                            <div class="min-w-0">
                                                <a href="{{ route('teacher.show', $teacher['id']) }}"
                                                   class="fw-semibold text-truncate d-block">{{ $teacher['name'] }}</a>
                                                <small class="text-muted">{{ $teacher['percent'] }}%</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td><span class="badge bg-label-info">{{ $teacher['groups'] }}</span></td>
                                    <td><span class="badge bg-label-warning">{{ $teacher['students'] }}</span></td>
                                    <td class="text-end fw-semibold text-success">
                                        {{ number_format($teacher['salary'], 0, '.', ' ') }} so‘m
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>

        {{-- Today's absences --}}
        <div class="col-xl-4">
            <div class="card h-100">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <span>Bugun kelmaganlar</span>
                    <span class="badge bg-label-danger">{{ count($today_attendances) }}</span>
                </div>
                <div class="card-body">
                    @forelse($today_attendances as $attendance)
                        <div class="d-flex align-items-center justify-content-between {{ $loop->last ? '' : 'mb-2 pb-2 border-bottom' }}">
                            <span class="text-truncate me-2">{{ $attendance->user->name ?? '—' }}</span>
                            <small class="text-muted flex-shrink-0">{{ $attendance->group->name ?? '—' }}</small>
                        </div>
                    @empty
                        <div class="empty-state py-4">
                            <i class="bx bx-check-circle"></i>
                            <h6>Hammasi joyida</h6>
                            <p class="mb-0">Bugun qoldirilgan dars yo‘q.</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Debtors --}}
        <div class="col-xl-6">
            <div class="card h-100">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <span>Qarzdor talabalar</span>
                    <a href="{{ route('dept.index') }}" class="btn btn-sm btn-outline-secondary">To‘lovlar</a>
                </div>
                @if(count($debtor_students) === 0)
                    <div class="empty-state">
                        <i class="bx bx-check-circle"></i>
                        <h6>Qarzdor yo‘q</h6>
                        <p class="mb-0">Barcha to‘lovlar joyida.</p>
                    </div>
                @else
                    <div class="table-responsive" style="max-height: 340px; overflow-y: auto;">
                        <table class="table table-hover">
                            <thead>
                            <tr>
                                <th>Talaba</th>
                                <th class="text-end">Oylar</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($debtor_students as $debtor)
                                <tr>
                                    <td>
                                        <a href="{{ route('student.show', $debtor->id) }}">{{ $debtor->name }}</a>
                                    </td>
                                    <td class="text-end">
                                        <span class="badge bg-label-danger">{{ abs($debtor->status) }}</span>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>

        {{-- Today's transactions --}}
        <div class="col-xl-6">
            <div class="card h-100">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <span>Bugungi to‘lovlar</span>
                    <span class="badge bg-label-success">
                        {{ number_format($daily_income, 0, '.', ' ') }} so‘m
                    </span>
                </div>
                @if(count($daily_transactions) === 0)
                    <div class="empty-state">
                        <i class="bx bx-receipt"></i>
                        <h6>To‘lov yo‘q</h6>
                        <p class="mb-0">Bugun to‘lov qayd etilmagan.</p>
                    </div>
                @else
                    <div class="table-responsive" style="max-height: 340px; overflow-y: auto;">
                        <table class="table table-hover">
                            <thead>
                            <tr>
                                <th>Talaba</th>
                                <th>Vaqt</th>
                                <th class="text-end">Summa</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($daily_transactions as $transaction)
                                <tr>
                                    <td class="text-truncate">{{ $transaction->name }}</td>
                                    <td class="text-muted">{{ $transaction->created_at?->format('H:i') }}</td>
                                    <td class="text-end fw-semibold text-success">
                                        {{ number_format($transaction->payment, 0, '.', ' ') }}
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
    @endrole

    {{-- =============================== TEACHER =============================== --}}
    @role('user')

    <div class="page-head">
        <h4 class="mb-0">Assalomu alaykum, {{ auth()->user()->name }}</h4>
        <a href="{{ route('attendance') }}" class="btn btn-primary">
            <i class="bx bx-calendar-check me-1"></i> Davomat olish
        </a>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-4">
            <div class="card stat-card h-100">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <div class="stat-label">Guruhlarim</div>
                        <div class="stat-value">{{ $groups->count() }}</div>
                    </div>
                    <span class="stat-icon"><i class="bx bx-group"></i></span>
                </div>
            </div>
        </div>

        <div class="col-6 col-lg-4">
            <div class="card stat-card h-100">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <div class="stat-label">Talabalarim</div>
                        <div class="stat-value">{{ $student_count }}</div>
                    </div>
                    <span class="stat-icon is-info"><i class="bx bx-user-voice"></i></span>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-4">
            <div class="card stat-card h-100">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <div class="stat-label">Hisoblangan oylik</div>
                        <div class="stat-value">{{ number_format($salary, 0, '.', ' ') }}</div>
                        <small class="text-muted">so‘m</small>
                    </div>
                    <span class="stat-icon is-success"><i class="bx bx-wallet"></i></span>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card h-100">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <span>Guruhlarim</span>
                    <a href="{{ route('teacher.groups') }}" class="btn btn-sm btn-outline-secondary">Barchasi</a>
                </div>
                @if($groups->isEmpty())
                    <div class="empty-state">
                        <i class="bx bx-group"></i>
                        <h6>Guruh yo‘q</h6>
                        <p class="mb-0">Sizga hali guruh biriktirilmagan.</p>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                            <tr>
                                <th>Guruh</th>
                                <th>Talabalar</th>
                                <th class="text-end">Vaqt</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($groups as $group)
                                <tr>
                                    <td class="fw-semibold">{{ $group->name }}</td>
                                    <td><span class="badge bg-label-info">{{ $group->students->count() }}</span></td>
                                    <td class="text-end text-muted">{{ $group->start_time }} – {{ $group->finish_time }}</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card mb-4">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <span>Bugun kelmaganlar</span>
                    <span class="badge bg-label-danger">{{ count($today_absences) }}</span>
                </div>
                <div class="card-body">
                    @forelse($today_absences as $absence)
                        <div class="d-flex align-items-center justify-content-between {{ $loop->last ? '' : 'mb-2 pb-2 border-bottom' }}">
                            <span class="text-truncate me-2">{{ $absence->user->name ?? '—' }}</span>
                            <small class="text-muted flex-shrink-0">{{ $absence->group->name ?? '—' }}</small>
                        </div>
                    @empty
                        <div class="empty-state py-4">
                            <i class="bx bx-check-circle"></i>
                            <h6>Hammasi joyida</h6>
                            <p class="mb-0">Bugun qoldirilgan dars yo‘q.</p>
                        </div>
                    @endforelse
                </div>
            </div>

            <div class="card">
                <div class="card-header">So‘nggi darslar</div>
                <div class="card-body">
                    @forelse($recent_lessons as $lesson)
                        <div class="d-flex align-items-center justify-content-between {{ $loop->last ? '' : 'mb-2 pb-2 border-bottom' }}">
                            <span class="text-truncate me-2">{{ $lesson->name }}</span>
                            <small class="text-muted flex-shrink-0">{{ $lesson->created_at?->format('d.m.Y') }}</small>
                        </div>
                    @empty
                        <div class="empty-state py-4">
                            <i class="bx bx-book-open"></i>
                            <h6>Dars yo‘q</h6>
                            <p class="mb-0">Hozircha dars qayd etilmagan.</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
    @endrole

@endsection
