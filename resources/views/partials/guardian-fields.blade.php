@php
    /**
     * Ota / ona (va ixtiyoriy vasiy) bloklari.
     *
     * @include('partials.guardian-fields', ['guardians' => $guardians ?? []])
     *
     * $guardians — ParentAccountService::guardiansOf() qaytargan massiv, yoki
     * yangi talaba uchun bo'sh massiv.
     *
     * Har bir bloк alohida ota-ona hisobini yaratadi: telefon raqami login
     * bo'ladi, pochta esa tasdiqlash uchun ishlatiladi. Telefon bo'sh bo'lsa
     * o'sha bloк butunlay e'tiborsiz qoldiriladi.
     */
    $guardians = $guardians ?? [];

    $blocks = [
        'ota'   => ['Ota',   'bx-male',   false],
        'ona'   => ['Ona',   'bx-female', false],
        'vasiy' => ['Vasiy', 'bx-user',   true],   // ixtiyoriy, yig'ilgan holda
    ];

    $has = fn(string $r) => filled(old("guardians.{$r}.phone", $guardians[$r]['phone'] ?? null));
@endphp

<div class="row g-4">
    @foreach($blocks as $relation => [$label, $icon, $collapsed])
        @php
            $saved = $guardians[$relation] ?? null;
            $open  = ! $collapsed || $has($relation);
        @endphp

        <div class="col-lg-6 {{ $relation === 'vasiy' ? 'col-lg-12' : '' }}">
            <div class="border rounded p-3 h-100" style="border-color: var(--app-border) !important;">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h6 class="mb-0">
                        <i class="bx {{ $icon }} me-1"></i>{{ $label }}
                        @if($relation === 'vasiy')
                            <span class="text-muted fw-normal" style="font-size: .8rem;">— ixtiyoriy</span>
                        @endif
                    </h6>

                    @if($saved && ($saved['email'] ?? null))
                        <span class="badge bg-label-{{ $saved['verified'] ? 'success' : 'warning' }}">
                            {{ $saved['verified'] ? 'Tasdiqlangan' : 'Tasdiqlanmagan' }}
                        </span>
                    @endif
                </div>

                <div class="row g-3 {{ $open ? '' : 'd-none' }}" id="guardian-{{ $relation }}-fields">
                    <div class="col-12">
                        <label class="form-label" for="guardian-{{ $relation }}-name">Ism familiya</label>
                        <input type="text"
                               id="guardian-{{ $relation }}-name"
                               name="guardians[{{ $relation }}][name]"
                               class="form-control @error("guardians.{$relation}.name") is-invalid @enderror"
                               value="{{ old("guardians.{$relation}.name", $saved['name'] ?? '') }}"
                               placeholder="Masalan: Aliyev Sardor">
                        @error("guardians.{$relation}.name")
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label" for="guardian-{{ $relation }}-phone">Telefon raqami</label>
                        <div class="input-group">
                            <span class="input-group-text">+998</span>
                            <input type="text"
                                   id="guardian-{{ $relation }}-phone"
                                   name="guardians[{{ $relation }}][phone]"
                                   class="form-control @error("guardians.{$relation}.phone") is-invalid @enderror"
                                   value="{{ old("guardians.{$relation}.phone", isset($saved['phone']) ? substr($saved['phone'], -9) : '') }}"
                                   placeholder="901234567" inputmode="numeric" maxlength="9">
                        </div>
                        @error("guardians.{$relation}.phone")
                            <div class="text-danger mt-1" style="font-size: .8rem;">{{ $message }}</div>
                        @enderror
                        <div class="form-text">Shu raqam bilan tizimga kiradi va SMS shu raqamga boradi.</div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label" for="guardian-{{ $relation }}-email">Pochta manzili</label>
                        <input type="email"
                               id="guardian-{{ $relation }}-email"
                               name="guardians[{{ $relation }}][email]"
                               class="form-control @error("guardians.{$relation}.email") is-invalid @enderror"
                               value="{{ old("guardians.{$relation}.email", $saved['email'] ?? '') }}"
                               placeholder="misol@pochta.uz">
                        @error("guardians.{$relation}.email")
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                @if(! $open)
                    <button type="button" class="btn btn-sm btn-outline-secondary"
                            onclick="document.getElementById('guardian-{{ $relation }}-fields').classList.remove('d-none'); this.remove();">
                        <i class="bx bx-plus me-1"></i>{{ $label }} qo‘shish
                    </button>
                @endif
            </div>
        </div>
    @endforeach
</div>

<p class="text-muted mt-3 mb-0" style="font-size: .8rem;">
    <i class="bx bx-info-circle me-1"></i>
    Telefon raqami kiritilgan har bir ota-ona uchun alohida hisob ochiladi — ular o‘z
    kabinetidan farzandining davomati, baholari va to‘lovlarini ko‘ra oladi.
    Boshlang‘ich parol — telefon raqamining oxirgi 9 ta raqami.
</p>
