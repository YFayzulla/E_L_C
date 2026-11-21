{{--
    Chegirma berish formasi: foiz, doimiy yoki muddatli, sabab.

    @param string $action          forma manzili
    @param string $prefix          id'lar uchun (sahifada bir nechta forma bo'lsa)
    @param \Illuminate\Support\Collection|null $groups  talabaning guruhlari (null — guruh tanlanmaydi)
    @param string $submitLabel
--}}
@php
    $prefix = $prefix ?? 'discount';
    $period = old('period', 'permanent');
@endphp

<form action="{{ $action }}" method="post" class="js-discount-form">
    @csrf
    <div class="row g-3">
        <div class="col-sm-4">
            <label class="form-label" for="{{ $prefix }}-percent">Chegirma, %</label>
            <div class="input-group">
                <input type="number" min="1" max="100" step="1" required
                       id="{{ $prefix }}-percent" name="percent" value="{{ old('percent') }}"
                       class="form-control @error('percent') is-invalid @enderror" placeholder="20">
                <span class="input-group-text">%</span>
            </div>
            @error('percent') <div class="text-danger mt-1" style="font-size: .8rem;">{{ $message }}</div> @enderror
        </div>

        @if(isset($groups) && $groups !== null)
            <div class="col-sm-8">
                <label class="form-label" for="{{ $prefix }}-group">Qaysi guruhga</label>
                <select id="{{ $prefix }}-group" name="group_id" class="form-select @error('group_id') is-invalid @enderror">
                    <option value="">Barcha guruhlariga</option>
                    @foreach($groups as $group)
                        <option value="{{ $group->id }}" @selected((int) old('group_id') === (int) $group->id)>{{ $group->name }}</option>
                    @endforeach
                </select>
                @error('group_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
        @endif

        <div class="col-12">
            <div class="d-flex flex-wrap gap-3">
                <div class="form-check">
                    <input class="form-check-input js-period" type="radio" name="period" value="permanent"
                           id="{{ $prefix }}-permanent" @checked($period === 'permanent')>
                    <label class="form-check-label" for="{{ $prefix }}-permanent">Doimiy</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input js-period" type="radio" name="period" value="limited"
                           id="{{ $prefix }}-limited" @checked($period === 'limited')>
                    <label class="form-check-label" for="{{ $prefix }}-limited">Muddatli</label>
                </div>
            </div>
        </div>

        <div class="col-sm-6">
            <label class="form-label" for="{{ $prefix }}-starts">Boshlanish sanasi</label>
            <input type="date" id="{{ $prefix }}-starts" name="starts_on" required
                   value="{{ old('starts_on', today()->toDateString()) }}"
                   class="form-control @error('starts_on') is-invalid @enderror">
            @error('starts_on') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="col-sm-6 js-ends {{ $period === 'limited' ? '' : 'd-none' }}">
            <label class="form-label" for="{{ $prefix }}-ends">Tugash sanasi</label>
            <input type="date" id="{{ $prefix }}-ends" name="ends_on" value="{{ old('ends_on') }}"
                   class="form-control @error('ends_on') is-invalid @enderror">
            @error('ends_on') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="col-12">
            <label class="form-label" for="{{ $prefix }}-reason">Sabab</label>
            <input type="text" id="{{ $prefix }}-reason" name="reason" maxlength="255" value="{{ old('reason') }}"
                   class="form-control @error('reason') is-invalid @enderror" placeholder="Masalan: aka-uka, a’lochi, aksiya">
        </div>

        <div class="col-12">
            <button type="submit" class="btn btn-primary">
                <i class="bx bx-purchase-tag-alt me-1"></i> {{ $submitLabel ?? 'Chegirma berish' }}
            </button>
        </div>
    </div>
</form>

@once
    <script>
        // "Muddatli" tanlansa tugash sanasi maydoni ochiladi.
        document.addEventListener('change', (event) => {
            if (!event.target.classList?.contains('js-period')) return;
            const form = event.target.closest('.js-discount-form');
            const ends = form?.querySelector('.js-ends');
            if (!ends) return;
            const limited = event.target.value === 'limited';
            ends.classList.toggle('d-none', !limited);
            const input = ends.querySelector('input');
            if (input) { input.required = limited; if (!limited) input.value = ''; }
        });
    </script>
@endonce
