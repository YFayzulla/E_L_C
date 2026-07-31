@php
    /** @var \App\Models\User|null $parent */
    $parent = $parent ?? null;
    $selectedChildren = old('children', $parent ? $parent->children->pluck('id')->all() : []);
    $phoneValue = old('phone', $parent?->phone);
@endphp

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">Asosiy ma’lumotlar</div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="name">Ism familiya <span class="text-danger">*</span></label>
                        <input type="text" id="name" name="name" class="form-control @error('name') is-invalid @enderror"
                               value="{{ old('name', $parent?->name) }}" placeholder="Masalan: Aliyev Vali" required>
                        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label" for="phone">Telefon raqami <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text">+998</span>
                            <input type="text" id="phone" name="phone"
                                   class="form-control @error('phone') is-invalid @enderror"
                                   value="{{ $phoneValue ? substr($phoneValue, -9) : '' }}"
                                   placeholder="901234567" inputmode="numeric" required>
                        </div>
                        @error('phone') <div class="text-danger mt-1" style="font-size: .8rem;">{{ $message }}</div> @enderror
                        <div class="form-text">Tizimga shu raqam orqali kiradi.</div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label" for="password">
                            Parol
                            @if($parent)
                                <span class="text-muted fw-normal">(o‘zgartirmasangiz bo‘sh qoldiring)</span>
                            @else
                                <span class="text-danger">*</span>
                            @endif
                        </label>
                        <input type="text" id="password" name="password"
                               class="form-control @error('password') is-invalid @enderror"
                               placeholder="{{ $parent ? '••••••' : 'Kamida 4 belgi' }}" {{ $parent ? '' : 'required' }}>
                        @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label" for="location">Manzil</label>
                        <input type="text" id="location" name="location" class="form-control"
                               value="{{ old('location', $parent?->location) }}" placeholder="Ixtiyoriy">
                    </div>

                    <div class="col-12">
                        <label class="form-label" for="description">Izoh</label>
                        <textarea id="description" name="description" class="form-control" rows="3"
                                  placeholder="Ixtiyoriy">{{ old('description', $parent?->description) }}</textarea>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header">Farzandlar</div>
            <div class="card-body d-flex flex-column">
                <label class="form-label" for="children">Talabalarni tanlang</label>
                <select id="children" name="children[]" class="choices form-select" multiple
                        data-placeholder="Talaba qidiring…">
                    @foreach($students as $student)
                        <option value="{{ $student->id }}"
                                @if(in_array($student->id, $selectedChildren)) selected @endif>
                            {{ $student->name }}@if($student->phone) — +{{ $student->phone }}@endif
                        </option>
                    @endforeach
                </select>
                <div class="form-text mt-2">
                    Ota-ona faqat shu ro‘yxatdagi talabalarning ma’lumotlarini ko‘ra oladi.
                </div>

                <div class="mt-auto pt-4 d-grid gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bx bx-save me-1"></i> Saqlash
                    </button>
                    <a href="{{ route('parents.index') }}" class="btn btn-outline-secondary">Bekor qilish</a>
                </div>
            </div>
        </div>
    </div>
</div>
