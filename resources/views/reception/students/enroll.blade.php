@extends('template.master')

@section('title', 'Guruhga yozish')
@section('subtitle', $student->name)

@section('content')
    @php
        $isTeacherOnly = ! auth()->user()->hasRole('admin') && ! auth()->user()->hasRole('reception');
        $backUrl = $isTeacherOnly ? route('teacher.reception.index') : route('reception.students.index');
        $selectedGroup = (int) old('group_id', $student->recommended_group_id);
    @endphp

    <div class="page-head justify-content-end">
        <a href="{{ $backUrl }}" class="btn btn-outline-secondary">
            <i class="bx bx-arrow-back me-1"></i> Ro‘yxatga qaytish
        </a>
    </div>

    <form action="{{ route('reception.students.enroll.store', $student->id) }}" method="POST">
        @csrf

        <div class="row g-4">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">Talaba hisobi</div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="name">Ism familiya <span class="text-danger">*</span></label>
                                <input type="text" id="name" name="name" required
                                       class="form-control @error('name') is-invalid @enderror"
                                       value="{{ old('name', $student->name) }}">
                                @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label" for="phone">Telefon raqami <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">+998</span>
                                    <input type="tel" id="phone" name="phone" maxlength="9" inputmode="numeric" required
                                           class="form-control @error('phone') is-invalid @enderror"
                                           value="{{ old('phone', \App\Models\ReceptionStudent::localPhone($student->phone)) }}"
                                           placeholder="901234567">
                                </div>
                                @error('phone') <div class="text-danger mt-1" style="font-size: .8rem;">{{ $message }}</div> @enderror
                                <div class="form-text">Tizimga shu raqam orqali kiradi. Boshlang‘ich parol ham shu raqam.</div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label" for="parent_name">Ota-ona ismi</label>
                                <input type="text" id="parent_name" name="parent_name"
                                       class="form-control @error('parent_name') is-invalid @enderror"
                                       value="{{ old('parent_name', $student->parent_name) }}">
                                @error('parent_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label" for="parent_phone">Ota-ona telefoni</label>
                                <div class="input-group">
                                    <span class="input-group-text">+998</span>
                                    <input type="tel" id="parent_phone" name="parent_phone" maxlength="9" inputmode="numeric"
                                           class="form-control @error('parent_phone') is-invalid @enderror"
                                           value="{{ old('parent_phone', \App\Models\ReceptionStudent::localPhone($student->parent_phone)) }}"
                                           placeholder="901234567">
                                </div>
                                @error('parent_phone') <div class="text-danger mt-1" style="font-size: .8rem;">{{ $message }}</div> @enderror
                                <div class="form-text">Kiritilsa, ota-ona hisobi ham ochiladi.</div>
                            </div>

                            <div class="col-12">
                                <label class="form-label" for="group_id">Guruh <span class="text-danger">*</span></label>
                                <select id="group_id" name="group_id" required
                                        class="form-select @error('group_id') is-invalid @enderror">
                                    <option value="">— Guruhni tanlang —</option>
                                    @foreach($groups as $group)
                                        <option value="{{ $group->id }}" data-payment="{{ (int) $group->monthly_payment }}"
                                                @selected($selectedGroup === (int) $group->id)>
                                            {{ $group->name }}
                                            @if((int) $student->recommended_group_id === (int) $group->id) (tavsiya) @endif
                                        </option>
                                    @endforeach
                                </select>
                                @error('group_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                @if($groups->isEmpty())
                                    <div class="form-text text-danger">Yozish mumkin bo‘lgan faol guruh yo‘q.</div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card h-100">
                    <div class="card-header">Reception ma’lumoti</div>
                    <div class="card-body d-flex flex-column">
                        <dl class="mb-3" style="font-size: .9rem;">
                            <dt class="text-muted fw-normal">Daraja</dt>
                            <dd>{{ $student->levelLabel() }}</dd>
                            <dt class="text-muted fw-normal">Test</dt>
                            <dd>
                                {{ $student->test_type ?: '—' }} · {{ $student->score ?: 'natija yo‘q' }}
                                @if($student->test_image_path)
                                    <br><a href="{{ route('reception.students.test-image', $student->id) }}" target="_blank" rel="noopener">Test faylini ko‘rish</a>
                                @endif
                            </dd>
                            @if($student->notes)
                                <dt class="text-muted fw-normal">Izoh</dt>
                                <dd>{{ $student->notes }}</dd>
                            @endif
                        </dl>

                        @if($isAdmin)
                            <label class="form-label" for="payment">Oylik to‘lov</label>
                            <div class="input-group">
                                <input type="text" id="payment" name="payment" inputmode="numeric"
                                       class="form-control @error('payment') is-invalid @enderror"
                                       value="{{ old('payment') }}" placeholder="Guruh narxi">
                                <span class="input-group-text">so‘m</span>
                            </div>
                            @error('payment') <div class="text-danger mt-1" style="font-size: .8rem;">{{ $message }}</div> @enderror
                            <div class="form-text">Bo‘sh qoldirilsa guruhning standart narxi yoziladi.</div>
                        @else
                            <div class="alert alert-info mb-0">
                                Oylik to‘lovni administrator belgilaydi. Guruhning standart narxi yoziladi va
                                administratorga xabar boradi.
                            </div>
                        @endif

                        <div class="mt-auto pt-4 d-grid gap-2">
                            <button type="submit" class="btn btn-primary" @disabled($groups->isEmpty())>
                                <i class="bx bx-user-check me-1"></i> Talaba hisobini ochib, guruhga yozish
                            </button>
                            <a href="{{ $backUrl }}" class="btn btn-outline-secondary">Bekor qilish</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection
