@php
    /** @var \App\Models\ReceptionStudent|null $student */
    $student = $student ?? null;
    $selectedLevel = old('level', $student?->level);
    $selectedStatus = old('status', $student?->status ?? \App\Models\ReceptionStudent::STATUS_NEW);
    $selectedGroup = old('recommended_group_id', $student?->recommended_group_id);
@endphp

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card mb-4">
            <div class="card-header">Asosiy ma’lumotlar</div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="name">Ism familiya <span class="text-danger">*</span></label>
                        <input type="text" id="name" name="name"
                               class="form-control @error('name') is-invalid @enderror"
                               value="{{ old('name', $student?->name) }}" required>
                        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label" for="phone">Telefon</label>
                        <input type="text" id="phone" name="phone"
                               class="form-control @error('phone') is-invalid @enderror"
                               value="{{ old('phone', $student?->phone) }}" placeholder="+998...">
                        @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label" for="parent_name">Ota-ona ismi</label>
                        <input type="text" id="parent_name" name="parent_name"
                               class="form-control @error('parent_name') is-invalid @enderror"
                               value="{{ old('parent_name', $student?->parent_name) }}">
                        @error('parent_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label" for="parent_phone">Ota-ona telefoni</label>
                        <input type="text" id="parent_phone" name="parent_phone"
                               class="form-control @error('parent_phone') is-invalid @enderror"
                               value="{{ old('parent_phone', $student?->parent_phone) }}" placeholder="+998...">
                        @error('parent_phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label" for="source">Qayerdan keldi</label>
                        <input type="text" id="source" name="source"
                               class="form-control @error('source') is-invalid @enderror"
                               value="{{ old('source', $student?->source) }}" placeholder="Instagram, tanish, reklama...">
                        @error('source') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">Test va izoh</div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="test_taken_at">Test sanasi</label>
                        <input type="datetime-local" id="test_taken_at" name="test_taken_at"
                               class="form-control @error('test_taken_at') is-invalid @enderror"
                               value="{{ old('test_taken_at', $student?->test_taken_at?->format('Y-m-d\TH:i')) }}">
                        @error('test_taken_at') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label" for="test_type">Test turi</label>
                        <input type="text" id="test_type" name="test_type"
                               class="form-control @error('test_type') is-invalid @enderror"
                               value="{{ old('test_type', $student?->test_type) }}" placeholder="Placement, IELTS...">
                        @error('test_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label" for="score">Natija</label>
                        <input type="text" id="score" name="score"
                               class="form-control @error('score') is-invalid @enderror"
                               value="{{ old('score', $student?->score) }}" placeholder="Masalan: 18/40 yoki B1">
                        @error('score') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label" for="test_image">Ishlangan test rasmi</label>
                        <input type="file" id="test_image" name="test_image" accept="image/jpeg,image/png,image/webp"
                               class="form-control @error('test_image') is-invalid @enderror">
                        @error('test_image') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        @if($student?->test_image_path)
                            <a class="d-inline-block mt-2" target="_blank" rel="noopener"
                               href="{{ route('reception.students.test-image', $student->id) }}">Biriktirilgan test rasmini ko‘rish</a>
                        @endif
                    </div>

                    <div class="col-12">
                        <label class="form-label" for="notes">Izoh</label>
                        <textarea id="notes" name="notes" rows="4"
                                  class="form-control @error('notes') is-invalid @enderror"
                                  placeholder="Qo‘shimcha ma’lumotlar...">{{ old('notes', $student?->notes) }}</textarea>
                        @error('notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header">Ajratish</div>
            <div class="card-body d-flex flex-column">
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label" for="level">Daraja</label>
                        <select id="level" name="level" class="form-select @error('level') is-invalid @enderror">
                            <option value="">Belgilanmagan</option>
                            @foreach($levels as $value => $label)
                                <option value="{{ $value }}" @selected((string) $selectedLevel === (string) $value)>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                        @error('level') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-12">
                        <label class="form-label" for="recommended_group_id">Tavsiya qilingan guruh</label>
                        <select id="recommended_group_id" name="recommended_group_id"
                                class="form-select @error('recommended_group_id') is-invalid @enderror">
                            <option value="">Hali tanlanmagan</option>
                            @foreach($groups as $group)
                                <option value="{{ $group->id }}" @selected((string) $selectedGroup === (string) $group->id)>
                                    {{ $group->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('recommended_group_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-12">
                        <label class="form-label" for="status">Holat <span class="text-danger">*</span></label>
                        <select id="status" name="status" class="form-select @error('status') is-invalid @enderror" required>
                            @foreach($statuses as $value => $label)
                                <option value="{{ $value }}" @selected((string) $selectedStatus === (string) $value)>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                        @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="mt-auto pt-4 d-grid gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bx bx-save me-1"></i> Saqlash
                    </button>
                    <a href="{{ route('reception.students.index') }}" class="btn btn-outline-secondary">Bekor qilish</a>
                </div>
            </div>
        </div>
    </div>
</div>
