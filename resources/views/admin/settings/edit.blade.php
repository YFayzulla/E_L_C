@extends('template.master')

@section('title', 'Sozlamalar')
@section('subtitle', $centre->name)

@section('content')

    <form method="POST" action="{{ route('settings.update') }}">
        @csrf
        @method('PUT')

        <div class="row g-4">
            <div class="col-lg-7">
                <div class="card">
                    <div class="card-header">Dars jarayonidagi baholash</div>
                    <div class="card-body">
                        <p class="text-muted" style="font-size: .86rem;">
                            O‘qituvchi dars davomida talabaga qanday baho qo‘yishini
                            belgilaydi. Oylik test ham shu usulda ishlaydi.
                        </p>

                        @php
                            $skills = collect(config('grading.skill_labels', []))
                                ->except(config('grading.single_skill', 'overall'));
                        @endphp

                        <div class="form-check card p-3 mb-3 {{ $skillMode === 'skills' ? 'border-primary' : '' }}">
                            <input class="form-check-input" type="radio" name="skill_mode"
                                   id="mode_skills" value="skills" @checked($skillMode === 'skills')>
                            <label class="form-check-label w-100" for="mode_skills">
                                <span class="d-block fw-semibold mb-1">Ko‘nikmalar bo‘yicha</span>
                                <span class="d-block text-muted" style="font-size: .84rem;">
                                    Har bir ko‘nikmaga alohida baho:
                                    {{ $skills->implode(', ') }}.
                                    Umumiy ball ularning o‘rta arifmetigi sifatida
                                    avtomatik hisoblanadi.
                                </span>
                            </label>
                        </div>

                        <div class="form-check card p-3 mb-0 {{ $skillMode === 'single' ? 'border-primary' : '' }}">
                            <input class="form-check-input" type="radio" name="skill_mode"
                                   id="mode_single" value="single" @checked($skillMode === 'single')>
                            <label class="form-check-label w-100" for="mode_single">
                                <span class="d-block fw-semibold mb-1">Bitta umumiy baho</span>
                                <span class="d-block text-muted" style="font-size: .84rem;">
                                    Dars uchun bitta ustun — o‘qituvchi bitta ball qo‘yadi.
                                    Ko‘nikmalarga bo‘lish shart bo‘lmagan guruhlar uchun
                                    qulayroq.
                                </span>
                            </label>
                        </div>

                        @error('skill_mode')
                            <div class="text-danger mt-2" style="font-size: .84rem;">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="card-footer d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary">
                            <i class="bx bx-save me-1"></i> Saqlash
                        </button>
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="card h-100">
                    <div class="card-header">Usul o‘zgarganda nima bo‘ladi</div>
                    <div class="card-body" style="font-size: .86rem;">
                        <p>
                            <strong>Eski baholar o‘chmaydi.</strong> Ular qaysi ko‘nikma
                            bilan qo‘yilgan bo‘lsa, o‘shanday saqlanadi va hisobotlarda
                            ko‘rinaveradi — hisobot ustunlari ma’lumotdan ham
                            to‘ldiriladi, faqat sozlamadan emas.
                        </p>
                        <p>
                            <strong>O‘zlashtirish ko‘rsatkichi buzilmaydi.</strong> U
                            baholarning o‘rtachasini oladi va qaysi kalit bilan
                            yozilganiga qaramaydi.
                        </p>
                        <p class="mb-0">
                            <strong>Istalgan payt qaytarish mumkin.</strong> Usulni
                            qayta o‘zgartirsangiz, ikkala davrdagi baholar ham
                            joyida qoladi.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </form>

@endsection
