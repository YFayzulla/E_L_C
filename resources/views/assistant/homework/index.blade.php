@extends('template.master')

@section('title', 'Uy vazifalarni tekshirish')
@section('subtitle', 'Writing va Speaking topshiriqlari')

@section('content')
    @php
        $bands = config('grading.bands');
        $hasFilters = filled($filters['skill']) || filled($filters['status']) || filled($filters['q']);
    @endphp

    <form method="GET" action="{{ route('assistant.homework.index') }}" class="filter-card">
        <div class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label" for="skill">Turi</label>
                <select id="skill" name="skill" class="form-select">
                    <option value="">Barchasi</option>
                    @foreach($skillOptions as $value => $label)
                        <option value="{{ $value }}" @selected($filters['skill'] === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-3">
                <label class="form-label" for="status">Holat</label>
                <select id="status" name="status" class="form-select">
                    <option value="">Barchasi</option>
                    <option value="active" @selected($filters['status'] === 'active')>Faol</option>
                    <option value="overdue" @selected($filters['status'] === 'overdue')>Muddati o‘tgan</option>
                </select>
            </div>

            <div class="col-md-4">
                <label class="form-label" for="q">Qidiruv</label>
                <input type="text" id="q" name="q" class="form-control"
                       value="{{ $filters['q'] }}" placeholder="Sarlavha bo‘yicha...">
            </div>

            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-grow-1">
                    <i class="bx bx-filter-alt me-1"></i> Filtr
                </button>
                @if($hasFilters)
                    <a href="{{ route('assistant.homework.index') }}" class="btn-icon" title="Tozalash">
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
                <h6>{{ $hasFilters ? 'Filtrga mos vazifa topilmadi' : 'Tekshiriladigan vazifa yo‘q' }}</h6>
                <p class="mb-0">{{ $hasFilters ? 'Filtrlarni o‘zgartirib ko‘ring.' : 'Writing yoki Speaking turidagi vazifalar shu yerda chiqadi.' }}</p>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                    <tr>
                        <th>Sarlavha</th>
                        <th>Turi</th>
                        <th>Guruh</th>
                        <th>Muddat</th>
                        <th class="text-center">Topshirgan</th>
                        <th class="text-center">Baholangan</th>
                        <th class="text-center">O‘rtacha</th>
                        <th class="text-end">Amal</th>
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
                            <td>
                                <a href="{{ route('assistant.homework.grade', $homework->id) }}"
                                   class="fw-semibold text-decoration-none">{{ $homework->title }}</a>
                                <div class="text-muted" style="font-size: .78rem;">
                                    {{ $homework->created_at?->format('d.m.Y') }} · maks. {{ $homework->max_score }} ball
                                </div>
                            </td>
                            <td><span class="badge bg-label-secondary">{{ $homework->skillLabel() }}</span></td>
                            <td>{{ $homework->group?->name ?? '—' }}</td>
                            <td>
                                @if($homework->due_date)
                                    <span class="badge bg-label-{{ $homework->isOverdue() ? 'danger' : 'info' }}">
                                        {{ $homework->dueLabel() }}
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
                            <td class="text-center">
                                @if($avg === null)
                                    <span class="text-muted">—</span>
                                @else
                                    <span class="badge bg-label-{{ $avgTone }}">{{ number_format((float) $avg, 1, '.', ' ') }}</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <a href="{{ route('assistant.homework.grade', $homework->id) }}"
                                   class="btn btn-sm btn-primary" title="Tekshirish">
                                    <i class="bx bx-check-square"></i>
                                </a>
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
