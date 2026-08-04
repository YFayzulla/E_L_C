@extends('template.master')

@section('title', 'Baholash — ' . $homework->title)
@section('subtitle', ($homework->group->name ?? 'Guruh') . ' · maksimal ' . $homework->max_score . ' ball')

@section('content')

    @php
        $statusOptions = config('grading.homework_status');
        $submittedCount = $submissions->whereNotNull('submitted_at')->count();
        $gradedCount = $submissions->whereNotNull('score')->count();
    @endphp

    <div class="page-head">
        <div class="page-sub">
            <span class="badge bg-label-success me-1">Topshirgan: {{ $submittedCount }} / {{ $students->count() }}</span>
            <span class="badge bg-label-info me-1">Baholangan: {{ $gradedCount }} / {{ $students->count() }}</span>
            @if($homework->due_date)
                <span class="badge bg-label-{{ $homework->isOverdue() ? 'danger' : 'secondary' }}">
                    Muddat: {{ $homework->due_date->format('d.m.Y') }} · {{ $homework->dueLabel() }}
                </span>
            @else
                <span class="badge bg-label-secondary">Muddatsiz</span>
            @endif
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('homework.show', $homework->id) }}" class="btn btn-outline-secondary">
                <i class="bx bx-arrow-back me-1"></i> Vazifa
            </a>
        </div>
    </div>

    @if($students->isEmpty())
        <div class="card">
            <div class="empty-state">
                <i class="bx bx-user-x"></i>
                <h6>Guruhda talaba yo‘q</h6>
                <p class="mb-0">«{{ $homework->group->name ?? 'Guruh' }}» guruhiga hali talaba biriktirilmagan.</p>
            </div>
        </div>
    @else
        <form action="{{ route('homework.grade.store', $homework->id) }}" method="POST">
            @csrf

            <div class="card">
                <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <span>Talabalar javoblari</span>
                    <span class="text-muted" style="font-size: .82rem;">
                        Ball 0 dan {{ $homework->max_score }} gacha. Bo‘sh qoldirilsa — baholanmagan hisoblanadi.
                    </span>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover grade-table align-middle">
                        <thead>
                        <tr>
                            <th style="min-width: 12rem;">Talaba</th>
                            <th style="min-width: 20rem;">Yuborilgan javob</th>
                            <th style="min-width: 10rem;">Holat</th>
                            <th style="min-width: 7rem;">Ball</th>
                            <th style="min-width: 16rem;">Izoh</th>
                        </tr>
                        </thead>
                        <tbody id="myTable">
                        @foreach($students as $student)
                            @php
                                $sub = $submissions[$student->id] ?? null;
                                $text = $sub?->submission_text;
                                $isLong = filled($text) && mb_strlen($text) > 140;
                                $currentStatus = old('status.' . $student->id, $sub?->status ?? 0);
                            @endphp
                            <tr>
                                <td>
                                    {{-- The photo was missing here entirely: only initials. --}}
                                    <x-avatar :user="$student"
                                              label
                                              :meta="$sub?->submitted_at?->format('d.m.Y H:i') ?? 'Topshirmagan'"
                                              class="fw-semibold" />
                                </td>

                                <td>
                                    @if($sub && $sub->isSubmitted())
                                        <span class="badge bg-label-{{ $sub->statusTone() }} mb-2">
                                            {{ $sub->statusLabel() }}
                                        </span>
                                    @else
                                        <span class="badge bg-label-danger mb-2">Topshirmagan</span>
                                    @endif

                                    @if(filled($text))
                                        <div style="white-space: pre-line;">
                                            {{ $isLong ? \Illuminate\Support\Str::limit($text, 140) : $text }}
                                        </div>
                                        @if($isLong)
                                            <a class="d-inline-block mt-1 text-decoration-none" style="font-size: .8rem;"
                                               data-bs-toggle="collapse" href="#hwText{{ $student->id }}"
                                               role="button" aria-expanded="false" aria-controls="hwText{{ $student->id }}">
                                                <i class="bx bx-chevron-down"></i> To‘liq ko‘rish
                                            </a>
                                            <div class="collapse mt-2" id="hwText{{ $student->id }}">
                                                <div class="p-2 rounded" style="white-space: pre-line; background: var(--app-surface-2); border: 1px solid var(--app-border);">
                                                    {{ $text }}
                                                </div>
                                            </div>
                                        @endif
                                    @endif

                                    @if($sub?->submission_file)
                                        <div class="mt-2">
                                            <a href="{{ asset('storage/' . $sub->submission_file) }}" target="_blank"
                                               class="btn btn-sm btn-outline-secondary">
                                                <i class="bx bx-download me-1"></i> Faylni yuklab olish
                                            </a>
                                        </div>
                                    @endif

                                    @if(! filled($text) && ! $sub?->submission_file)
                                        <div class="text-muted" style="font-size: .82rem;">Javob yuborilmagan.</div>
                                    @endif
                                </td>

                                <td>
                                    <select name="status[{{ $student->id }}]"
                                            class="form-select form-select-sm @error('status.' . $student->id) is-invalid @enderror">
                                        @foreach($statusOptions as $value => $label)
                                            <option value="{{ $value }}"
                                                    @selected((string) $currentStatus === (string) $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    @error('status.' . $student->id)
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </td>

                                <td>
                                    <input type="number" name="score[{{ $student->id }}]"
                                           class="form-control form-control-sm grade-input @error('score.' . $student->id) is-invalid @enderror"
                                           min="0" max="{{ $homework->max_score }}" step="1"
                                           value="{{ old('score.' . $student->id, $sub?->score) }}"
                                           placeholder="—">
                                    @error('score.' . $student->id)
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </td>

                                <td>
                                    <input type="text" name="comment[{{ $student->id }}]" maxlength="500"
                                           class="form-control form-control-sm @error('comment.' . $student->id) is-invalid @enderror"
                                           value="{{ old('comment.' . $student->id, $sub?->comment) }}"
                                           placeholder="Talabaga izoh…">
                                    @error('comment.' . $student->id)
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="card-footer d-flex justify-content-end gap-2">
                    <a href="{{ route('homework.show', $homework->id) }}" class="btn btn-outline-secondary">
                        Bekor qilish
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="bx bx-save me-1"></i> Baholarni saqlash
                    </button>
                </div>
            </div>
        </form>
    @endif

@endsection
