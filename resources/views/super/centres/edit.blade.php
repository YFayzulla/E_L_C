@extends('template.master')

@section('title', $centre->name)
@section('subtitle', 'Markaz sozlamalari')

@section('content')

    <div class="page-head">
        <div class="d-flex align-items-center gap-3">
            <a href="{{ route('super.centres.index') }}" class="btn-icon" title="Orqaga">
                <i class="bx bx-arrow-back"></i>
            </a>
            <div>
                <div class="page-sub mb-0">
                    <a href="{{ $centre->url('/') }}">{{ $centre->host() }}</a>
                    <span class="mx-1">·</span> #{{ $centre->id }}
                </div>
            </div>
        </div>
        <span class="badge bg-label-{{ $centre->isActive() ? 'success' : 'warning' }}">
            {{ $centre->statusLabel() }}
        </span>
    </div>

    @unless($centre->waiting_room_group_id)
        <div class="alert alert-danger">
            <strong>Kutish zali yo‘q.</strong> Talaba qabul qilish, guruhdan chiqarish
            va kutish xonasi sahifasi ishlamaydi. Bu markaz konsol buyrug‘isiz
            (bazaga qo‘lda) ochilgan bo‘lishi mumkin.
        </div>
    @endunless

    <form method="POST" action="{{ route('super.centres.update', $centre->id) }}">
        @csrf
        @method('PUT')

        <div class="row g-4">
            <div class="col-lg-7">
                <div class="card mb-4">
                    <div class="card-header">Umumiy</div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="name">Nom <span class="text-danger">*</span></label>
                                <input type="text" id="name" name="name"
                                       value="{{ old('name', $centre->name) }}"
                                       class="form-control @error('name') is-invalid @enderror" required>
                                @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label" for="legal_name">Rasmiy nomi</label>
                                <input type="text" id="legal_name" name="legal_name"
                                       value="{{ old('legal_name', $centre->legal_name) }}" class="form-control">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label" for="phone">Telefon</label>
                                <input type="text" id="phone" name="phone"
                                       value="{{ old('phone', $centre->phone) }}" class="form-control">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label" for="timezone">Vaqt mintaqasi</label>
                                <input type="text" id="timezone" name="timezone"
                                       value="{{ old('timezone', $centre->timezone) }}" class="form-control">
                            </div>

                            <div class="col-12">
                                <label class="form-label" for="address">Manzil</label>
                                <input type="text" id="address" name="address"
                                       value="{{ old('address', $centre->address) }}" class="form-control">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label" for="certificate_prefix">Sertifikat prefiksi</label>
                                <input type="text" id="certificate_prefix" name="certificate_prefix"
                                       value="{{ old('certificate_prefix', $centre->certificate_prefix) }}"
                                       class="form-control" maxlength="8">
                                <div class="form-text">Berilgan sertifikatlarning seriyasi o‘zgarmaydi.</div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label" for="brand_color">Asosiy rang</label>
                                <input type="text" id="brand_color" name="brand_color"
                                       value="{{ old('brand_color', $centre->brand_color) }}"
                                       class="form-control" placeholder="#7367f0" maxlength="9">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">Eskiz SMS</div>
                    <div class="card-body">
                        <p class="text-muted" style="font-size: .84rem;">
                            Bo‘sh qoldirilsa markaz global <code>.env</code> hisobidan
                            foydalanadi. O‘z hisobi kiritilsa, SMS shu markaz nomidan
                            ketadi va uning tokeni alohida saqlanadi.
                        </p>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="sms_email">Eskiz email</label>
                                <input type="email" id="sms_email" name="sms_email"
                                       value="{{ old('sms_email', $centre->sms_email) }}" class="form-control">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label" for="sms_password">Eskiz paroli</label>
                                <input type="password" id="sms_password" name="sms_password"
                                       class="form-control" autocomplete="new-password"
                                       placeholder="{{ filled($centre->sms_password) ? '••••••• (o‘zgarmaydi)' : '' }}">
                                <div class="form-text">Bo‘sh qoldirilsa hozirgisi saqlanadi.</div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label" for="sms_from">Jo‘natuvchi nomi</label>
                                <input type="text" id="sms_from" name="sms_from"
                                       value="{{ old('sms_from', $centre->sms_from) }}"
                                       class="form-control" placeholder="4546">
                            </div>

                            <div class="col-md-6 d-flex align-items-end">
                                <div class="form-check form-switch mb-2">
                                    <input class="form-check-input" type="checkbox" id="sms_enabled"
                                           name="sms_enabled" value="1"
                                           @checked(old('sms_enabled', $centre->sms_enabled))>
                                    <label class="form-check-label" for="sms_enabled">SMS yoqilgan</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="card mb-4">
                    <div class="card-header">Holat</div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label" for="status">Markaz holati</label>
                            <select id="status" name="status" class="form-select">
                                <option value="{{ \App\Models\Centre::STATUS_ACTIVE }}"
                                    @selected(old('status', $centre->status) == \App\Models\Centre::STATUS_ACTIVE)>
                                    Faol — hammasi ishlaydi
                                </option>
                                <option value="{{ \App\Models\Centre::STATUS_SUSPENDED }}"
                                    @selected(old('status', $centre->status) == \App\Models\Centre::STATUS_SUSPENDED)>
                                    To‘xtatilgan — 503, ma’lumot joyida
                                </option>
                                <option value="{{ \App\Models\Centre::STATUS_ARCHIVED }}"
                                    @selected(old('status', $centre->status) == \App\Models\Centre::STATUS_ARCHIVED)>
                                    Arxiv — 404, tashqaridan mavjud emas
                                </option>
                            </select>
                            <div class="form-text">
                                Ma’lumot hech qaysi holatda o‘chirilmaydi. O‘zgarish darhol
                                kuchga kiradi.
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bx bx-save me-1"></i> Saqlash
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>

    <div class="card mt-4">
        <div class="card-header">Admin biriktirish</div>
        <div class="card-body">
            <p class="text-muted" style="font-size: .84rem;">
                Shu raqamli odam tizimda bo‘lsa, yangi hisob yaratilmaydi — u shu
                markazda ham admin bo‘ladi. Parol faqat yangi hisob uchun kerak.
            </p>

            <form method="POST" action="{{ route('super.centres.admin', $centre->id) }}" class="row g-3 align-items-end">
                @csrf
                <div class="col-md-3">
                    <label class="form-label" for="a_name">Ism</label>
                    <input type="text" id="a_name" name="name" class="form-control" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="a_phone">Telefon</label>
                    <input type="text" id="a_phone" name="phone" class="form-control"
                           placeholder="998901234567" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="a_email">Pochta</label>
                    <input type="email" id="a_email" name="email" class="form-control">
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="a_password">Parol</label>
                    <input type="password" id="a_password" name="password" class="form-control" minlength="6">
                </div>
                <div class="col-md-1">
                    <button type="submit" class="btn btn-outline-primary w-100">
                        <i class="bx bx-user-plus"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>

@endsection
