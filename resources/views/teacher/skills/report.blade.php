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
        <div class="col-6 col-xl">
            <div class="card stat-card h-100">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <div class="stat-label">{{ $coreSkills ? '4 ko‘nikma o‘rtachasi' : 'Umumiy o‘rtacha' }}</div>
                        <div class="stat-value text-{{ $tone($groupOverall) }}" id="filteredOverall">{{ $groupOverall ?? '—' }}</div>
                    </div>
                    <span class="stat-icon is-info"><i class="bx bx-target-lock"></i></span>
                </div>
            </div>
        </div>
        <div class="col-6 col-xl">
            <div class="card stat-card h-100">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <div class="stat-label">Grammatika o‘rtachasi</div>
                        <div class="stat-value" id="filteredGrammar">{{ $groupGrammarAverage ?? '—' }}</div>
                    </div>
                    <span class="stat-icon is-warning"><i class="bx bx-spreadsheet"></i></span>
                </div>
            </div>
        </div>
        <div class="col-6 col-xl">
            <div class="card stat-card h-100">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <div class="stat-label">Qo‘yilgan baho</div>
                        <div class="stat-value" id="filteredMarks">{{ $totalMarks }}</div>
                    </div>
                    <span class="stat-icon"><i class="bx bx-list-check"></i></span>
                </div>
            </div>
        </div>
        <div class="col-6 col-xl">
            <div class="card stat-card h-100">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <div class="stat-label">Talabalar</div>
                        <div class="stat-value" id="filteredStudentTotal">{{ $students->count() }}</div>
                    </div>
                    <span class="stat-icon is-success"><i class="bx bx-user"></i></span>
                </div>
            </div>
        </div>
        <div class="col-6 col-xl">
            <div class="card stat-card h-100">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <div class="stat-label">Baholanmagan</div>
                        <div class="stat-value">
                            <span id="filteredUngraded">{{ $students->filter(fn($s) => ($overall[$s->id] ?? null) === null)->count() }}</span>
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
            <div class="filter-card d-flex flex-wrap align-items-end gap-3">
                <div class="flex-grow-1" style="max-width: 26rem;">
                    <label class="form-label" for="studentFilter">Talabani qidirish</label>
                    <input type="search" id="studentFilter" class="form-control"
                           placeholder="Ism bo‘yicha filtrlash" autocomplete="off">
                </div>
                <div class="small text-muted" id="studentFilterCount" aria-live="polite">
                    {{ $students->count() }} ta talaba
                </div>
            </div>
            <div class="table-responsive" id="skillReportTable">
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
                        <th style="min-width: 9rem;">{{ $coreSkills ? '4 ko‘nikma o‘rtachasi' : 'Umumiy o‘rtacha' }}</th>
                    </tr>
                    </thead>
                    <tbody id="myTable">
                    @foreach($students as $student)
                        <tr data-student-row
                            data-overall="{{ $overall[$student->id] ?? '' }}"
                            data-grammar="{{ $matrix[$student->id]['grammar']['avg'] ?? '' }}"
                            data-marks="{{ collect($matrix[$student->id] ?? [])->sum('marks') }}">
                            <td data-student-overall>
                                <a href="{{ route('progress.student', $student->id) }}" class="fw-semibold">
                                    {{ $student->name }}
                                </a>
                            </td>

                            @foreach($skills as $skill)
                                @php $cell = $matrix[$student->id][$skill] ?? null; @endphp
                                <td data-skill-score="{{ $skill }}" data-score="{{ $cell['avg'] ?? '' }}">
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
                            <th>Filtrlangan o‘rtacha</th>
                        @foreach($skills as $skill)
                            <th data-group-skill="{{ $skill }}">
                                @if(($groupAverages[$skill] ?? null) === null)
                                    <span class="text-muted fw-normal" data-aggregate-value>—</span>
                                @else
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="text-{{ $tone($groupAverages[$skill]) }}" data-aggregate-value
                                              style="min-width: 2rem;">{{ $groupAverages[$skill] }}</span>
                                        <div class="score-bar is-{{ $tone($groupAverages[$skill]) }} flex-grow-1">
                                            <span data-aggregate-bar style="width: {{ $groupAverages[$skill] }}%;"></span>
                                        </div>
                                    </div>
                                @endif
                            </th>
                        @endforeach
                        <th>
                            <span class="badge bg-{{ $tone($groupOverall) }}" id="filteredOverallFooter">{{ $groupOverall ?? '—' }}</span>
                        </th>
                    </tr>
                    </tfoot>
                </table>
            </div>
            <div class="empty-state d-none" id="studentFilterEmpty">
                <i class="bx bx-search-alt"></i>
                <h6>Talaba topilmadi</h6>
                <p class="mb-0">Qidiruv so‘zini o‘zgartirib ko‘ring.</p>
            </div>
        @endif
    </div>

    @if($totalMarks > 0)
        <script>
            (function () {
                const input = document.getElementById('studentFilter');
                const rows = [...document.querySelectorAll('[data-student-row]')];
                const table = document.getElementById('skillReportTable');
                const empty = document.getElementById('studentFilterEmpty');
                const average = values => {
                    const scored = values.filter(value => value !== null && value !== '');
                    return scored.length ? Math.round(scored.reduce((sum, value) => sum + Number(value), 0) / scored.length) : null;
                };
                const tone = value => value === null ? 'secondary' : (value >= 80 ? 'success' : (value >= 60 ? 'warning' : 'danger'));
                const show = (element, value) => {
                    if (element) element.textContent = value === null ? '—' : value;
                };

                function update() {
                    const query = input.value.trim().toLocaleLowerCase();
                    const visible = rows.filter(row => {
                        const name = row.querySelector('td a')?.textContent.trim().toLocaleLowerCase() ?? '';
                        const matches = name.includes(query);
                        row.classList.toggle('d-none', !matches);
                        return matches;
                    });
                    const overall = average(visible.map(row => row.dataset.overall));
                    const grammar = average(visible.map(row => row.dataset.grammar));
                    const count = visible.length;
                    const ungraded = visible.filter(row => row.dataset.overall === '').length;
                    const marks = visible.reduce((sum, row) => sum + Number(row.dataset.marks || 0), 0);

                    show(document.getElementById('filteredOverall'), overall);
                    show(document.getElementById('filteredOverallFooter'), overall);
                    show(document.getElementById('filteredGrammar'), grammar);
                    show(document.getElementById('filteredStudentTotal'), count);
                    show(document.getElementById('filteredUngraded'), ungraded);
                    show(document.getElementById('filteredMarks'), marks);
                    show(document.getElementById('studentFilterCount'), `${count} ta talaba`);

                    const overallCard = document.getElementById('filteredOverall');
                    const overallFooter = document.getElementById('filteredOverallFooter');
                    overallCard.className = `stat-value text-${tone(overall)}`;
                    overallFooter.className = `badge bg-${tone(overall)}`;

                    document.querySelectorAll('[data-group-skill]').forEach(cell => {
                        const skill = cell.dataset.groupSkill;
                        const skillAverage = average(visible.map(row =>
                            row.querySelector(`[data-skill-score="${skill}"]`)?.dataset.score ?? ''
                        ));
                        show(cell.querySelector('[data-aggregate-value]'), skillAverage);
                        const bar = cell.querySelector('[data-aggregate-bar]');
                        if (bar) bar.style.width = `${skillAverage ?? 0}%`;
                    });

                    table.classList.toggle('d-none', count === 0);
                    empty.classList.toggle('d-none', count !== 0);
                }

                input.addEventListener('input', update);
            })();
        </script>
    @endif

@endsection
