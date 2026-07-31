@extends('template.master')

@section('title', 'Sertifikatlarim')
@section('subtitle', 'Bitirgan kurslaringiz bo‘yicha hujjatlar')

@section('content')

    <div class="card">
        <div class="card-header d-flex align-items-center justify-content-between">
            <span>Sertifikatlarim</span>
            @if($certificates->isNotEmpty())
                <span class="badge bg-label-primary">{{ $certificates->count() }} ta</span>
            @endif
        </div>

        @if($certificates->isEmpty())
            <div class="empty-state">
                <i class="bx bx-award"></i>
                <h6>Hozircha sertifikat yo‘q</h6>
                <p class="mb-0">
                    Kursni tugatganingizdan so‘ng sertifikat shu yerda paydo bo‘ladi
                    va uni istalgan vaqtda yuklab olishingiz mumkin.
                </p>
            </div>
        @else
            <div class="row g-3 p-3">
                @foreach($certificates as $certificate)
                    <div class="col-md-6">
                        <div class="card h-100 card-hover">
                            <div class="card-body d-flex flex-column">
                                <div class="d-flex align-items-start gap-3 mb-3">
                                    <span class="stat-icon is-success"><i class="bx bx-award"></i></span>
                                    <div class="flex-grow-1 min-w-0">
                                        <h6 class="mb-1">{{ $certificate->title }}</h6>
                                        @if($certificate->group)
                                            <div class="text-muted" style="font-size: .82rem;">
                                                <i class="bx bx-group me-1"></i>{{ $certificate->group->name }}
                                            </div>
                                        @endif
                                        <div class="text-muted" style="font-size: .82rem;">
                                            <i class="bx bx-calendar me-1"></i>
                                            {{ optional($certificate->issued_at)->format('d.m.Y') }}
                                        </div>
                                    </div>
                                </div>

                                <div class="row g-2 mb-3">
                                    @if($certificate->level)
                                        <div class="col-6">
                                            <div class="metric-box">
                                                <div class="metric-value">{{ $certificate->level }}</div>
                                                <div class="metric-label">Daraja</div>
                                            </div>
                                        </div>
                                    @endif
                                    @if($certificate->final_score !== null)
                                        <div class="col-6">
                                            <div class="metric-box">
                                                <div class="metric-value">{{ $certificate->final_score }}</div>
                                                <div class="metric-label">Yakuniy ball</div>
                                            </div>
                                        </div>
                                    @endif
                                </div>

                                @if($certificate->note)
                                    <p class="text-muted mb-3" style="font-size: .85rem;">{{ $certificate->note }}</p>
                                @endif

                                <div class="d-flex align-items-center justify-content-between mt-auto">
                                    <span class="text-muted" style="font-size: .75rem;" dir="ltr">
                                        {{ $certificate->serial }}
                                    </span>
                                    <a href="{{ route('certificates.download', $certificate->id) }}"
                                       class="btn btn-sm btn-primary">
                                        <i class="bx bx-download me-1"></i>Yuklab olish
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

@endsection
