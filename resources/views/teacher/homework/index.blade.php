@extends('template.master')

@section('title', 'Uy vazifalari')
@section('subtitle', 'Guruhlaringizga bergan topshiriqlar va ularning holati')

@section('content')

    @php
        $bands = config('grading.bands');
        $hasFilters = filled($filters['group_id']) || filled($filters['status']) || filled($filters['q']);
    @endphp

    <div class="page-head justify-content-end">
        <a href="{{ route('homework.create') }}" class="btn btn-primary">
            <i class="bx bx-plus me-1"></i> Yangi vazifa
        </a>
    </div>

    <form method="GET" action="{{ route('homework.index') }}" class="filter-card">
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
                <label class="form-label" for="filter_status">Holat</label>
                <select id="filter_status" name="status" class="form-select">
                    <option value="">Barchasi</option>
                    <option value="active" @selected($filters['status'] === 'active')>Faol</option>
                    <option value="overdue" @selected($filters['status'] === 'overdue')>Muddati o‘tgan</option>
                </select>
            </div>

            <div class="col-md-3">
                <label class="form-label" for="filter_q">Qidiruv</label>
                <input type="text" id="filter_q" name="q" class="form-control"
                       value="{{ $filters['q'] }}" placeholder="Sarlavha bo‘yicha…">
            </div>

            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-grow-1">
                    <i class="bx bx-filter-alt me-1"></i> Filtr
                </button>
                @if($hasFilters)
                    <a href="{{ route('homework.index') }}" class="btn-icon" title="Tozalash">
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
                <h6>{{ $hasFilters ? 'Filtrga mos vazifa topilmadi' : 'Hali uy vazifasi yo‘q' }}</h6>
                <p class="mb-3">
                    {{ $hasFilters
                        ? 'Filtrlarni o‘zgartirib ko‘ring.'
                        : 'Birinchi topshiriqni yaratib, talabalardan javob kutishingiz mumkin.' }}
                </p>
                @if($hasFilters)
                    <a href="{{ route('homework.index') }}" class="btn btn-outline-secondary btn-sm">Filtrni tozalash</a>
                @else
                    <a href="{{ route('homework.create') }}" class="btn btn-primary btn-sm">
                        <i class="bx bx-plus me-1"></i> Yangi vazifa
                    </a>
                @endif
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                    <tr>
                        <th>Sarlavha</th>
                        <th>Guruh</th>
                        <th>Muddat</th>
                        <th class="text-center">Topshirgan</th>
                        <th class="text-center">Baholangan</th>
                        <th class="text-center">O‘rtacha ball</th>
                        <th class="text-end">Amallar</th>
                    </tr>
                    </thead>
                    <tbody id="myTable">
                    @foreach($homeworks as $homework)
                        @php
                            $total = (int) ($memberCounts[$homework->group_id] ?? 0);
                            $submitted = (int) $homework->submitted_count;
                            $graded = (int) $homework->graded_count;
                            $avg = $homework->average_score;
                            $max = max(1, (int) $homework->max_score);
                            $avgPct = $avg !== null ? ($avg / $max) * 100 : null;
                            $avgTone = $avgPct === null
                                ? 'secondary'
                                : ($avgPct >= $bands['good'] ? 'success' : ($avgPct >= $bands['ok'] ? 'warning' : 'danger'));
                        @endphp
                        <tr>
                            <td class="min-w-0">
                                <a href="{{ route('homework.show', $homework->id) }}"
                                   class="fw-semibold text-decoration-none">{{ $homework->title }}</a>
                                <div class="text-muted" style="font-size: .78rem;">
                                    {{ $homework->created_at?->format('d.m.Y') }} · maks. {{ $homework->max_score }} ball
                                </div>
                            </td>
                            <td>{{ $homework->group->name ?? '—' }}</td>
                            <td>
                                @if($homework->due_date)
                                    <span class="badge bg-label-{{ $homework->isOverdue() ? 'danger' : 'info' }}">
                                        {{ $homework->dueLabel() }}
                                    </span>
                                    <div class="text-muted" style="font-size: .78rem;">
                                        {{ $homework->due_date->format('d.m.Y') }}
                                    </div>
                                @else
                                    <span class="badge bg-label-secondary">Muddatsiz</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <span class="fw-semibold">{{ $submitted }}</span>
                                <span class="text-muted">/ {{ $total }}</span>
                            </td>
                            <td class="text-center">
                                <span class="fw-semibold">{{ $graded }}</span>
                                <span class="text-muted">/ {{ $total }}</span>
                            </td>
                            <td class="text-center">
                                @if($avg === null)
                                    <span class="text-muted">—</span>
                                @else
                                    <span class="badge bg-label-{{ $avgTone }}">
                                        {{ number_format((float) $avg, 1, '.', ' ') }}
                                    </span>
                                @endif
                            </td>
                            <td>
                                <div class="d-flex justify-content-end gap-1">
                                    <a href="{{ route('homework.grade', $homework->id) }}"
                                       class="btn btn-sm btn-primary" title="Tekshirish va baholash">
                                        <i class="bx bx-check-square"></i>
                                    </a>
                                    <a href="{{ route('homework.edit', $homework->id) }}"
                                       class="btn btn-sm btn-outline-secondary" title="Tahrirlash">
                                        <i class="bx bx-edit-alt"></i>
                                    </a>
                                    <form action="{{ route('homework.destroy', $homework->id) }}" method="POST"
                                          onsubmit="return confirm('«{{ $homework->title }}» vazifasi va unga yuborilgan barcha javoblar o‘chirilsinmi?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="O‘chirish">
                                            <i class="bx bx-trash-alt"></i>
                                        </button>
                                    </form>
                                </div>
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
