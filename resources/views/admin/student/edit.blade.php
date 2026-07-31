@extends('template.master')

@section('title', 'Talabani tahrirlash')
@section('subtitle', $student->name)

@section('content')

    <div class="page-head justify-content-end">
        <a href="{{ route('student.show', $student->id) }}" class="btn btn-outline-secondary">
            <i class="bx bx-user me-1"></i> Profilni ko‘rish
        </a>
        <a href="{{ route('student.index') }}" class="btn btn-outline-secondary">
            <i class="bx bx-arrow-back me-1"></i> Ro‘yxatga qaytish
        </a>
    </div>

    <form action="{{ route('student.update', $student->id) }}" method="POST" enctype="multipart/form-data"
          id="studentForm">
        @csrf
        @method('PUT')

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
                                       value="{{ old('name', $student->name) }}" required>
                                @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label" for="phone">Telefon raqami <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">+998</span>
                                    <input type="tel" id="phone" name="phone" maxlength="9"
                                           class="form-control @error('phone') is-invalid @enderror"
                                           value="{{ old('phone', substr($student->phone, -9)) }}"
                                           placeholder="901234567" inputmode="numeric" required>
                                </div>
                                @error('phone') <div class="text-danger mt-1" style="font-size: .8rem;">{{ $message }}</div> @enderror
                            </div>

                            @include('partials.email-field', ['user' => $student])

                            <div class="col-md-6">
                                <label class="form-label" for="birth_date">Tug‘ilgan sana</label>
                                <input type="date" id="birth_date" name="birth_date"
                                       class="form-control @error('birth_date') is-invalid @enderror"
                                       value="{{ old('birth_date', $student->date_born) }}">
                                @error('birth_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label" for="password">
                                    Yangi parol
                                    <span class="text-muted fw-normal">(o‘zgartirmasangiz bo‘sh qoldiring)</span>
                                </label>
                                <input type="text" id="password" name="password"
                                       class="form-control @error('password') is-invalid @enderror"
                                       placeholder="••••••">
                                @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-12">
                                <label class="form-label" for="group_id">Guruhlar <span class="text-danger">*</span></label>
                                <select id="group_id" name="group_id[]" class="choices form-select" multiple required
                                        data-placeholder="Guruh tanlang…">
                                    @foreach($groups as $group)
                                        <option value="{{ $group->id }}" data-payment="{{ $group->monthly_payment }}"
                                                @if($student->groups->contains($group->id)) selected @endif>
                                            {{ $group->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('group_id') <div class="text-danger mt-1" style="font-size: .8rem;">{{ $message }}</div> @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header">Ota-ona ma’lumotlari</div>
                    <div class="card-body">
                        @include('partials.guardian-fields', ['guardians' => $guardians ?? []])
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header">Qo‘shimcha ma’lumotlar</div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="location">Manzil</label>
                                <input type="text" id="location" name="location"
                                       class="form-control @error('location') is-invalid @enderror"
                                       value="{{ old('location', $student->location) }}" placeholder="Ixtiyoriy">
                                @error('location') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label" for="photo">Rasm</label>
                                <input type="file" id="photo" name="photo" accept="image/*"
                                       class="form-control @error('photo') is-invalid @enderror">
                                @error('photo') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                @if($student->photo)
                                    <div class="form-text">Yangi rasm yuklansa, eskisi o‘chadi.</div>
                                @endif
                            </div>

                            <div class="col-12">
                                <label class="form-label" for="description">Izoh</label>
                                <textarea id="description" name="description" rows="3"
                                          class="form-control @error('description') is-invalid @enderror"
                                          placeholder="Ixtiyoriy">{{ old('description', $student->description) }}</textarea>
                                @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card h-100">
                    <div class="card-header">Oylik to‘lov</div>
                    <div class="card-body d-flex flex-column">
                        <div id="group_payments_container">
                            {{-- Per-group payment inputs are injected here by JS --}}
                        </div>

                        <div class="metric-box mt-3">
                            <div class="metric-label">Jami oylik to‘lov</div>
                            <div class="metric-value" id="paymentsTotal">0</div>
                        </div>

                        <div class="form-text mt-2">
                            To‘lov guruhdan olinadi, kerak bo‘lsa o‘zgartiring.
                        </div>

                        <div class="mt-auto pt-4 d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bx bx-save me-1"></i> Saqlash
                            </button>
                            <a href="{{ route('student.index') }}" class="btn btn-outline-secondary">Bekor qilish</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var groupSelect = document.getElementById('group_id');
            var paymentsContainer = document.getElementById('group_payments_container');
            var totalEl = document.getElementById('paymentsTotal');
            var formEl = document.getElementById('studentForm');
            var existingPayments = {!! json_encode(old('group_payment', $student->groups->pluck('pivot.payment', 'id')->toArray())) !!};

            function formatNumberWithSpaces(value) {
                if (value === null || value === undefined || value === '') return '';
                return value.toString().replace(/\D/g, '').replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
            }

            function recalcTotal() {
                var total = 0;
                paymentsContainer.querySelectorAll('.group-payment-input').forEach(function (i) {
                    var raw = i.value.replace(/\s/g, '');
                    total += raw ? parseInt(raw, 10) : 0;
                });
                totalEl.textContent = formatNumberWithSpaces(total) || '0';
            }

            function renderGroupPayments() {
                var selected = Array.prototype.slice.call(groupSelect.selectedOptions)
                    .filter(function (o) { return o.value; });

                paymentsContainer.innerHTML = '';

                if (!selected.length) {
                    var hint = document.createElement('p');
                    hint.className = 'text-muted mb-0';
                    hint.style.fontSize = '.85rem';
                    hint.textContent = 'Avval guruh tanlang.';
                    paymentsContainer.appendChild(hint);
                    recalcTotal();
                    return;
                }

                selected.forEach(function (opt) {
                    var gid = opt.value;
                    var wrapper = document.createElement('div');
                    wrapper.className = 'mb-3';

                    var label = document.createElement('label');
                    label.className = 'form-label';
                    label.setAttribute('for', 'group_payment_' + gid);
                    label.textContent = opt.textContent.trim();

                    var input = document.createElement('input');
                    input.type = 'text';
                    input.id = 'group_payment_' + gid;
                    input.name = 'group_payment[' + gid + ']';
                    input.className = 'form-control group-payment-input';
                    input.inputMode = 'numeric';
                    input.value = (existingPayments && existingPayments[gid])
                        ? formatNumberWithSpaces(existingPayments[gid])
                        : formatNumberWithSpaces(opt.dataset.payment || '');

                    input.addEventListener('input', function () {
                        this.value = formatNumberWithSpaces(this.value.replace(/\s/g, ''));
                        recalcTotal();
                    });

                    wrapper.appendChild(label);
                    wrapper.appendChild(input);
                    paymentsContainer.appendChild(wrapper);
                });

                recalcTotal();
            }

            // The server expects plain digits, not the grouped display value.
            formEl.addEventListener('submit', function () {
                paymentsContainer.querySelectorAll('.group-payment-input').forEach(function (i) {
                    i.value = i.value.replace(/\s/g, '');
                });
            });

            groupSelect.addEventListener('change', renderGroupPayments);
            renderGroupPayments();
        });
    </script>

@endsection
