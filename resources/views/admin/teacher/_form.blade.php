@php
    /** @var \App\Models\User|null $teacher */
    $teacher = $teacher ?? null;
    $selectedGroups = old('group_id', $teacher ? $teacher->teacherGroups->pluck('id')->all() : []);
    $selectedGroups = array_map('strval', (array) $selectedGroups);
    $phoneValue = old('phone', $teacher?->phone);
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
                               value="{{ old('name', $teacher?->name) }}" placeholder="Masalan: Karimov Aziz" required>
                        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label" for="phone">Telefon raqami <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text">+998</span>
                            <input type="text" id="phone" name="phone"
                                   class="form-control @error('phone') is-invalid @enderror"
                                   value="{{ $phoneValue ? substr($phoneValue, -9) : '' }}"
                                   placeholder="901234567" inputmode="numeric" maxlength="9"
                                   pattern="[0-9]{9}" required>
                        </div>
                        @error('phone')
                        <div class="text-danger mt-1" style="font-size: .8rem;">{{ $message }}</div>
                        @enderror
                        <div class="form-text">Tizimga shu raqam orqali kiradi. 9 ta raqam.</div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label" for="password">
                            Parol
                            @if($teacher)
                                <span class="text-muted fw-normal">(bo‘sh qoldirsangiz o‘zgarmaydi)</span>
                            @else
                                <span class="text-danger">*</span>
                            @endif
                        </label>
                        <input type="text" id="password" name="password"
                               class="form-control @error('password') is-invalid @enderror"
                               placeholder="{{ $teacher ? '••••••' : 'Kamida 4 belgi' }}"
                               autocomplete="new-password" {{ $teacher ? '' : 'required' }}>
                        @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label" for="percent">Ulush (foiz)</label>
                        <div class="input-group">
                            <input type="number" id="percent" name="percent" min="0" max="100" step="1"
                                   class="form-control @error('percent') is-invalid @enderror"
                                   value="{{ old('percent', $teacher?->percent) }}" placeholder="50">
                            <span class="input-group-text">%</span>
                        </div>
                        @error('percent')
                        <div class="text-danger mt-1" style="font-size: .8rem;">{{ $message }}</div>
                        @enderror
                        <div class="form-text">Guruh tushumidan hisoblanadigan oylik ulushi.</div>
                    </div>

                    @include('partials.email-field', ['user' => $teacher ?? null])
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">Qo‘shimcha ma’lumotlar</div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="date_born">Tug‘ilgan sana</label>
                        <input type="date" id="date_born" name="date_born"
                               class="form-control @error('date_born') is-invalid @enderror"
                               value="{{ old('date_born', $teacher?->date_born) }}">
                        @error('date_born') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label" for="passport">Passport seriyasi</label>
                        <input type="text" id="passport" name="passport"
                               class="form-control text-uppercase @error('passport') is-invalid @enderror"
                               value="{{ old('passport', $teacher?->passport) }}"
                               placeholder="AA1234567" maxlength="9">
                        @error('passport') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        <div class="form-text">2 ta katta harf va 7 ta raqam. Ixtiyoriy, lekin takrorlanmasligi shart.</div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label" for="location">Manzil</label>
                        <input type="text" id="location" name="location"
                               class="form-control @error('location') is-invalid @enderror"
                               value="{{ old('location', $teacher?->location) }}" placeholder="Ixtiyoriy">
                        @error('location') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label" for="photo">Rasm</label>
                        <input type="file" id="photo" name="photo" accept="image/*"
                               class="form-control @error('photo') is-invalid @enderror">
                        @error('photo') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        @if($teacher?->photo)
                            <div class="form-text">Yangi rasm yuklamasangiz, joriy rasm saqlanib qoladi.</div>
                        @endif
                    </div>

                    <div class="col-12">
                        <label class="form-label" for="description">Izoh</label>
                        <textarea id="description" name="description" rows="3"
                                  class="form-control @error('description') is-invalid @enderror"
                                  placeholder="Ixtiyoriy">{{ old('description', $teacher?->description) }}</textarea>
                        @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header">Guruhlar</div>
            <div class="card-body d-flex flex-column">
                @if($teacher)
                    <div class="d-flex align-items-center gap-3 mb-3 pb-3 border-bottom">
                        <div class="avatar avatar-lg">
                            @if($teacher->photo)
                                <img src="{{ asset('storage/' . $teacher->photo) }}" alt=""
                                     class="rounded-circle w-100 h-100" style="object-fit: cover;">
                            @else
                                <span class="avatar-initial rounded-circle bg-label-primary">
                                    {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($teacher->name, 0, 2)) }}
                                </span>
                            @endif
                        </div>
                        <div class="min-w-0">
                            <div class="fw-semibold text-truncate">{{ $teacher->name }}</div>
                            <div class="text-muted" style="font-size: .82rem;" dir="ltr">+{{ $teacher->phone }}</div>
                        </div>
                    </div>
                @endif

                {{-- Bu forma guruhlar ro‘yxati uchun mas’ul ekanini bildiradi:
                     tanlov bo‘shatilsa ham sync() ishlaydi, boshqa joydan kelgan
                     so‘rov esa guruhlarga umuman tegmaydi. --}}
                <input type="hidden" name="groups_submitted" value="1">

                <label class="form-label" for="group_id">Biriktiriladigan guruhlar</label>
                <select id="group_id" name="group_id[]" class="choices form-select" multiple
                        data-placeholder="Guruh qidiring…">
                    @foreach($groups as $group)
                        <option value="{{ $group->id }}"
                                @if(in_array((string) $group->id, $selectedGroups, true)) selected @endif>
                            {{ $group->name }}
                        </option>
                    @endforeach
                </select>
                @error('group_id')
                <div class="text-danger mt-1" style="font-size: .8rem;">{{ $message }}</div>
                @enderror
                <div class="form-text mt-2">
                    O‘qituvchi faqat shu guruhlarning davomati va baholarini yurita oladi.
                </div>

                <div class="mt-auto pt-4 d-grid gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bx bx-save me-1"></i> Saqlash
                    </button>
                    <a href="{{ $teacher ? route('teacher.show', $teacher->id) : route('teacher.index') }}"
                       class="btn btn-outline-secondary">Bekor qilish</a>
                </div>
            </div>
        </div>
    </div>
</div>
