@extends('template.master')

@section('title', 'Guruh dinamikasi')
@section('subtitle', $group->name)

@section('content')

    @php
        $good = config('grading.bands.good', 80);
        $ok   = config('grading.bands.ok', 60);
        $tone = fn($v) => $v === null ? 'secondary' : ($v >= $good ? 'success' : ($v >= $ok ? 'warning' : 'danger'));

        $current = $progress['current'] ?? null;
        $delta   = $progress['delta'] ?? null;
    @endphp

    <div class="page-head">
        <div class="d-flex align-items-center gap-3">
            <a href="{{ route('progress.index') }}" class="btn-icon" title="Orqaga">
                <i class="bx bx-arrow-back"></i>
            </a>
            <div class="page-sub mb-0">
                {{ $group->teachers->pluck('name')->implode(', ') ?: 'O‘qituvchi biriktirilmagan' }}
                <span class="mx-1">·</span>{{ $students->count() }} ta talaba
            </div>
        </div>
        <a href="{{ route('skills.report', $group->id) }}" class="btn btn-outline-secondary">
            <i class="bx bx-bar-chart-alt-2 me-1"></i> Ko‘nikma hisoboti
        </a>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="card stat-card h-100">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <div class="stat-label">Guruh ko‘rsatkichi</div>
                        <div class="stat-value text-{{ $tone($current) }}">{{ $current ?? '—' }}</div>
                        <div>
                            @if($delta === null)
                                <span class="delta is-flat">o‘tgan oy bilan solishtirib bo‘lmadi</span>
                            @elseif($delta > 0)
                                <span class="delta is-up"><i class="bx bx-up-arrow-alt"></i>+{{ $delta }}</span>
                            @elseif($delta < 0)
                                <span class="delta is-down"><i class="bx bx-down-arrow-alt"></i>{{ $delta }}</span>
                            @else
                                <span class="delta is-flat"><i class="bx bx-minus"></i>o‘zgarishsiz</span>
                            @endif
                        </div>
                    </div>
                    <span class="stat-icon is-info"><i class="bx bx-line-chart"></i></span>
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
                    <span class="stat-icon"><i class="bx bx-user"></i></span>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card stat-card h-100">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <div class="stat-label">Pasaygan</div>
                        <div class="stat-value text-danger">{{ $declining }}</div>
                    </div>
                    <span class="stat-icon is-danger"><i class="bx bx-trending-down"></i></span>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card stat-card h-100">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <div class="stat-label">{{ $ok }} balldan past</div>
                        <div class="stat-value text-warning">{{ $atRisk }}</div>
                    </div>
                    <span class="stat-icon is-warning"><i class="bx bx-error"></i></span>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">Guruhning oylik o‘rtachasi</div>
        <div class="card-body">
            @include('partials.progress-chart', [
                'chartId'    => 'groupProgressChart',
                'buckets'    => $progress['buckets'],
                'chartLabel' => 'Guruh o‘rtachasi',
            ])
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
            <span>Talabalar</span>
            <span class="text-muted" style="font-size: .8rem;">Eng ko‘p pasayganidan boshlab</span>
        </div>

        @if($students->isEmpty())
            <div class="empty-state">
                <i class="bx bx-user-x"></i>
                <h6>Talaba yo‘q</h6>
                <p class="mb-0">Bu guruhga hali talaba biriktirilmagan.</p>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                    <tr>
                        <th class="text-center" style="width: 3.5rem;">#</th>
                        <th style="min-width: 11rem;">Talaba</th>
                        <th class="text-center" style="width: 6.5rem;">Davomat</th>
                        <th class="text-center" style="width: 6.5rem;">Uy vazifa</th>
                        <th class="text-center" style="width: 7rem;">Dars faoliyati</th>
                        <th class="text-center" style="width: 6rem;">Test</th>
                        <th style="min-width: 10rem;">Umumiy reyting</th>
                        <th class="text-center" style="width: 6rem;">O‘rtacha</th>
                        <th class="text-center" style="width: 7rem;">O‘zgarish</th>
                        <th class="text-end" style="width: 5rem;"></th>
                    </tr>
                    </thead>
                    <tbody id="myTable">
                    @foreach($students as $row)
                        <tr>
                            <td class="text-center">
                                @if($row['rank'] === null)
                                    <span class="text-muted">—</span>
                                @else
                                    <span class="badge bg-label-{{ $row['rank'] <= 3 ? 'primary' : 'secondary' }}">
                                        {{ $row['rank'] }}
                                    </span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('progress.student', $row['id']) }}" class="fw-semibold">
                                    {{ $row['name'] }}
                                </a>
                                @if($row['basis'] === 'attendance_only')
                                    <div class="text-muted" style="font-size: .72rem;">faqat davomat asosida</div>
                                @endif
                            </td>

                            {{-- Joriy oy qiymati / butun davr o'rtachasi --}}
                            @foreach(['attendance', 'homework', 'skills', 'tests'] as $component)
                                @php
                                    $now = $row['components'][$component] ?? null;
                                    $avg = $row['averages'][$component] ?? null;
                                @endphp
                                <td class="text-center">
                                    @if($now === null && $avg === null)
                                        <span class="text-muted">—</span>
                                    @else
                                        <span class="fw-semibold text-{{ $tone($now ?? $avg) }}">
                                            {{ $now ?? $avg }}
                                        </span>
                                        @if($avg !== null && $now !== null && $avg !== $now)
                                            <div class="text-muted" style="font-size: .7rem;">o‘rt. {{ $avg }}</div>
                                        @endif
                                    @endif
                                </td>
                            @endforeach

                            <td>
                                @if($row['current'] === null)
                                    <span class="text-muted">— ma’lumot yo‘q</span>
                                @else
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="fw-semibold text-{{ $tone($row['current']) }}"
                                              style="min-width: 2rem;">{{ $row['current'] }}</span>
                                        <div class="score-bar is-{{ $tone($row['current']) }} flex-grow-1">
                                            <span style="width: {{ $row['current'] }}%;"></span>
                                        </div>
                                    </div>
                                @endif
                            </td>

                            <td class="text-center">
                                @if($row['average'] === null)
                                    <span class="text-muted">—</span>
                                @else
                                    <span class="fw-semibold text-{{ $tone($row['average']) }}">{{ $row['average'] }}</span>
                                @endif
                            </td>

                            <td class="text-center">
                                @if($row['delta'] === null)
                                    <span class="delta is-flat">—</span>
                                @elseif($row['delta'] > 0)
                                    <span class="delta is-up"><i class="bx bx-up-arrow-alt"></i>+{{ $row['delta'] }}</span>
                                @elseif($row['delta'] < 0)
                                    <span class="delta is-down"><i class="bx bx-down-arrow-alt"></i>{{ $row['delta'] }}</span>
                                @else
                                    <span class="delta is-flat"><i class="bx bx-minus"></i>0</span>
                                @endif
                            </td>

                            <td>
                                <div class="d-flex justify-content-end">
                                    <a href="{{ route('progress.student', $row['id']) }}"
                                       class="btn btn-sm btn-outline-secondary" title="Batafsil">
                                        <i class="bx bx-right-arrow-alt"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>

                    {{-- Guruh o'rtachasi --}}
                    <tfoot>
                    <tr style="border-top: 2px solid var(--app-border-strong);">
                        <td></td>
                        <td class="fw-semibold">Guruh o‘rtachasi</td>
                        @foreach(['attendance', 'homework', 'skills', 'tests'] as $component)
                            @php $avg = $progress['averages'][$component] ?? null; @endphp
                            <td class="text-center fw-semibold">
                                @if($avg === null)
                                    <span class="text-muted">—</span>
                                @else
                                    <span class="text-{{ $tone($avg) }}">{{ $avg }}</span>
                                @endif
                            </td>
                        @endforeach
                        <td>
                            @if(($progress['current'] ?? null) !== null)
                                <span class="fw-semibold text-{{ $tone($progress['current']) }}">
                                    {{ $progress['current'] }}
                                </span>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td class="text-center fw-semibold">
                            @if(($progress['average'] ?? null) !== null)
                                <span class="text-{{ $tone($progress['average']) }}">{{ $progress['average'] }}</span>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td colspan="2"></td>
                    </tr>
                    </tfoot>
                </table>
            </div>

            <div class="card-body pt-3 pb-0">
                <p class="text-muted mb-0" style="font-size: .78rem;">
                    <i class="bx bx-info-circle me-1"></i>
                    Katta raqam — oxirgi ma’lumot bo‘lgan oy, ostidagi kichik raqam — butun davr o‘rtachasi.
                    Ko‘rsatkichlar talaba <strong>o‘sha paytda qaysi guruhda bo‘lgan</strong> bo‘lsa o‘shanga qarab
                    hisoblanadi, shuning uchun guruh o‘zgarganda tarix yo‘qolmaydi.
                </p>
            </div>
        @endif
    </div>

@endsection
