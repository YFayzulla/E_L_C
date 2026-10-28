@extends('template.master')

@section('title', 'Ko‘nikma baholari')
@section('subtitle', $group->name)

@section('content')

    @php
        $skills     = (array) config('grading.skills', []);
        $labels     = (array) config('grading.skill_labels', []);
        $icons      = (array) config('grading.skill_icons', []);
        $lessonName = optional($lessons->firstWhere('id', $lessonId));
        // Xato bilan qaytilganda tanlov saqlanadi; aks holda controller beradi
        // (darsda baholangan ko'nikmalar yoki hammasi).
        $selectedSkills = (array) old('skills', $selectedSkills ?? $skills);
        $hasAggregates  = count($skills) > 1;
    @endphp

    <div class="page-head">
        <div class="d-flex align-items-center gap-3">
            <a href="{{ route('skills.groups') }}" class="btn-icon" title="Orqaga">
                <i class="bx bx-arrow-back"></i>
            </a>
            <div>
                <div class="page-sub mb-0">
                    Dars davomida qo‘yilgan baholar. Bo‘sh katak — baholanmagan degani.
                </div>
            </div>
        </div>
        <a href="{{ route('skills.report', $group->id) }}" class="btn btn-outline-secondary">
            <i class="bx bx-bar-chart-alt-2 me-1"></i> Hisobot
        </a>
    </div>

    @if($lessons->isEmpty())

        <div class="card">
            <div class="empty-state">
                <i class="bx bx-calendar-x"></i>
                <h6>Bu guruhda hali dars qayd etilmagan. Avval davomat oling.</h6>
                <p class="mb-3">
                    Ko‘nikma bahosi mavjud darsga biriktiriladi — baholash sahifasi dars yaratmaydi.
                </p>
                <a href="{{ route('attendance') }}" class="btn btn-primary btn-sm">
                    <i class="bx bx-calendar-check me-1"></i> Davomatga o‘tish
                </a>
            </div>
        </div>

    @elseif($students->isEmpty())

        <div class="card">
            <div class="empty-state">
                <i class="bx bx-user-x"></i>
                <h6>Guruhda talaba yo‘q</h6>
                <p class="mb-0">Bu guruhga hali talaba biriktirilmagan.</p>
            </div>
        </div>

    @else

        {{-- Lesson picker: reloads the page so existing marks are prefilled --}}
        <form method="GET" action="{{ route('skills.grade', $group->id) }}" class="filter-card">
            <div class="row g-3 align-items-end">
                <div class="col-md-7">
                    <label class="form-label" for="lesson">Dars</label>
                    <select id="lesson" name="lesson" class="form-select" onchange="this.form.submit();">
                        @foreach($lessons as $lesson)
                            <option value="{{ $lesson->id }}" @selected($lesson->id == $lessonId)>
                                {{ $lesson->name ?: 'Dars' }} — {{ optional($lesson->created_at)->format('d.m.Y') }}
                            </option>
                        @endforeach
                    </select>
                    <div class="form-text">Oxirgi 60 kundagi darslar. Yangi dars faqat davomat orqali yaratiladi.</div>
                </div>
                <div class="col-md-5">
                    <noscript>
                        <button type="submit" class="btn btn-outline-secondary">Darsni ochish</button>
                    </noscript>
                    <div class="text-muted" style="font-size: .82rem;">
                        <i class="bx bx-user me-1"></i>{{ $students->count() }} ta talaba
                        <span class="mx-1">·</span>
                        <i class="bx bx-list-check me-1"></i>{{ count($skills) }} ta ko‘nikma
                    </div>
                </div>
            </div>
        </form>

        <form method="POST" action="{{ route('skills.store', $group->id) }}" id="skillsForm">
            @csrf
            <input type="hidden" name="lesson_id" value="{{ $lessonId }}">

            <div class="card">
                <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <span>
                        {{ $lessonName->name ?: 'Dars' }}
                        <span class="text-muted">— {{ optional($lessonName->created_at)->format('d.m.Y') }}</span>
                    </span>
                    <span class="badge bg-label-primary">0 – 100</span>
                </div>

                @error('skills')
                    <div class="alert alert-danger mx-3 mt-3 mb-0 py-2">{{ $message }}</div>
                @enderror

                <div class="table-responsive">
                    <table class="table table-hover grade-table marks-table mb-0">
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
                                {{-- Serverga yuborilmaydi — faqat ko'rsatish uchun. --}}
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
                        @foreach($students as $student)
                            <tr data-student="{{ $student->id }}">
                                <td>
                                    <x-avatar :user="$student" label class="fw-semibold" />
                                </td>

                                @foreach($skills as $skill)
                                    @php
                                        $field = "score.{$student->id}.{$skill}";
                                        $value = old($field, $scores[$student->id][$skill] ?? null);
                                    @endphp
                                    <td>
                                        <input type="number" inputmode="numeric" min="0" max="100" step="1"
                                               name="score[{{ $student->id }}][{{ $skill }}]"
                                               value="{{ $value }}"
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
                                        {{-- Derived on the fly; nothing is posted or stored for it. --}}
                                        <span class="badge bg-label-secondary js-average" style="font-size: .9rem;">—</span>
                                    </td>
                                    <td>
                                        <span class="badge bg-label-secondary js-sum" style="font-size: .9rem;">—</span>
                                    </td>
                                @endif

                                <td>
                                    <input type="text" maxlength="250"
                                           name="comment[{{ $student->id }}]"
                                           value="{{ old("comment.{$student->id}", $comments[$student->id] ?? '') }}"
                                           class="form-control form-control-sm @error("comment.{$student->id}") is-invalid @enderror"
                                           placeholder="Ixtiyoriy izoh"
                                           aria-label="{{ $student->name }} — izoh">
                                    @error("comment.{$student->id}")
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="card-footer d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <span class="text-muted" style="font-size: .82rem;">
                        Ustun tepasidagi belgi — dars shu ko‘nikmani o‘z ichiga oladimi: faqat belgilangan
                        ustunlar saqlanadi va o‘rtacha hamda yig‘indi faqat ular bo‘yicha hisoblanadi.
                        Bo‘sh qoldirilgan katak saqlanmaydi — 0 ball sifatida yozilmaydi.
                    </span>
                    <button type="submit" class="btn btn-primary">
                        <i class="bx bx-save me-1"></i> Saqlash
                    </button>
                </div>
            </div>
        </form>

        @include('partials.marks-matrix-assets', ['formId' => 'skillsForm'])

    @endif

@endsection
