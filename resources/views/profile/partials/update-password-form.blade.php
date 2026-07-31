<p class="text-muted mb-4" style="font-size: .875rem;">
    Hisobingiz xavfsizligi uchun uzun va boshqa joyda ishlatilmagan parol tanlang.
</p>

@if (session('status') === 'password-updated')
    <div class="alert alert-success py-2" role="status">Parol yangilandi.</div>
@endif

<form method="post" action="{{ route('password.update') }}">
    @csrf
    @method('put')

    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label" for="current_password">Joriy parol</label>
            <input type="password" id="current_password" name="current_password" autocomplete="current-password"
                   class="form-control @if($errors->updatePassword->has('current_password')) is-invalid @endif">
            <x-input-error class="mt-1" :messages="$errors->updatePassword->get('current_password')"/>
        </div>

        <div class="col-md-6"></div>

        <div class="col-md-6">
            <label class="form-label" for="password">Yangi parol</label>
            <input type="password" id="password" name="password" autocomplete="new-password"
                   class="form-control @if($errors->updatePassword->has('password')) is-invalid @endif">
            <x-input-error class="mt-1" :messages="$errors->updatePassword->get('password')"/>
        </div>

        <div class="col-md-6">
            <label class="form-label" for="password_confirmation">Yangi parolni tasdiqlang</label>
            <input type="password" id="password_confirmation" name="password_confirmation" autocomplete="new-password"
                   class="form-control @if($errors->updatePassword->has('password_confirmation')) is-invalid @endif">
            <x-input-error class="mt-1" :messages="$errors->updatePassword->get('password_confirmation')"/>
        </div>
    </div>

    <div class="mt-4">
        <button type="submit" class="btn btn-primary">
            <i class="bx bx-lock-alt me-1"></i> Parolni yangilash
        </button>
    </div>
</form>
