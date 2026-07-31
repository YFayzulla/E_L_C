@extends('template.master')

@section('title', 'Sertifikat berish')
@section('subtitle', 'Bitiruvchiga hujjat rasmiylashtirish')

@section('content')

    <div class="page-head">
        <div class="d-flex align-items-center gap-3">
            <a href="{{ route('certificates.index') }}" class="btn-icon" title="Orqaga">
                <i class="bx bx-arrow-back"></i>
            </a>
            <div>
                <div class="page-sub">Keyingi seriya raqami — <strong dir="ltr">{{ $serial }}</strong></div>
            </div>
        </div>
    </div>

    <form action="{{ route('certificates.store') }}" method="post" enctype="multipart/form-data">
        @csrf

        <div class="row g-4">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">Sertifikat ma’lumotlari</div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="user_id">Talaba <span class="text-danger">*</span></label>
                                <select id="user_id" name="user_id"
                                        class="choices form-select @error('user_id') is-invalid @enderror"
                                        data-placeholder="Talaba qidiring…" required>
                                    <option value=""></option>
                                    @foreach($students as $student)
                                        <option value="{{ $student->id }}"
                                                @selected(old('user_id', $preselect) == $student->id)>
                                            {{ $student->name }}@if($student->phone) — +{{ $student->phone }}@endif
                                        </option>
                                    @endforeach
                                </select>
                                @error('user_id') <div class="text-danger mt-1" style="font-size: .8rem;">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label" for="group_id">Guruh</label>
                                <select id="group_id" name="group_id" class="form-select">
                                    <option value="">— tanlanmagan —</option>
                                    @foreach($groups as $group)
                                        <option value="{{ $group->id }}" @selected(old('group_id') == $group->id)>
                                            {{ $group->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-12">
                                <label class="form-label" for="title">Sertifikat nomi <span class="text-danger">*</span></label>
                                <input type="text" id="title" name="title"
                                       class="form-control @error('title') is-invalid @enderror"
                                       value="{{ old('title', 'General English kursini muvaffaqiyatli tamomlagani uchun') }}"
                                       required>
                                @error('title') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-4">
                                <label class="form-label" for="level">Daraja</label>
                                <input type="text" id="level" name="level" class="form-control"
                                       value="{{ old('level') }}" placeholder="B1, IELTS 6.5 …">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label" for="final_score">Yakuniy ball</label>
                                <input type="number" id="final_score" name="final_score" min="0" max="100"
                                       class="form-control @error('final_score') is-invalid @enderror"
                                       value="{{ old('final_score') }}" placeholder="0–100">
                                @error('final_score') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-4">
                                <label class="form-label" for="issued_at">Berilgan sana <span class="text-danger">*</span></label>
                                <input type="date" id="issued_at" name="issued_at"
                                       class="form-control @error('issued_at') is-invalid @enderror"
                                       value="{{ old('issued_at', now()->toDateString()) }}" required>
                                @error('issued_at') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-12">
                                <label class="form-label" for="note">Izoh</label>
                                <textarea id="note" name="note" rows="2" class="form-control"
                                          placeholder="Ixtiyoriy — sertifikatda ko‘rinadi">{{ old('note') }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card h-100">
                    <div class="card-header">Fayl</div>
                    <div class="card-body d-flex flex-column">
                        <label class="form-label" for="file">Tayyor sertifikat (ixtiyoriy)</label>
                        <input type="file" id="file" name="file"
                               class="form-control @error('file') is-invalid @enderror"
                               accept=".pdf,.jpg,.jpeg,.png">
                        @error('file') <div class="invalid-feedback">{{ $message }}</div> @enderror

                        <div class="form-text mt-2">
                            Dizayn qilingan sertifikat bo‘lsa shu yerga yuklang (PDF yoki rasm, 5 MB gacha).
                            <strong>Bo‘sh qoldirsangiz</strong> — tizim shablon asosida PDF yaratib beradi.
                        </div>

                        <div class="alert alert-info mt-3 py-2" style="font-size: .82rem;">
                            <i class="bx bx-info-circle me-1"></i>
                            Talaba sertifikatni o‘z profilidan (<em>Sertifikatlarim</em>) yuklab oladi.
                        </div>

                        <div class="mt-auto pt-3 d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bx bx-save me-1"></i> Berish
                            </button>
                            <a href="{{ route('certificates.index') }}" class="btn btn-outline-secondary">Bekor qilish</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>

@endsection
