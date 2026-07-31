@extends('template.master')

@section('title', $homework->title)
@section('subtitle', ($homework->group->name ?? 'Guruh') . ' uy vazifasi')

@section('content')

    @php
        $upload = config('grading.homework_upload');
        $isSubmitted = $submission && $submission->submitted_at !== null;
        $isGraded = $submission && $submission->score !== null;
        $overdue = $homework->isOverdue();
    @endphp

    <div class="page-head">
        <div class="page-sub">
            <i class="bx bx-user-voice me-1"></i>{{ $homework->author->name ?? 'O‘qituvchi' }}
            <span class="mx-1">·</span>
            <i class="bx bx-medal me-1"></i>Maksimal {{ $homework->max_score }} ball
        </div>
        <a href="{{ route('student.homework') }}" class="btn btn-outline-secondary">
            <i class="bx bx-arrow-back me-1"></i> Vazifalarim
        </a>
    </div>

    <div class="row g-4">

        {{-- Assignment --}}
        <div class="col-lg-5">
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
                            <span class="badge bg-label-{{ $overdue ? 'danger' : 'info' }}">
                                {{ $homework->due_date->format('d.m.Y') }} · {{ $homework->dueLabel() }}
                            </span>
                        @else
                            <span class="badge bg-label-secondary">Muddatsiz</span>
                        @endif
                    </div>
                    <div class="d-flex align-items-center justify-content-between">
                        <span class="text-muted">Holat</span>
                        @if($isGraded)
                            <span class="badge bg-label-{{ $submission->scoreTone($homework->max_score) }}">
                                Baholandi: {{ $submission->score }} / {{ $homework->max_score }}
                            </span>
                        @elseif($isSubmitted)
                            <span class="badge bg-label-{{ $submission->statusTone() }}">
                                {{ (int) $submission->status === 2 ? 'Kech topshirildi' : 'Topshirildi' }}
                            </span>
                        @else
                            <span class="badge bg-label-{{ $overdue ? 'danger' : 'warning' }}">Topshirilmagan</span>
                        @endif
                    </div>

                    @if(filled($homework->description))
                        <div class="mt-3 pt-3 border-top">
                            <div class="text-muted mb-2" style="font-size: .82rem;">Topshiriq matni</div>
                            <div style="white-space: pre-line;">{{ $homework->description }}</div>
                        </div>
                    @endif

                    @if($homework->attachment)
                        <a href="{{ asset('storage/' . $homework->attachment) }}" target="_blank"
                           class="btn btn-outline-secondary btn-sm w-100 mt-3">
                            <i class="bx bx-paperclip me-1"></i> O‘qituvchi ilovasini ochish
                        </a>
                    @endif
                </div>
            </div>
        </div>

        {{-- Hand-in --}}
        <div class="col-lg-7">
            @if($isGraded)
                <div class="card mb-4">
                    <div class="card-header d-flex align-items-center justify-content-between">
                        <span>Baho</span>
                        <span class="badge bg-label-{{ $submission->scoreTone($homework->max_score) }}">
                            {{ $submission->score }} / {{ $homework->max_score }}
                        </span>
                    </div>
                    <div class="card-body">
                        @php
                            $pct = $homework->max_score > 0
                                ? min(100, round(($submission->score / $homework->max_score) * 100))
                                : 0;
                        @endphp
                        <div class="score-bar is-{{ $submission->scoreTone($homework->max_score) }} mb-3">
                            <span style="width: {{ $pct }}%;"></span>
                        </div>
                        @if(filled($submission->comment))
                            <div class="text-muted mb-1" style="font-size: .82rem;">O‘qituvchi izohi</div>
                            <div style="white-space: pre-line;">{{ $submission->comment }}</div>
                        @else
                            <div class="text-muted">O‘qituvchi izoh qoldirmagan.</div>
                        @endif
                        @if($submission->graded_at)
                            <div class="text-muted mt-3" style="font-size: .78rem;">
                                <i class="bx bx-time me-1"></i>{{ $submission->graded_at->format('d.m.Y H:i') }} da baholandi
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            <form action="{{ route('student.homework.submit', $homework->id) }}" method="POST"
                  enctype="multipart/form-data"
                  onsubmit="return confirm('{{ $isSubmitted ? 'Javobingiz yangilansinmi?' : 'Javobingiz topshirilsinmi?' }}');">
                @csrf

                <div class="card">
                    <div class="card-header d-flex align-items-center justify-content-between">
                        <span>{{ $isSubmitted ? 'Javobingiz' : 'Javob yuborish' }}</span>
                        @if($isSubmitted)
                            <span class="text-muted" style="font-size: .8rem;">
                                <i class="bx bx-check me-1"></i>{{ $submission->submitted_at->format('d.m.Y H:i') }}
                            </span>
                        @endif
                    </div>
                    <div class="card-body">
                        @if($overdue && ! $isSubmitted)
                            <div class="d-flex align-items-start gap-2 p-2 mb-3 rounded"
                                 style="background: var(--app-surface-2); border: 1px solid var(--app-border);">
                                <i class="bx bx-error-circle text-danger"></i>
                                <div style="font-size: .85rem;">
                                    Muddat tugagan — javobingiz «kech topshirilgan» deb belgilanadi.
                                </div>
                            </div>
                        @endif

                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label" for="submission_text">Javob matni</label>
                                <textarea id="submission_text" name="submission_text" rows="7"
                                          class="form-control @error('submission_text') is-invalid @enderror"
                                          placeholder="Javobingizni shu yerga yozing…">{{ old('submission_text', $submission?->submission_text) }}</textarea>
                                @error('submission_text') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            @if($homework->allow_file)
                                <div class="col-12">
                                    <label class="form-label" for="submission_file">Fayl (ixtiyoriy)</label>
                                    <input type="file" id="submission_file" name="submission_file"
                                           class="form-control @error('submission_file') is-invalid @enderror">
                                    @error('submission_file') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    <div class="form-text">
                                        Ruxsat etilgan turlar: {{ implode(', ', $upload['mimes']) }}.
                                        Maksimal hajm: {{ round($upload['max_kb'] / 1024) }} MB.
                                    </div>

                                    @if($submission?->submission_file)
                                        <div class="d-flex align-items-center justify-content-between gap-2 mt-3 p-2 rounded"
                                             style="background: var(--app-surface-2); border: 1px solid var(--app-border);">
                                            <span class="min-w-0 text-truncate">
                                                <i class="bx bx-paperclip me-1"></i>{{ basename($submission->submission_file) }}
                                            </span>
                                            <a href="{{ asset('storage/' . $submission->submission_file) }}" target="_blank"
                                               class="btn btn-sm btn-outline-secondary flex-shrink-0">
                                                <i class="bx bx-download me-1"></i> Yuklab olish
                                            </a>
                                        </div>
                                        <div class="form-text">
                                            Yangi fayl yuklasangiz, avvalgisi almashtiriladi.
                                        </div>
                                    @endif
                                </div>
                            @else
                                <div class="col-12">
                                    <div class="text-muted" style="font-size: .85rem;">
                                        <i class="bx bx-info-circle me-1"></i>
                                        Bu vazifaga fayl biriktirish yopilgan — faqat matnli javob qabul qilinadi.
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="card-footer d-flex justify-content-end gap-2">
                        <a href="{{ route('student.homework') }}" class="btn btn-outline-secondary">Bekor qilish</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bx bx-send me-1"></i>
                            {{ $isSubmitted ? 'Javobni yangilash' : 'Topshirish' }}
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

@endsection
