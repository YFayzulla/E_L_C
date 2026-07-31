@extends('template.master')

@section('title', 'O‘zlashtirish dinamikasi')
@section('subtitle', 'Guruhlar kesimidagi umumiy ko‘rsatkich')

@section('content')

    @php
        $good = config('grading.bands.good', 80);
        $ok   = config('grading.bands.ok', 60);
        $tone = fn($v) => $v === null ? 'secondary' : ($v >= $good ? 'success' : ($v >= $ok ? 'warning' : 'danger'));

        $declining = collect($progress)->filter(fn($p) => ($p['delta'] ?? null) !== null && $p['delta'] < 0)->count();
        $rising    = collect($progress)->filter(fn($p) => ($p['delta'] ?? null) !== null && $p['delta'] > 0)->count();
    @endphp

    <div class="page-head justify-content-end">
        <div class="page-sub me-auto">
            Ko‘rsatkich davomat, test, uy vazifasi va ko‘nikma baholaridan hisoblanadi.
        </div>
        @role('user')
        <a href="{{ route('skills.groups') }}" class="btn btn-outline-secondary">
            <i class="bx bx-award me-1"></i> Ko‘nikma baholari
        </a>
        @endrole
    </div>

    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="card stat-card h-100">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <div class="stat-label">Umumiy o‘rtacha</div>
                        <div class="stat-value text-{{ $tone($overall) }}">{{ $overall ?? '—' }}</div>
                    </div>
                    <span class="stat-icon is-info"><i class="bx bx-line-chart"></i></span>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card stat-card h-100">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <div class="stat-label">Guruhlar</div>
                        <div class="stat-value">{{ $groups->count() }}</div>
                        <div class="text-muted" style="font-size: .8rem;">{{ $measured }} tasida ma’lumot bor</div>
                    </div>
                    <span class="stat-icon"><i class="bx bx-group"></i></span>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card stat-card h-100">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <div class="stat-label">O‘sish</div>
                        <div class="stat-value text-success">{{ $rising }}</div>
                    </div>
                    <span class="stat-icon is-success"><i class="bx bx-trending-up"></i></span>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card stat-card h-100">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <div class="stat-label">Pasayish</div>
                        <div class="stat-value text-danger">{{ $declining }}</div>
                    </div>
                    <span class="stat-icon is-danger"><i class="bx bx-trending-down"></i></span>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
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
                        <th>O‘qituvchi</th>
                        <th class="text-center" style="width: 6rem;">Talaba</th>
                        <th style="min-width: 12rem;">Joriy ko‘rsatkich</th>
                        <th class="text-center" style="width: 7rem;">O‘zgarish</th>
                        <th class="text-end" style="width: 8rem;">Amallar</th>
                    </tr>
                    </thead>
                    <tbody id="myTable">
                    @foreach($groups as $group)
                        @php
                            $row     = $progress[$group->id] ?? ['current' => null, 'delta' => null];
                            $current = $row['current'] ?? null;
                            $delta   = $row['delta'] ?? null;
                        @endphp
                        <tr>
                            <td>
                                <a href="{{ route('progress.group', $group->id) }}" class="fw-semibold">
                                    {{ $group->name }}
                                </a>
                            </td>
                            <td class="text-muted">{{ $group->teachers->pluck('name')->implode(', ') ?: '—' }}</td>
                            <td class="text-center">{{ $group->members_count }}</td>
                            <td>
                                @if($current === null)
                                    <span class="text-muted">— ma’lumot yo‘q</span>
                                @else
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="fw-semibold text-{{ $tone($current) }}"
                                              style="min-width: 2rem;">{{ $current }}</span>
                                        <div class="score-bar is-{{ $tone($current) }} flex-grow-1">
                                            <span style="width: {{ $current }}%;"></span>
                                        </div>
                                    </div>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($delta === null)
                                    <span class="delta is-flat">—</span>
                                @elseif($delta > 0)
                                    <span class="delta is-up"><i class="bx bx-up-arrow-alt"></i>+{{ $delta }}</span>
                                @elseif($delta < 0)
                                    <span class="delta is-down"><i class="bx bx-down-arrow-alt"></i>{{ $delta }}</span>
                                @else
                                    <span class="delta is-flat"><i class="bx bx-minus"></i>0</span>
                                @endif
                            </td>
                            <td>
                                <div class="d-flex justify-content-end gap-1">
                                    <a href="{{ route('progress.group', $group->id) }}"
                                       class="btn btn-sm btn-outline-secondary" title="Dinamika">
                                        <i class="bx bx-line-chart"></i>
                                    </a>
                                    <a href="{{ route('skills.report', $group->id) }}"
                                       class="btn btn-sm btn-outline-secondary" title="Ko‘nikma hisoboti">
                                        <i class="bx bx-bar-chart-alt-2"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

@endsection
