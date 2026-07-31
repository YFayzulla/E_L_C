@extends('template.master')

@section('title', $homework->title)
@section('subtitle', ($homework->group->name ?? 'Guruh') . ' uchun uy vazifasi')

@section('content')

    @php
        $bands = config('grading.bands');
        $max = max(1, (int) $homework->max_score);
        $avgPct = $averageScore !== null ? ($averageScore / $max) * 100 : null;
        $avgTone = $avgPct === null
            ? 'info'
            : ($avgPct >= $bands['good'] ? 'success' : ($avgPct >= $bands['ok'] ? 'warning' : 'danger'));
        $pending = max(0, $rosterCount - $submitted);
    @endphp

    <div class="page-head">
        <div class="page-sub">
            <i class="bx bx-user-voice me-1"></i>{{ $homework->author->name ?? '—' }}
            <span class="mx-1">·</span>
            <i class="bx bx-calendar me-1"></i>{{ $homework->created_at?->format('d.m.Y') }}
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('homework.index') }}" class="btn btn-outline-secondary">
                <i class="bx bx-arrow-back me-1"></i> Ro‘yxat
            </a>
            <a href="{{ route('homework.edit', $homework->id) }}" class="btn btn-outline-secondary">
                <i class="bx bx-edit-alt me-1"></i> Tahrirlash
            </a>
            <a href="{{ route('homework.grade', $homework->id) }}" class="btn btn-primary">
                <i class="bx bx-check-square me-1"></i> Tekshirish va baholash
            </a>
        </div>
    </div>

    {{-- Summary tiles --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="card stat-card h-100">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <div class="stat-label">Topshirgan</div>
                        <div class="stat-value">{{ $submitted }} <span class="text-muted">/ {{ $rosterCount }}</span></div>
                    </div>
                    <span class="stat-icon is-success"><i class="bx bx-check-circle"></i></span>
                </div>
            </div>
        </div>

        <div class="col-6 col-lg-3">
            <div class="card stat-card h-100">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <div class="stat-label">Kutilmoqda</div>
                        <div class="stat-value">{{ $pending }}</div>
                    </div>
                    <span class="stat-icon is-warning"><i class="bx bx-time-five"></i></span>
                </div>
            </div>
        </div>

        <div class="col-6 col-lg-3">
            <div class="card stat-card h-100">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <div class="stat-label">Baholangan</div>
                        <div class="stat-value">{{ $graded }} <span class="text-muted">/ {{ $rosterCount }}</span></div>
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
                        <div class="stat-value text-{{ $averageScore === null ? '' : $avgTone }}">
                            {{ $averageScore === null ? '—' : number_format((float) $averageScore, 1, '.', ' ') }}
                        </div>
                    </div>
                    <span class="stat-icon is-{{ $avgTone }}"><i class="bx bx-bar-chart-alt-2"></i></span>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">

        {{-- Assignment --}}
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header">Topshiriq</div>
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between pb-3 mb-3 border-bottom">
                        <span class="text-muted">Guruh</span>
                        <span class="fw-semibold">{{ $homework->group->name ?? '—' }}</span>
                    </div>
                    <div class="d-flex align-items-center justify-content-between pb-3 mb-3 border-bottom">
                        <span class="text-muted">Muddat</span>
                        @if($homework->due_date)
                            <span class="badge bg-label-{{ $homework->isOverdue() ? 'danger' : 'info' }}">
                                {{ $homework->due_date->format('d.m.Y') }} · {{ $homework->dueLabel() }}
                            </span>
                        @else
                            <span class="badge bg-label-secondary">Muddatsiz</span>
                        @endif
                    </div>
                    <div class="d-flex align-items-center justify-content-between pb-3 mb-3 border-bottom">
                        <span class="text-muted">Maksimal ball</span>
                        <span class="fw-semibold">{{ $homework->max_score }}</span>
                    </div>
                    <div class="d-flex align-items-center justify-content-between {{ $homework->attachment ? 'pb-3 mb-3 border-bottom' : '' }}">
                        <span class="text-muted">Fayl biriktirish</span>
                        <span class="badge bg-label-{{ $homework->allow_file ? 'success' : 'secondary' }}">
                            {{ $homework->allow_file ? 'Ruxsat etilgan' : 'Taqiqlangan' }}
                        </span>
                    </div>

                    @if($homework->attachment)
                        <a href="{{ asset('storage/' . $homework->attachment) }}" target="_blank"
                           class="btn btn-outline-secondary btn-sm w-100">
                            <i class="bx bx-paperclip me-1"></i> Ilovani ochish
                        </a>
                    @endif

                    @if(filled($homework->description))
                        <div class="mt-3 pt-3 border-top">
                            <div class="text-muted mb-2" style="font-size: .82rem;">Topshiriq matni</div>
                            <div style="white-space: pre-line;">{{ $homework->description }}</div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Submissions --}}
        <div class="col-lg-8">
            <div class="card h-100">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <span>Yuborilgan javoblar</span>
                    <a href="{{ route('homework.grade', $homework->id) }}" class="btn btn-sm btn-outline-secondary">
                        Baholash varag‘i
                    </a>
                </div>

                @if($submissions->isEmpty())
                    <div class="empty-state">
                        <i class="bx bx-archive"></i>
                        <h6>Javob yo‘q</h6>
                        <p class="mb-0">Hozircha hech kim bu vazifani topshirmagan.</p>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                            <tr>
                                <th>Talaba</th>
                                <th>Holat</th>
                                <th>Topshirilgan</th>
                                <th>Javob</th>
                                <th class="text-end">Ball</th>
                            </tr>
                            </thead>
                            <tbody id="myTable">
                            @foreach($submissions as $submission)
                                <tr>
                                    <td class="fw-semibold min-w-0">{{ $submission->student->name ?? '—' }}</td>
                                    <td>
                                        <span class="badge bg-label-{{ $submission->statusTone() }}">
                                            {{ $submission->statusLabel() }}
                                        </span>
                                    </td>
                                    <td class="text-muted">
                                        {{ $submission->submitted_at?->format('d.m.Y H:i') ?? '—' }}
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            @if(filled($submission->submission_text))
                                                <i class="bx bx-text text-muted" title="Matnli javob"></i>
                                            @endif
                                            @if($submission->submission_file)
                                                <a href="{{ asset('storage/' . $submission->submission_file) }}"
                                                   target="_blank" class="text-decoration-none" title="Faylni yuklab olish">
                                                    <i class="bx bx-paperclip"></i>
                                                </a>
                                            @endif
                                            @if(! filled($submission->submission_text) && ! $submission->submission_file)
                                                <span class="text-muted">—</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="text-end">
                                        @if($submission->score === null)
                                            <span class="text-muted">Baholanmagan</span>
                                        @else
                                            <span class="badge bg-label-{{ $submission->scoreTone($homework->max_score) }}">
                                                {{ $submission->score }} / {{ $homework->max_score }}
                                            </span>
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
