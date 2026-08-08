@extends('template.master')

@section('title', 'Yangi o‘quv markazi')
@section('subtitle', 'Platforma boshqaruvi')

@section('content')

    <div class="page-head">
        <div class="d-flex align-items-center gap-3">
            <a href="{{ route('super.centres.index') }}" class="btn-icon" title="Orqaga">
                <i class="bx bx-arrow-back"></i>
            </a>
            <div class="page-sub mb-0">
                Markaz, uning kutish zali, SMS shablonlari va birinchi admini
                birgalikda ochiladi — biri yaratilmasa, hech biri yaratilmaydi.
            </div>
        </div>
    </div>

    <form method="POST" action="{{ route('super.centres.store') }}">
        @csrf

        <div class="row g-4">
            <div class="col-lg-7">
                <div class="card">
                    <div class="card-header">Markaz</div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="slug">
                                    Subdomen (slug) <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <input type="text" id="slug" name="slug" value="{{ old('slug') }}"
                                           class="form-control @error('slug') is-invalid @enderror"
                                           placeholder="beta" required autofocus>
                                    <span class="input-group-text">.{{ config('app.domain') }}</span>
                                </div>
                                @error('slug') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                                <div class="form-text">
                                    Kichik harf, raqam va tire. Keyinchalik o‘zgartirish
                                    markazning barcha havolalarini buzadi.
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label" for="name">
                                    Markaz nomi <span class="text-danger">*</span>
                                </label>
                                <input type="text" id="name" name="name" value="{{ old('name') }}"
                                       class="form-control @error('name') is-invalid @enderror"
                                       placeholder="BETA o‘quv markazi" required>
                                @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label" for="certificate_prefix">Sertifikat prefiksi</label>
                                <input type="text" id="certificate_prefix" name="certificate_prefix"
                                       value="{{ old('certificate_prefix') }}" class="form-control"
                                       placeholder="BET" maxlength="8">
                                <div class="form-text">Bo‘sh qoldirilsa slug‘ning dastlabki uch harfi olinadi.</div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label" for="timezone">Vaqt mintaqasi</label>
                                <input type="text" id="timezone" name="timezone"
                                       value="{{ old('timezone', config('app.timezone', 'Asia/Tashkent')) }}"
                                       class="form-control">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="card h-100">
                    <div class="card-header">Birinchi admin</div>
                    <div class="card-body">
                        <p class="text-muted" style="font-size: .84rem;">
                            Shu raqamli odam allaqachon tizimda bo‘lsa, yangi hisob
                            yaratilmaydi — u shu markazda ham admin bo‘ladi.
                        </p>

                        <div class="mb-3">
                            <label class="form-label" for="admin_name">Ism <span class="text-danger">*</span></label>
                            <input type="text" id="admin_name" name="admin_name"
                                   value="{{ old('admin_name') }}"
                                   class="form-control @error('admin_name') is-invalid @enderror" required>
                            @error('admin_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="admin_phone">Telefon <span class="text-danger">*</span></label>
                            <input type="text" id="admin_phone" name="admin_phone"
                                   value="{{ old('admin_phone') }}" placeholder="998901234567"
                                   class="form-control @error('admin_phone') is-invalid @enderror" required>
                            @error('admin_phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="admin_email">Pochta</label>
                            <input type="email" id="admin_email" name="admin_email"
                                   value="{{ old('admin_email') }}"
                                   class="form-control @error('admin_email') is-invalid @enderror">
                            @error('admin_email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-0">
                            <label class="form-label" for="admin_password">Parol <span class="text-danger">*</span></label>
                            <input type="password" id="admin_password" name="admin_password"
                                   class="form-control @error('admin_password') is-invalid @enderror"
                                   minlength="6" required>
                            @error('admin_password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            <div class="form-text">Kamida 6 belgi. Adminga alohida yetkazing.</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-end gap-2 mt-4">
            <a href="{{ route('super.centres.index') }}" class="btn btn-outline-secondary">Bekor qilish</a>
            <button type="submit" class="btn btn-primary">
                <i class="bx bx-plus me-1"></i> Markazni ochish
            </button>
        </div>
    </form>

@endsection
