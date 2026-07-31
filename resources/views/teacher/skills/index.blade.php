@extends('template.master')

@section('title', 'Ko‘nikma baholari')
@section('subtitle', 'Dars davomida reading, listening, writing va speaking uchun baho qo‘yish')

@section('content')

    <div class="page-head justify-content-end">
        <div class="page-sub me-auto">
            {{ $groups->count() }} ta guruh · baholash uchun guruhni tanlang
        </div>
        <a href="{{ route('attendance') }}" class="btn btn-outline-secondary">
            <i class="bx bx-calendar-check me-1"></i> Davomat
        </a>
    </div>

    @if($groups->isEmpty())
        <div class="card">
            <div class="empty-state">
                <i class="bx bx-group"></i>
                <h6>Guruh biriktirilmagan</h6>
                <p class="mb-0">Sizga hali guruh biriktirilmagan. Administrator bilan bog‘laning.</p>
            </div>
        </div>
    @else
        <div class="row g-3">
            @foreach($groups as $group)
                @php
                    $stats     = $lessonStats->get($group->id);
                    $lastAt    = $stats && $stats->last_lesson_at ? \Carbon\Carbon::parse($stats->last_lesson_at) : null;
                    $lessons   = $stats ? (int) $stats->lessons_count : 0;
                    $graded    = (int) ($gradeCounts[$group->id] ?? 0);
                    $canGrade  = $lessons > 0;
                @endphp

                <div class="col-md-6 col-xl-4">
                    <div class="card card-hover h-100">
                        <div class="card-body d-flex flex-column">
                            <div class="d-flex align-items-start gap-3 mb-3">
                                <span class="stat-icon is-info"><i class="bx bx-chalkboard"></i></span>
                                <div class="flex-grow-1 min-w-0">
                                    <h6 class="mb-1 text-truncate">{{ $group->name }}</h6>
                                    <div class="text-muted" style="font-size: .82rem;">
                                        <i class="bx bx-user me-1"></i>{{ $group->members_count }} ta talaba
                                        @if($group->room)
                                            <span class="mx-1">·</span>
                                            <i class="bx bx-door-open me-1"></i>{{ $group->room->room }}-xona
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <div class="row g-2 mb-3">
                                <div class="col-6">
                                    <div class="metric-box">
                                        <div class="metric-value">{{ $lastAt ? $lastAt->format('d.m.Y') : '—' }}</div>
                                        <div class="metric-label">Oxirgi dars</div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="metric-box">
                                        <div class="metric-value">{{ $graded }}</div>
                                        <div class="metric-label">Qo‘yilgan baho</div>
                                    </div>
                                </div>
                            </div>

                            @unless($canGrade)
                                <div class="text-muted mb-3" style="font-size: .82rem;">
                                    <i class="bx bx-info-circle me-1"></i>
                                    Bu guruhda hali dars qayd etilmagan.
                                </div>
                            @endunless

                            <div class="mt-auto d-flex gap-2">
                                @if($canGrade)
                                    <a href="{{ route('skills.grade', $group->id) }}" class="btn btn-primary btn-sm flex-grow-1">
                                        <i class="bx bx-edit me-1"></i> Baho qo‘yish
                                    </a>
                                @else
                                    <a href="{{ route('attendance') }}" class="btn btn-outline-secondary btn-sm flex-grow-1">
                                        <i class="bx bx-calendar-plus me-1"></i> Avval davomat oling
                                    </a>
                                @endif
                                <a href="{{ route('skills.report', $group->id) }}" class="btn btn-outline-secondary btn-sm"
                                   title="Hisobot">
                                    <i class="bx bx-bar-chart-alt-2"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

@endsection
