@extends('template.master')

@section('title', 'O‘zlashtirishim')
@section('subtitle', 'Oylik ko‘rsatkich va uning tarkibi')

@section('content')

    @php
        $good = config('grading.bands.good', 80);
        $ok   = config('grading.bands.ok', 60);
        $tone = fn($v) => $v === null ? 'secondary' : ($v >= $good ? 'success' : ($v >= $ok ? 'warning' : 'danger'));

        $weights = (array) config('grading.progress_weights', []);

        $componentMeta = [
            'attendance' => ['label' => 'Davomat',      'icon' => 'bx-calendar-check'],
            'tests'      => ['label' => 'Testlar',      'icon' => 'bx-clipboard'],
            'homework'   => ['label' => 'Uy vazifasi',  'icon' => 'bx-book'],
            'skills'     => ['label' => 'Ko‘nikmalar',  'icon' => 'bx-award'],
        ];

        $current      = $progress['current'] ?? null;
        $delta        = $progress['delta'] ?? null;
        $basisLabel   = \App\Services\ProgressService::basisLabel($progress['basis'] ?? null);
        $currentMonth = ($progress['current_month'] ?? null)
            ? \App\Services\ProgressService::monthLabel($progress['current_month'])
            : null;
    @endphp

    <div class="page-head justify-content-end">
        <div class="page-sub me-auto">
            Ko‘rsatkich davomat, test, uy vazifasi va ko‘nikma baholaringizdan hisoblanadi.
        </div>
        <a href="{{ route('student.skills') }}" class="btn btn-outline-secondary">
            <i class="bx bx-award me-1"></i> Ko‘nikmalarim
        </a>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-12 col-lg-4">
            <div class="card stat-card h-100">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div class="min-w-0">
                        <div class="stat-label">Joriy ko‘rsatkich</div>
                        <div class="stat-value text-{{ $tone($current) }}">{{ $current ?? '—' }}</div>
                        <div>
                            @if($delta === null)
                                <span class="delta is-flat">solishtirish uchun ma’lumot yo‘q</span>
                            @elseif($delta > 0)
                                <span class="delta is-up"><i class="bx bx-up-arrow-alt"></i>+{{ $delta }}</span>
                            @elseif($delta < 0)
                                <span class="delta is-down"><i class="bx bx-down-arrow-alt"></i>{{ $delta }}</span>
                            @else
                                <span class="delta is-flat"><i class="bx bx-minus"></i>o‘zgarishsiz</span>
                            @endif
                        </div>
                        @if($currentMonth)
                            <div class="text-muted" style="font-size: .78rem;">
                                {{ $currentMonth }}@if($basisLabel), {{ $basisLabel }}@endif
                            </div>
                        @endif
                    </div>
                    <span class="stat-icon is-{{ $tone($current) === 'secondary' ? 'info' : $tone($current) }}">
                        <i class="bx bx-line-chart"></i>
                    </span>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-8">
            <div class="card h-100">
                <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <span>Ko‘rsatkich tarkibi</span>
                    <span class="text-muted" style="font-size: .8rem;">{{ $currentMonth ?: 'Ma’lumot yo‘q' }}</span>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        @foreach($componentMeta as $key => $meta)
                            @php $value = $progress['components'][$key] ?? null; @endphp
                            <div class="col-6 col-md-3">
                                <div class="metric-box h-100">
                                    <i class="bx {{ $meta['icon'] }} text-{{ $tone($value) }}"
                                       style="font-size: 1.35rem;"></i>
                                    <div class="metric-value text-{{ $tone($value) }}">{{ $value ?? '—' }}</div>
                                    <div class="metric-label">{{ $meta['label'] }}</div>
                                    <div class="score-bar is-{{ $tone($value) }} mt-2">
                                        <span style="width: {{ $value ?? 0 }}%;"></span>
                                    </div>
                                    <div class="metric-label mt-1">ulushi {{ (int) round(($weights[$key] ?? 0) * 100) }}%</div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <div class="text-muted mt-3 mb-0" style="font-size: .78rem;">
                        <i class="bx bx-info-circle me-1"></i>
                        Ma’lumot bo‘lmagan tarkibiy qism hisobga olinmaydi.
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">Oylik dinamika</div>
        <div class="card-body">
            @include('partials.progress-chart', [
                'chartId'    => 'myProgressChart',
                'buckets'    => $progress['buckets'],
                'chartLabel' => 'O‘zlashtirish',
            ])
        </div>
    </div>

    <div class="row g-4">

        {{-- Tests --}}
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <span>So‘nggi test natijalari</span>
                    <a href="{{ route('assessment.index') }}" class="btn btn-sm btn-outline-secondary">Barchasi</a>
                </div>
                @if($evidence['tests']->isEmpty())
                    <div class="empty-state py-4">
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
                                <th class="text-center">Ball</th>
                                <th class="text-end">Sana</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($evidence['tests'] as $test)
                                @php $mark = (int) $test->get_mark; @endphp
                                <tr>
                                    <td>{{ $test->test_name ?? 'Test' }}</td>
                                    <td class="text-center">
                                        <span class="badge bg-label-{{ $tone($mark) }}">{{ $mark }}</span>
                                    </td>
                                    <td class="text-end text-muted">{{ optional($test->created_at)->format('d.m.Y') }}</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>

        {{-- Homework --}}
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <span>So‘nggi uy vazifalari</span>
                    <a href="{{ route('student.homework') }}" class="btn btn-sm btn-outline-secondary">Barchasi</a>
                </div>
                @if($evidence['homework']->isEmpty())
                    <div class="empty-state py-4">
                        <i class="bx bx-book"></i>
                        <h6>Vazifa yo‘q</h6>
                        <p class="mb-0">Uy vazifasi qayd etilmagan.</p>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                            <tr>
                                <th>Vazifa</th>
                                <th>Holat</th>
                                <th class="text-end">Ball</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($evidence['homework'] as $submission)
                                <tr>
                                    <td>
                                        {{ $submission->homework->title ?? '—' }}
                                        @if($submission->homework?->due_date)
                                            <div class="text-muted" style="font-size: .75rem;">
                                                {{ $submission->homework->due_date->format('d.m.Y') }}
                                            </div>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge bg-label-{{ $submission->statusTone() }}">
                                            {{ $submission->statusLabel() }}
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        @if($submission->score === null)
                                            <span class="text-muted">—</span>
                                        @else
                                            <span class="fw-semibold text-{{ $submission->scoreTone() }}">
                                                {{ $submission->score }}
                                            </span>
                                            <span class="text-muted">/ {{ $submission->homework->max_score ?? 100 }}</span>
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

        {{-- Skill grades --}}
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <span>So‘nggi ko‘nikma baholari</span>
                    <a href="{{ route('student.skills') }}" class="btn btn-sm btn-outline-secondary">Barchasi</a>
                </div>
                @if($evidence['skills']->isEmpty())
                    <div class="empty-state py-4">
                        <i class="bx bx-award"></i>
                        <h6>Baho yo‘q</h6>
                        <p class="mb-0">Hozircha ko‘nikma bahosi qo‘yilmagan.</p>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                            <tr>
                                <th>Ko‘nikma</th>
                                <th>Dars</th>
                                <th class="text-end">Ball</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($evidence['skills'] as $mark)
                                <tr>
                                    <td>
                                        <i class="bx {{ config('grading.skill_icons')[$mark->skill] ?? 'bx-star' }} me-1"></i>
                                        {{ $mark->skillLabel() }}
                                    </td>
                                    <td class="text-muted">
                                        {{ $mark->lesson->name ?? '—' }}
                                        <div style="font-size: .75rem;">{{ optional($mark->created_at)->format('d.m.Y') }}</div>
                                    </td>
                                    <td class="text-end">
                                        <span class="badge bg-label-{{ \App\Models\LessonSkillGrade::tone($mark->score) }}">
                                            {{ $mark->score }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>

        {{-- Absences --}}
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <span>So‘nggi qoldirilgan darslar</span>
                    <a href="{{ route('attendance.index') }}" class="btn btn-sm btn-outline-secondary">Barchasi</a>
                </div>
                @if($evidence['absences']->isEmpty())
                    <div class="empty-state py-4">
                        <i class="bx bx-check-circle"></i>
                        <h6>Qoldirilgan dars yo‘q</h6>
                        <p class="mb-0">Barcha darslarda qatnashgansiz.</p>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                            <tr>
                                <th>Sana</th>
                                <th>Guruh</th>
                                <th class="text-end">Holat</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($evidence['absences'] as $item)
                                <tr>
                                    <td>{{ optional($item->created_at)->format('d.m.Y') }}</td>
                                    <td class="text-muted">{{ $item->group->name ?? '—' }}</td>
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
    </div>

@endsection
