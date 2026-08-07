@extends('template.master')

@section('title', 'Oylik test')
@section('subtitle', $groupName ?? 'Guruh')

@php
    // The same list the Ko‘nikmalar page uses, so both speak one vocabulary
    // and a skill added to config/grading.php appears in both.
    $skills = (array) config('grading.skills', []);
    $labels = (array) config('grading.skill_labels', []);
    $icons  = (array) config('grading.skill_icons', []);
@endphp

@section('content')

    <form action="{{ route('assessment.update', $id) }}" method="post" id="markForm">
        @csrf
        @method('PUT')

        <div class="card mb-3">
            <div class="card-body">
                <label for="lesson" class="form-label">Test nomi</label>
                <input type="text" name="lesson" id="lesson"
                       class="form-control @error('lesson') is-invalid @enderror"
                       value="{{ old('lesson', 'Oylik test — ' . now()->translatedFormat('F Y')) }}"
                       maxlength="255" required>
                @error('lesson') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
        </div>

        <div class="card">
            <div class="table-responsive">
                <table class="table align-middle grade-table mb-0">
                    <thead>
                    <tr>
                        <th style="min-width: 13rem;">Talaba</th>
                        @foreach($skills as $skill)
                            <th style="min-width: 7rem;">
                                <i class="bx {{ $icons[$skill] ?? 'bx-star' }} me-1"></i>
                                {{ $labels[$skill] ?? ucfirst($skill) }}
                            </th>
                        @endforeach
                        <th style="min-width: 7rem;">
                            <i class="bx bx-calculator me-1"></i>
                            {{ config('grading.overall_label', 'Umumiy') }}
                        </th>
                        <th style="min-width: 14rem;">Izoh</th>
                    </tr>
                    </thead>

                    <tbody id="myTable">
                    @forelse($students as $student)
                        <tr data-student="{{ $student->id }}">
                            <td>
                                <x-avatar :user="$student" label class="fw-semibold" />
                                <input type="hidden" name="student[]" value="{{ $student->id }}">
                            </td>

                            @foreach($skills as $skill)
                                @php
                                    $field = "score.{$student->id}.{$skill}";
                                @endphp
                                <td>
                                    <input type="number" inputmode="numeric" min="0" max="100" step="1"
                                           name="score[{{ $student->id }}][{{ $skill }}]"
                                           value="{{ old($field) }}"
                                           class="form-control grade-input js-score @error($field) is-invalid @enderror"
                                           placeholder="—"
                                           aria-label="{{ $student->name }} — {{ $labels[$skill] ?? $skill }}">
                                    @error($field) <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </td>
                            @endforeach

                            <td>
                                {{-- Derived, never posted: the server recomputes it from the
                                     marks so a hand-edited field cannot disagree with them. --}}
                                <span class="badge bg-label-secondary js-overall" style="font-size: .9rem;">—</span>
                            </td>

                            <td>
                                <input type="text" maxlength="250"
                                       name="comment[{{ $student->id }}]"
                                       value="{{ old("comment.{$student->id}") }}"
                                       class="form-control @error("comment.{$student->id}") is-invalid @enderror"
                                       placeholder="Ixtiyoriy izoh">
                                @error("comment.{$student->id}")
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ count($skills) + 3 }}" class="text-center py-4">
                                Bu guruhda talaba yo‘q.
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <div class="card-body d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div class="text-muted" style="font-size: .85rem;">
                    Bo‘sh katak «baholanmagan» degani — u o‘rtachaga kirmaydi.
                    <strong>0</strong> esa haqiqiy baho va saqlanadi.
                </div>
                <button type="submit" class="btn btn-primary">Saqlash</button>
            </div>
        </div>
    </form>

    <script>
        // Live arithmetic mean, so the teacher sees the overall while typing.
        // Blank cells are skipped, exactly as the server does.
        (function () {
            const rows = document.querySelectorAll('#myTable tr[data-student]');

            function recalc(row) {
                const values = [...row.querySelectorAll('.js-score')]
                    .map(i => i.value.trim())
                    .filter(v => v !== '')
                    .map(Number)
                    .filter(n => Number.isFinite(n));

                const out = row.querySelector('.js-overall');
                if (!out) { return; }

                if (values.length === 0) {
                    out.textContent = '—';
                    out.className = 'badge bg-label-secondary js-overall';
                    return;
                }

                const mean = Math.round(values.reduce((a, b) => a + b, 0) / values.length);
                out.textContent = mean;
                out.className = 'badge js-overall bg-label-'
                    + (mean >= 80 ? 'success' : mean >= 60 ? 'warning' : 'danger');
            }

            rows.forEach(row => {
                row.querySelectorAll('.js-score').forEach(input => {
                    input.addEventListener('input', () => recalc(row));
                });
                recalc(row);
            });
        })();
    </script>

@endsection
