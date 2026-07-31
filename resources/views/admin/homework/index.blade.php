@extends('template.master')

@section('title', 'Uy vazifalari hisoboti')
@section('subtitle', 'Barcha guruhlar bo‘yicha topshiriqlar va ularning bajarilishi')

@section('content')

    @php
        $bands = config('grading.bands');
        $hasFilters = filled($filters['group_id']) || filled($filters['from']) || filled($filters['to']);
        $avgTotalTone = $averageTotal === null
            ? 'info'
            : ($averageTotal >= $bands['good'] ? 'success' : ($averageTotal >= $bands['ok'] ? 'warning' : 'danger'));
    @endphp

    {{-- Summary tiles --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="card stat-card h-100">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <div class="stat-label">Vazifalar</div>
                        <div class="stat-value">{{ $totalHomeworks }}</div>
                    </div>
                    <span class="stat-icon"><i class="bx bx-task"></i></span>
                </div>
            </div>
        </div>

        <div class="col-6 col-lg-3">
            <div class="card stat-card h-100">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <div class="stat-label">Topshirilgan javoblar</div>
                        <div class="stat-value">{{ $submittedTotal }}</div>
                    </div>
                    <span class="stat-icon is-success"><i class="bx bx-check-circle"></i></span>
                </div>
            </div>
        </div>

        <div class="col-6 col-lg-3">
            <div class="card stat-card h-100">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <div class="stat-label">Baholangan</div>
                        <div class="stat-value">{{ $gradedTotal }}</div>
                    </div>
                    <span class="stat-icon is-info"><i class="bx bx-medal"></i></span>
                </div>
            </div>
        </div>

        <div class="col-6 col-lg-3">
            <div class="card stat-card h-100">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <div class="stat-label">O‘rtacha ball</div>
                        <div class="stat-value text-{{ $averageTotal === null ? '' : $avgTotalTone }}">
                            {{ $averageTotal === null ? '—' : number_format((float) $averageTotal, 1, '.', ' ') }}
                        </div>
                    </div>
                    <span class="stat-icon is-{{ $avgTotalTone }}"><i class="bx bx-bar-chart-alt-2"></i></span>
                </div>
            </div>
        </div>
    </div>

    <form method="GET" action="{{ route('homework.admin.index') }}" class="filter-card">
        <div class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label" for="filter_group">Guruh</label>
                <select id="filter_group" name="group_id" class="form-select">
                    <option value="">Barcha guruhlar</option>
                    @foreach($groups as $group)
                        <option value="{{ $group->id }}"
                                @selected((string) $filters['group_id'] === (string) $group->id)>{{ $group->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-3">
                <label class="form-label" for="filter_from">Sanadan</label>
                <input type="date" id="filter_from" name="from" class="form-control" value="{{ $filters['from'] }}">
            </div>

            <div class="col-md-3">
                <label class="form-label" for="filter_to">Sanagacha</label>
                <input type="date" id="filter_to" name="to" class="form-control" value="{{ $filters['to'] }}">
            </div>

            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-grow-1">
                    <i class="bx bx-filter-alt me-1"></i> Filtr
                </button>
                @if($hasFilters)
                    <a href="{{ route('homework.admin.index') }}" class="btn-icon" title="Tozalash">
                        <i class="bx bx-x"></i>
                    </a>
                @endif
            </div>
        </div>
    </form>

    <div class="card">
        @if($homeworks->isEmpty())
            <div class="empty-state">
                <i class="bx bx-task"></i>
                <h6>{{ $hasFilters ? 'Filtrga mos vazifa topilmadi' : 'Uy vazifalari yo‘q' }}</h6>
                <p class="mb-0">
                    {{ $hasFilters
                        ? 'Sana oralig‘i yoki guruhni o‘zgartirib ko‘ring.'
                        : 'O‘qituvchilar hali birorta topshiriq yaratmagan.' }}
                </p>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                    <tr>
                        <th>Guruh</th>
                        <th>Sarlavha</th>
                        <th>Muddat</th>
                        <th class="text-center">Topshirgan</th>
                        <th class="text-center">Baholangan</th>
                        <th class="text-end">O‘rtacha ball</th>
                    </tr>
                    </thead>
                    <tbody id="myTable">
                    @foreach($homeworks as $homework)
                        @php
                            $total = (int) ($memberCounts[$homework->group_id] ?? 0);
                            $avg = $homework->average_score;
                            $max = max(1, (int) $homework->max_score);
                            $avgPct = $avg !== null ? ($avg / $max) * 100 : null;
                            $avgTone = $avgPct === null
                                ? 'secondary'
                                : ($avgPct >= $bands['good'] ? 'success' : ($avgPct >= $bands['ok'] ? 'warning' : 'danger'));
                        @endphp
                        <tr>
                            <td class="fw-semibold">{{ $homework->group->name ?? '—' }}</td>
                            <td class="min-w-0">
                                {{ $homework->title }}
                                <div class="text-muted" style="font-size: .78rem;">
                                    {{ $homework->author->name ?? '—' }} ·
                                    {{ $homework->created_at?->format('d.m.Y') }} ·
                                    maks. {{ $homework->max_score }} ball
                                </div>
                            </td>
                            <td>
                                @if($homework->due_date)
                                    <span class="badge bg-label-{{ $homework->isOverdue() ? 'danger' : 'info' }}">
                                        {{ $homework->due_date->format('d.m.Y') }}
                                    </span>
                                @else
                                    <span class="badge bg-label-secondary">Muddatsiz</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <span class="fw-semibold">{{ (int) $homework->submitted_count }}</span>
                                <span class="text-muted">/ {{ $total }}</span>
                            </td>
                            <td class="text-center">
                                <span class="fw-semibold">{{ (int) $homework->graded_count }}</span>
                                <span class="text-muted">/ {{ $total }}</span>
                            </td>
                            <td class="text-end">
                                @if($avg === null)
                                    <span class="text-muted">—</span>
                                @else
                                    <span class="badge bg-label-{{ $avgTone }}">
                                        {{ number_format((float) $avg, 1, '.', ' ') }}
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>

            @if($homeworks->hasPages())
                <div class="card-footer d-flex justify-content-end">
                    {{ $homeworks->links() }}
                </div>
            @endif
        @endif
    </div>

@endsection
