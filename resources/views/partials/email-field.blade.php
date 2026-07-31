{{--
    Shared "Pochta manzili" input for every account form (talaba, o‘qituvchi, profil).

    Usage — the signature is fixed, other workstreams include this exact path:
        @include('partials.email-field', ['user' => $student ?? null])

    $user may be null (create forms). When it carries an address we also show
    whether that address has been confirmed.
--}}
@php
    /** @var \App\Models\User|null $user */
    $user = $user ?? null;
    $currentEmail = $user?->email;
@endphp

<div class="col-md-6">
    <label class="form-label" for="email">Pochta manzili</label>

    <input type="email"
           id="email"
           name="email"
           class="form-control @error('email') is-invalid @enderror"
           value="{{ old('email', $currentEmail) }}"
           placeholder="misol@pochta.uz"
           dir="ltr"
           autocomplete="email"
           maxlength="191">
    @error('email')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror

    @if(filled($currentEmail))
        <div class="mt-2">
            @if($user->email_verified_at)
                <span class="badge bg-label-success">
                    <i class="bx bx-check-circle me-1"></i> Tasdiqlangan
                </span>
            @else
                <span class="badge bg-label-warning">
                    <i class="bx bx-time-five me-1"></i> Tasdiqlanmagan
                </span>
            @endif
        </div>
    @endif

    <div class="form-text">
        Ixtiyoriy. Hisobni tasdiqlash uchun ishlatiladi. Bo‘sh qoldirilsa, saqlangan manzil o‘chadi.
    </div>
</div>
