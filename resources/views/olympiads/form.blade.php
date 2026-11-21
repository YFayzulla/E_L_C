@extends('template.master')

@section('title', $olympiad ? 'Olimpiadani tahrirlash' : 'Yangi olimpiada')

@section('content')
    @php
        $sections = old('sections', $olympiad?->sections ?? [['key' => '', 'label' => 'Umumiy', 'max' => 100]]);
    @endphp

    <div class="page-head justify-content-end">
        <a href="{{ $olympiad ? route('olympiads.show', $olympiad) : route('olympiads.index') }}" class="btn btn-outline-secondary">
            <i class="bx bx-arrow-back me-1"></i> Orqaga
        </a>
    </div>

    <form method="POST" action="{{ $olympiad ? route('olympiads.update', $olympiad) : route('olympiads.store') }}">
        @csrf
        @if($olympiad) @method('PUT') @endif

        <div class="row g-4">
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">Olimpiada</div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label" for="name">Nomi <span class="text-danger">*</span></label>
                            <input type="text" id="name" name="name" required maxlength="255"
                                   class="form-control @error('name') is-invalid @enderror"
                                   value="{{ old('name', $olympiad?->name) }}" placeholder="Masalan: Qishki olimpiada 2026">
                            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-sm-6">
                                <label class="form-label" for="held_on">O‘tkaziladigan sana</label>
                                <input type="date" id="held_on" name="held_on" class="form-control"
                                       value="{{ old('held_on', $olympiad?->held_on?->toDateString()) }}">
                            </div>
                            @if($olympiad)
                                <div class="col-sm-6">
                                    <label class="form-label" for="status">Holat</label>
                                    <select id="status" name="status" class="form-select">
                                        @foreach(\App\Models\Olympiad::statuses() as $value => $label)
                                            <option value="{{ $value }}" @selected(old('status', $olympiad->status) === $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif
                        </div>
                        <label class="form-label" for="notes">Izoh</label>
                        <textarea id="notes" name="notes" rows="3" class="form-control">{{ old('notes', $olympiad?->notes) }}</textarea>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span>Bo‘limlar va maksimal ball</span>
                        <button type="button" class="btn btn-sm btn-outline-primary" id="addSection">
                            <i class="bx bx-plus me-1"></i> Bo‘lim
                        </button>
                    </div>
                    <div class="card-body">
                        <p class="text-muted" style="font-size: .84rem;">
                            Masalan: Grammatika / 40, O‘qish / 30, Tinglash / 30. Bitta bo‘lim ham bo‘lishi mumkin.
                            O‘rin foiz bo‘yicha (jami ball / jami maksimum) hisoblanadi.
                        </p>
                        @error('sections') <div class="alert alert-danger py-2">{{ $message }}</div> @enderror
                        <div id="sectionRows">
                            @foreach($sections as $i => $section)
                                <div class="row g-2 mb-2 js-section-row">
                                    <input type="hidden" name="sections[{{ $i }}][key]" value="{{ $section['key'] ?? '' }}">
                                    <div class="col-7">
                                        <input type="text" name="sections[{{ $i }}][label]" required maxlength="100"
                                               class="form-control" value="{{ $section['label'] ?? '' }}" placeholder="Bo‘lim nomi">
                                    </div>
                                    <div class="col-3">
                                        <input type="number" name="sections[{{ $i }}][max]" required min="1" max="1000"
                                               class="form-control" value="{{ $section['max'] ?? 100 }}" placeholder="Maks.">
                                    </div>
                                    <div class="col-2 d-grid">
                                        <button type="button" class="btn btn-outline-danger js-remove-section" title="Olib tashlash">
                                            <i class="bx bx-x"></i>
                                        </button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        @if($olympiad)
                            <div class="form-text">Bo‘lim olib tashlansa, undagi ballar hisobga kirmaydi.</div>
                        @endif
                    </div>
                    <div class="card-footer d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary"><i class="bx bx-save me-1"></i> Saqlash</button>
                    </div>
                </div>
            </div>
        </div>
    </form>

    <script>
        (function () {
            const rows = document.getElementById('sectionRows');
            let index = rows.querySelectorAll('.js-section-row').length + 100;

            document.getElementById('addSection').addEventListener('click', () => {
                const i = index++;
                rows.insertAdjacentHTML('beforeend', `
                    <div class="row g-2 mb-2 js-section-row">
                        <input type="hidden" name="sections[${i}][key]" value="">
                        <div class="col-7"><input type="text" name="sections[${i}][label]" required maxlength="100" class="form-control" placeholder="Bo‘lim nomi"></div>
                        <div class="col-3"><input type="number" name="sections[${i}][max]" required min="1" max="1000" class="form-control" value="100"></div>
                        <div class="col-2 d-grid"><button type="button" class="btn btn-outline-danger js-remove-section" title="Olib tashlash"><i class="bx bx-x"></i></button></div>
                    </div>`);
            });

            rows.addEventListener('click', (event) => {
                const button = event.target.closest('.js-remove-section');
                if (!button) return;
                if (rows.querySelectorAll('.js-section-row').length <= 1) {
                    alert('Kamida bitta bo‘lim bo‘lishi kerak.');
                    return;
                }
                button.closest('.js-section-row').remove();
            });
        })();
    </script>
@endsection
