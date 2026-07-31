@extends('template.master')

@section('title', 'Uy vazifalarim')
@section('subtitle', 'Topshirishingiz kerak bo‘lgan vazifalar va olingan baholar')

@section('content')

    @php
        $overdueCount = $homeworks->getCollection()
            ->filter(fn ($h) => $h->isOverdue() && ! optional($h->submissions->first())->submitted_at)
            ->count();
    @endphp

    <div class="page-head justify-content-end">
        @if($overdueCount > 0)
            <span class="badge bg-label-danger">
                <i class="bx bx-error-circle me-1"></i> {{ $overdueCount }} ta vazifa muddati o‘tgan
            </span>
        @endif
    </div>

    <div class="card">
        @if($homeworks->isEmpty())
            <div class="empty-state">
                <i class="bx bx-task"></i>
                <h6>Uy vazifasi yo‘q</h6>
                <p class="mb-0">Hozircha o‘qituvchi sizga topshiriq bermagan.</p>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                    <tr>
                        <th>Vazifa</th>
                        <th>Guruh</th>
                        <th>Muddat</th>
                        <th>Holat</th>
                        <th class="text-end">Amal</th>
                    </tr>
                    </thead>
                    <tbody id="myTable">
                    @foreach($homeworks as $homework)
                        @php
                            $sub = $homework->submissions->first();
                            $isSubmitted = $sub && $sub->submitted_at !== null;
                            $isGraded = $sub && $sub->score !== null;
                            $overdue = $homework->isOverdue();
                        @endphp
                        <tr>
                            <td class="min-w-0">
                                <a href="{{ route('student.homework.show', $homework->id) }}"
                                   class="fw-semibold text-decoration-none">{{ $homework->title }}</a>
                                <div class="text-muted" style="font-size: .78rem;">
                                    Maksimal {{ $homework->max_score }} ball
                                    @if($homework->attachment)
                                        · <i class="bx bx-paperclip"></i> ilova bor
                                    @endif
                                </div>
                            </td>
                            <td>{{ $homework->group->name ?? '—' }}</td>
                            <td>
                                @if($homework->due_date)
                                    <span class="badge bg-label-{{ $overdue ? 'danger' : 'info' }}">
                                        {{ $homework->dueLabel() }}
                                    </span>
                                    <div class="text-muted" style="font-size: .78rem;">
                                        {{ $homework->due_date->format('d.m.Y') }}
                                    </div>
                                @else
                                    <span class="badge bg-label-secondary">Muddatsiz</span>
                                @endif
                            </td>
                            <td>
                                @if($isGraded)
                                    <span class="badge bg-label-{{ $sub->scoreTone($homework->max_score) }}">
                                        Baholandi: {{ $sub->score }} / {{ $homework->max_score }}
                                    </span>
                                @elseif($isSubmitted)
                                    <span class="badge bg-label-{{ $sub->statusTone() }}">
                                        {{ (int) $sub->status === 2 ? 'Kech topshirildi' : 'Topshirildi' }}
                                    </span>
                                @else
                                    <span class="badge bg-label-{{ $overdue ? 'danger' : 'warning' }}">
                                        Topshirilmagan
                                    </span>
                                @endif
                            </td>
                            <td class="text-end">
                                <a href="{{ route('student.homework.show', $homework->id) }}"
                                   class="btn btn-sm {{ $isSubmitted ? 'btn-outline-secondary' : 'btn-primary' }}">
                                    <i class="bx {{ $isSubmitted ? 'bx-show' : 'bx-upload' }} me-1"></i>
                                    {{ $isSubmitted ? 'Ko‘rish' : 'Topshirish' }}
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
