@php
    /** @var \App\Models\Homework|null $homework */
    $homework = $homework ?? null;
    $upload = config('grading.homework_upload');
    $selectedGroup = old('group_id', $homework?->group_id ?? request('group_id'));
    $allowFile = (bool) old('allow_file', $homework ? $homework->allow_file : true);
@endphp

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">Topshiriq</div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="group_id">Guruh <span class="text-danger">*</span></label>
                        <select id="group_id" name="group_id"
                                class="form-select @error('group_id') is-invalid @enderror" required>
                            <option value="">Guruhni tanlang…</option>
                            @foreach($groups as $group)
                                <option value="{{ $group->id }}"
                                        @selected((string) $selectedGroup === (string) $group->id)>{{ $group->name }}</option>
                            @endforeach
                        </select>
                        @error('group_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label" for="title">Sarlavha <span class="text-danger">*</span></label>
                        <input type="text" id="title" name="title"
                               class="form-control @error('title') is-invalid @enderror"
                               value="{{ old('title', $homework?->title) }}"
                               placeholder="Masalan: Unit 5 — Present Perfect" required>
                        @error('title') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-12">
                        <label class="form-label" for="description">Topshiriq matni</label>
                        <textarea id="description" name="description" rows="6"
                                  class="form-control @error('description') is-invalid @enderror"
                                  placeholder="Talabalar nima qilishi kerakligini yozing…">{{ old('description', $homework?->description) }}</textarea>
                        @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-12">
                        <label class="form-label" for="attachment">Ilova (ixtiyoriy)</label>
                        <input type="file" id="attachment" name="attachment"
                               class="form-control @error('attachment') is-invalid @enderror">
                        @error('attachment') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        <div class="form-text">
                            Ruxsat etilgan turlar: {{ implode(', ', $upload['mimes']) }}.
                            Maksimal hajm: {{ round($upload['max_kb'] / 1024) }} MB.
                        </div>

                        @if($homework?->attachment)
                            <div class="d-flex align-items-center justify-content-between gap-2 mt-3 p-2 rounded"
                                 style="background: var(--app-surface-2); border: 1px solid var(--app-border);">
                                <a href="{{ asset('storage/' . $homework->attachment) }}" target="_blank"
                                   class="text-decoration-none min-w-0 text-truncate">
                                    <i class="bx bx-paperclip me-1"></i>
                                    {{ basename($homework->attachment) }}
                                </a>
                                <div class="form-check mb-0 flex-shrink-0">
                                    <input class="form-check-input" type="checkbox" value="1"
                                           id="remove_attachment" name="remove_attachment"
                                           @checked(old('remove_attachment'))>
                                    <label class="form-check-label" for="remove_attachment">O‘chirish</label>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header">Sozlamalar</div>
            <div class="card-body d-flex flex-column">
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label" for="due_date">Topshirish muddati</label>
                        <input type="date" id="due_date" name="due_date"
                               class="form-control @error('due_date') is-invalid @enderror"
                               value="{{ old('due_date', $homework?->due_date?->format('Y-m-d')) }}">
                        @error('due_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        <div class="form-text">Bo‘sh qoldirilsa vazifa muddatsiz bo‘ladi.</div>
                    </div>

                    <div class="col-12">
                        <label class="form-label" for="max_score">Maksimal ball <span class="text-danger">*</span></label>
                        <input type="number" id="max_score" name="max_score" min="1" max="1000" step="1"
                               class="form-control @error('max_score') is-invalid @enderror"
                               value="{{ old('max_score', $homework?->max_score ?? 100) }}" required>
                        @error('max_score') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-12">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" value="1"
                                   id="allow_file" name="allow_file" @checked($allowFile)>
                            <label class="form-check-label" for="allow_file">Fayl biriktirishga ruxsat</label>
                        </div>
                        <div class="form-text">
                            O‘chirilsa, talabalar faqat matn ko‘rinishida javob yubora oladi.
                        </div>
                    </div>
                </div>

                <div class="mt-auto pt-4 d-grid gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bx bx-save me-1"></i> Saqlash
                    </button>
                    <a href="{{ $homework ? route('homework.show', $homework->id) : route('homework.index') }}"
                       class="btn btn-outline-secondary">Bekor qilish</a>
                </div>
            </div>
        </div>
    </div>
</div>
