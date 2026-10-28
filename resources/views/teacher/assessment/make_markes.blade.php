@extends('template.master')

@section('title', 'Oylik test')
@section('subtitle', $groupName ?? 'Guruh')

@php
    // The same list the Ko‘nikmalar page uses, so both speak one vocabulary
    // and a skill added to config/grading.php appears in both.
    $skills = (array) config('grading.skills', []);
    $labels = (array) config('grading.skill_labels', []);
    $icons  = (array) config('grading.skill_icons', []);

    // Qaysi ustunlar testga kiradi. Xato bilan qaytilganda tanlov saqlanadi;
    // birinchi ochilishda hammasi belgilangan.
    $selectedSkills = (array) old('skills', $skills);
    $hasAggregates  = count($skills) > 1;
@endphp

@section('content')

    <form action="{{ route('assessment.update', $id) }}" method="post" id="markForm">
        @csrf
        @method('PUT')

        <div class="card mb-3">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-8">
                        <label for="lesson" class="form-label">Test nomi</label>
                        <input type="text" name="lesson" id="lesson"
                               class="form-control @error('lesson') is-invalid @enderror"
                               value="{{ old('lesson', 'Oylik test — ' . now()->translatedFormat('F Y')) }}"
                               maxlength="255" required>
                        @error('lesson') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-4">
                        {{-- Formaga tegishli emas (name yo'q): tanlansa shu guruhning
                             sahifasi ochiladi. --}}
                        <label for="groupSwitch" class="form-label">Guruh</label>
                        <select id="groupSwitch" class="form-select" aria-label="Guruhni tanlash">
                            @foreach($groups as $option)
                                <option value="{{ route('assessment.show', $option->id) }}"
                                        @selected((int) $option->id === (int) $id)>
                                    {{ $option->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                @error('skills')
                    <div class="alert alert-danger mt-3 mb-0 py-2">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <div class="card">
            <div class="table-responsive">
                <table class="table align-middle grade-table marks-table mb-0">
                    <thead>
                    <tr>
                        <th>Talaba</th>
                        @foreach($skills as $skill)
                            <th class="js-skill-head" data-skill="{{ $skill }}">
                                <label class="d-flex align-items-center gap-1 mb-0" style="cursor: pointer;">
                                    <input type="checkbox" class="form-check-input mt-0 js-skill-toggle"
                                           name="skills[]" value="{{ $skill }}"
                                           @checked(in_array($skill, $selectedSkills, true))>
                                    <i class="bx {{ $icons[$skill] ?? 'bx-star' }} d-none d-xxl-inline"></i>
                                    {{ $labels[$skill] ?? ucfirst($skill) }}
                                </label>
                            </th>
                        @endforeach
                        @if($hasAggregates)
                            {{-- Bitta ko'nikma bo'lsa bu ustunlar o'sha bahoni
                                 takrorlaydi, ya'ni ortiqcha. Ular serverga
                                 yuborilmaydi — faqat ko'rsatish uchun. --}}
                            <th>
                                <label class="d-flex align-items-center gap-1 mb-0" style="cursor: pointer;">
                                    <input type="checkbox" class="form-check-input mt-0 js-agg-toggle"
                                           data-agg="average" checked>
                                    <i class="bx bx-calculator d-none d-xxl-inline"></i> O‘rtacha
                                </label>
                            </th>
                            <th>
                                <label class="d-flex align-items-center gap-1 mb-0" style="cursor: pointer;">
                                    <input type="checkbox" class="form-check-input mt-0 js-agg-toggle"
                                           data-agg="sum" checked>
                                    <i class="bx bx-plus-circle d-none d-xxl-inline"></i> Yig‘indi
                                </label>
                            </th>
                        @endif
                        <th style="min-width: 8rem;">Izoh</th>
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
                                           data-skill="{{ $skill }}"
                                           placeholder="—"
                                           @disabled(! in_array($skill, $selectedSkills, true))
                                           aria-label="{{ $student->name }} — {{ $labels[$skill] ?? $skill }}">
                                    @error($field) <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </td>
                            @endforeach

                            @if($hasAggregates)
                                <td>
                                    {{-- Derived, never posted: the server recomputes it from the
                                         marks so a hand-edited field cannot disagree with them. --}}
                                    <span class="badge bg-label-secondary js-average" style="font-size: .9rem;">—</span>
                                </td>
                                <td>
                                    <span class="badge bg-label-secondary js-sum" style="font-size: .9rem;">—</span>
                                </td>
                            @endif

                            <td>
                                <input type="text" maxlength="250"
                                       name="comment[{{ $student->id }}]"
                                       value="{{ old("comment.{$student->id}") }}"
                                       class="form-control form-control-sm @error("comment.{$student->id}") is-invalid @enderror"
                                       placeholder="Ixtiyoriy izoh">
                                @error("comment.{$student->id}")
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ count($skills) + ($hasAggregates ? 4 : 2) }}" class="text-center py-4">
                                Bu guruhda talaba yo‘q.
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <div class="card-body d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div class="text-muted" style="font-size: .85rem;">
                    Ustun tepasidagi belgi — test shu ko‘nikmani o‘z ichiga oladimi: faqat belgilangan
                    ustunlarga ball yoziladi va o‘rtacha hamda yig‘indi faqat ular bo‘yicha hisoblanadi.
                    Talaba kartasidagi «Oxirgi ball» — belgilangan ustunlar o‘rtachasi.
                    Bo‘sh katak «baholanmagan» degani — hisobga kirmaydi; <strong>0</strong> esa haqiqiy baho.
                </div>
                <button type="submit" class="btn btn-primary">Saqlash</button>
            </div>
        </div>
    </form>

    <script>
        document.getElementById('groupSwitch')?.addEventListener('change', (event) => {
            window.location.href = event.target.value;
        });
    </script>

    @include('partials.marks-matrix-assets', ['formId' => 'markForm'])

@endsection
