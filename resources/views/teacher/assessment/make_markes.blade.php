@extends('template.master')

@php
    // The same list lesson_skill_grades uses, so both grading paths speak one
    // vocabulary and a skill added to the config shows up in both.
    $skills = (array) config('grading.skills', []);
    $labels = (array) config('grading.skill_labels', []);
@endphp

@section('content')

    <div class="card">
        <form action="{{ route('assessment.update', $id) }}" method="post">
            @csrf
            @method('PUT')

            <div class="card-header">
                <label for="lesson" class="form-label">Test nomi</label>
                <input type="text" name="lesson" id="lesson"
                       class="form-control @error('lesson') is-invalid @enderror"
                       value="{{ old('lesson') }}"
                       placeholder="Masalan: Unit 5 — Listening" required>
                @error('lesson') <div class="invalid-feedback">{{ $message }}</div> @enderror
                <div class="form-text">
                    Har bir talabaga alohida ko‘nikma tanlash mumkin — bitta testda
                    biriga listening, boshqasiga speaking qo‘yilsa ham bo‘ladi.
                </div>
            </div>

            <div class="table-responsive text-nowrap">
                <table class="table align-middle">
                    <thead>
                    <tr>
                        <th style="width: 60px;">T/R</th>
                        <th>Talaba</th>
                        <th style="width: 190px;">Ko‘nikma</th>
                        <th style="width: 110px;">Ball</th>
                        <th>Izoh</th>
                    </tr>
                    </thead>
                    <tbody class="table-border-bottom-0">
                    @forelse($students as $index => $student)
                        <tr>
                            <td>{{ $index + 1 }}</td>

                            <td>
                                {{ $student->name }}
                                <input type="hidden" name="student[]" value="{{ $student->id }}">
                            </td>

                            <td>
                                <select name="skill[]"
                                        class="form-select @error('skill.' . $index) is-invalid @enderror">
                                    {{-- Empty is stored as NULL: a test that measures no one skill. --}}
                                    <option value="">Umumiy</option>
                                    @foreach($skills as $skill)
                                        <option value="{{ $skill }}"
                                                @selected(old('skill.' . $index) === $skill)>
                                            {{ $labels[$skill] ?? $skill }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('skill.' . $index)
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </td>

                            <td>
                                <input type="number" name="end_mark[]" min="0" max="100"
                                       class="form-control @error('end_mark.' . $index) is-invalid @enderror"
                                       value="{{ old('end_mark.' . $index) }}"
                                       placeholder="0–100">
                                @error('end_mark.' . $index)
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </td>

                            <td>
                                <input type="text" name="reason[]"
                                       class="form-control @error('reason.' . $index) is-invalid @enderror"
                                       value="{{ old('reason.' . $index) }}"
                                       placeholder="ixtiyoriy">
                                @error('reason.' . $index)
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-4">
                                Bu guruhda talaba yo‘q.
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <div class="card-body d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div class="text-muted" style="font-size: .85rem;">
                    Ball bo‘sh qoldirilsa — o‘sha talaba baholanmagan hisoblanadi.
                    <strong>0</strong> esa haqiqiy baho va saqlanadi.
                </div>
                <button type="submit" class="btn btn-primary">Saqlash</button>
            </div>
        </form>
    </div>

@endsection
