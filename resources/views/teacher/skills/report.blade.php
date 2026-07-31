@extends('template.master')

@section('title', 'Ko‘nikma hisoboti')
@section('subtitle', $group->name)

@section('content')

    @php
        $labels = (array) config('grading.skill_labels', []);
        $icons  = (array) config('grading.skill_icons', []);
        $good   = config('grading.bands.good', 80);
        $ok     = config('grading.bands.ok', 60);
        $tone   = fn($v) => $v === null ? 'secondary' : ($v >= $good ? 'success' : ($v >= $ok ? 'warning' : 'danger'));
    @endphp

    <div class="page-head">
        <div class="d-flex align-items-center gap-3">
            <a href="{{ auth()->user()->hasRole('admin') ? route('group.index') : route('skills.groups') }}"
               class="btn-icon" title="Orqaga">
                <i class="bx bx-arrow-back"></i>
            </a>
            <div class="page-sub mb-0">
                {{ $from->format('d.m.Y') }} – {{ $to->format('d.m.Y') }} oralig‘idagi o‘rtacha baholar
            </div>
        </div>
        <div class="d-flex gap-2">
            @unless(auth()->user()->hasRole('admin'))
                <a href="{{ route('skills.grade', $group->id) }}" class="btn btn-outline-secondary">
                    <i class="bx bx-edit me-1"></i> Baho qo‘yish
                </a>
            @endunless
            <a href="{{ route('progress.group', $group->id) }}" class="btn btn-outline-secondary">
                <i class="bx bx-line-chart me-1"></i> Dinamika
            </a>
        </div>
    </div>

    {{-- Summary --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="card stat-card h-100">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <div class="stat-label">Guruh o‘rtachasi</div>
                        <div class="stat-value text-{{ $tone($groupOverall) }}">{{ $groupOverall ?? '—' }}</div>
                    </div>
                    <span class="stat-icon is-info"><i class="bx bx-target-lock"></i></span>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card stat-card h-100">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <div class="stat-label">Qo‘yilgan baho</div>
                        <div class="stat-value">{{ $totalMarks }}</div>
                    </div>
                    <span class="stat-icon"><i class="bx bx-list-check"></i></span>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card stat-card h-100">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <div class="stat-label">Talabalar</div>
                        <div class="stat-value">{{ $students->count() }}</div>
                    </div>
                    <span class="stat-icon is-success"><i class="bx bx-user"></i></span>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card stat-card h-100">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <div class="stat-label">Baholanmagan</div>
                        <div class="stat-value">
                            {{ $students->filter(fn($s) => ($overall[$s->id] ?? null) === null)->count() }}
                        </div>
                    </div>
                    <span class="stat-icon is-warning"><i class="bx bx-user-x"></i></span>
                </div>
            </div>
        </div>
    </div>

    {{-- Date range --}}
    <form method="GET" action="{{ route('skills.report', $group->id) }}" class="filter-card">
        <div class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label" for="from">Boshlanish sanasi</label>
                <input type="date" id="from" name="from" class="form-control" value="{{ $from->format('Y-m-d') }}">
            </div>
            <div class="col-md-4">
                <label class="form-label" for="to">Tugash sanasi</label>
                <input type="date" id="to" name="to" class="form-control" value="{{ $to->format('Y-m-d') }}">
            </div>
            <div class="col-md-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bx bx-filter-alt me-1"></i> Filtrlash
                </button>
                <a href="{{ route('skills.report', $group->id) }}" class="btn btn-outline-secondary">Tozalash</a>
            </div>
        </div>
    </form>

    <div class="card">
        @if($students->isEmpty())
            <div class="empty-state">
                <i class="bx bx-user-x"></i>
                <h6>Guruhda talaba yo‘q</h6>
                <p class="mb-0">Bu guruhga hali talaba biriktirilmagan.</p>
            </div>
        @elseif($totalMarks === 0)
            <div class="empty-state">
                <i class="bx bx-bar-chart-alt-2"></i>
                <h6>Bu oraliqda baho yo‘q</h6>
                <p class="mb-0">Tanlangan sanalar oralig‘ida ko‘nikma bahosi qo‘yilmagan.</p>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover grade-table mb-0">
                    <thead>
                    <tr>
                        <th style="min-width: 12rem;">Talaba</th>
                        @foreach($skills as $skill)
                            <th style="min-width: 9rem;">
                                <i class="bx {{ $icons[$skill] ?? 'bx-star' }} me-1"></i>
                                {{ $labels[$skill] ?? ucfirst($skill) }}
                            </th>
                        @endforeach
                        <th style="min-width: 7rem;">O‘rtacha</th>
                    </tr>
                    </thead>
                    <tbody id="myTable">
                    @foreach($students as $student)
                        <tr>
                            <td>
                                <a href="{{ route('progress.student', $student->id) }}" class="fw-semibold">
                                    {{ $student->name }}
                                </a>
                            </td>

                            @foreach($skills as $skill)
                                @php $cell = $matrix[$student->id][$skill] ?? null; @endphp
                                <td>
                                    @if($cell === null)
                                        <span class="text-muted">—</span>
                                    @else
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="fw-semibold text-{{ $tone($cell['avg']) }}"
                                                  style="min-width: 2rem;">{{ $cell['avg'] }}</span>
                                            <div class="score-bar is-{{ $tone($cell['avg']) }} flex-grow-1">
                                                <span style="width: {{ $cell['avg'] }}%;"></span>
                                            </div>
                                        </div>
                                        <div class="text-muted" style="font-size: .72rem;">{{ $cell['marks'] }} ta baho</div>
                                    @endif
                                </td>
                            @endforeach

                            <td>
                                @if(($overall[$student->id] ?? null) === null)
                                    <span class="text-muted">—</span>
                                @else
                                    <span class="badge bg-label-{{ $tone($overall[$student->id]) }}">
                                        {{ $overall[$student->id] }}
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                    <tfoot>
                    <tr>
                        <th>Guruh o‘rtachasi</th>
                        @foreach($skills as $skill)
                            <th>
                                @if(($groupAverages[$skill] ?? null) === null)
                                    <span class="text-muted fw-normal">—</span>
                                @else
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="text-{{ $tone($groupAverages[$skill]) }}"
                                              style="min-width: 2rem;">{{ $groupAverages[$skill] }}</span>
                                        <div class="score-bar is-{{ $tone($groupAverages[$skill]) }} flex-grow-1">
                                            <span style="width: {{ $groupAverages[$skill] }}%;"></span>
                                        </div>
                                    </div>
                                @endif
                            </th>
                        @endforeach
                        <th>
                            @if($groupOverall === null)
                                <span class="text-muted fw-normal">—</span>
                            @else
                                <span class="badge bg-{{ $tone($groupOverall) }}">{{ $groupOverall }}</span>
                            @endif
                        </th>
                    </tr>
                    </tfoot>
                </table>
            </div>
        @endif
    </div>

@endsection
