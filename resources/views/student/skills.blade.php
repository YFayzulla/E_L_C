@extends('template.master')

@section('title', 'Ko‘nikmalarim')
@section('subtitle', 'Reading, listening, writing va speaking bo‘yicha baholaringiz')

@section('content')

    @php
        $labels = (array) config('grading.skill_labels', []);
        $icons  = (array) config('grading.skill_icons', []);
        $good   = config('grading.bands.good', 80);
        $ok     = config('grading.bands.ok', 60);
        $tone   = fn($v) => $v === null ? 'secondary' : ($v >= $good ? 'success' : ($v >= $ok ? 'warning' : 'danger'));

        // Only scored rows feed the averages, so count those — not every row.
        $scoredMarks = array_sum(array_column($averages, 'marks'));
    @endphp

    <div class="page-head justify-content-end">
        <div class="page-sub me-auto">
            Baholar dars davomida o‘qituvchi tomonidan qo‘yiladi.
        </div>
        <a href="{{ route('student.progress') }}" class="btn btn-outline-secondary">
            <i class="bx bx-line-chart me-1"></i> O‘zlashtirishim
        </a>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-12 col-lg-4">
            <div class="card stat-card h-100">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <div class="stat-label">Umumiy o‘rtacha</div>
                        <div class="stat-value text-{{ $tone($overall) }}">{{ $overall ?? '—' }}</div>
                        <div class="text-muted" style="font-size: .8rem;">
                            {{ $scoredMarks }} ta baho asosida
                        </div>
                    </div>
                    <span class="stat-icon is-info"><i class="bx bx-target-lock"></i></span>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-8">
            <div class="card h-100">
                <div class="card-header">Ko‘nikmalar bo‘yicha</div>
                <div class="card-body">
                    <div class="row g-3">
                        @foreach($skills as $skill)
                            @php $value = $averages[$skill]['avg'] ?? null; @endphp
                            <div class="col-6 col-md-3">
                                <div class="metric-box h-100">
                                    <i class="bx {{ $icons[$skill] ?? 'bx-star' }} text-{{ $tone($value) }}"
                                       style="font-size: 1.35rem;"></i>
                                    <div class="metric-value text-{{ $tone($value) }}">{{ $value ?? '—' }}</div>
                                    <div class="metric-label">{{ $labels[$skill] ?? ucfirst($skill) }}</div>
                                    <div class="score-bar is-{{ $tone($value) }} mt-2">
                                        <span style="width: {{ $value ?? 0 }}%;"></span>
                                    </div>
                                    <div class="metric-label mt-1">{{ $averages[$skill]['marks'] ?? 0 }} ta baho</div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">So‘nggi baholar</div>

        @if($recent->isEmpty())
            <div class="empty-state">
                <i class="bx bx-award"></i>
                <h6>Baho yo‘q</h6>
                <p class="mb-0">Hozircha ko‘nikma bahosi qo‘yilmagan.</p>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                    <tr>
                        <th>Sana</th>
                        <th>Ko‘nikma</th>
                        <th>Guruh</th>
                        <th>Dars</th>
                        <th>Izoh</th>
                        <th class="text-end">Ball</th>
                    </tr>
                    </thead>
                    <tbody id="myTable">
                    @foreach($recent as $mark)
                        <tr>
                            <td class="text-muted">{{ optional($mark->created_at)->format('d.m.Y') }}</td>
                            <td>
                                <i class="bx {{ $icons[$mark->skill] ?? 'bx-star' }} me-1"></i>
                                {{ $mark->skillLabel() }}
                            </td>
                            <td class="text-muted">{{ $mark->group->name ?? '—' }}</td>
                            <td class="text-muted">{{ $mark->lesson->name ?? '—' }}</td>
                            <td class="text-muted">{{ $mark->comment ?: '—' }}</td>
                            <td class="text-end">
                                @if($mark->score === null)
                                    <span class="text-muted">—</span>
                                @else
                                    <span class="badge bg-label-{{ \App\Models\LessonSkillGrade::tone($mark->score) }}">
                                        {{ $mark->score }}
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            <div class="card-footer d-flex justify-content-end">
                {{ $recent->links() }}
            </div>
        @endif
    </div>

@endsection
