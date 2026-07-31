<form method="post" action="{{ route('profile.update') }}">
    @csrf
    @method('patch')

    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label" for="name">Ism familiya</label>
            <input class="form-control @error('name') is-invalid @enderror" type="text" id="name" name="name"
                   value="{{ old('name', $user->name) }}" required>
            <x-input-error class="mt-1" :messages="$errors->get('name')"/>
        </div>

        <div class="col-md-6">
            <label class="form-label" for="phone">Telefon raqami</label>
            <input class="form-control @error('phone') is-invalid @enderror" type="text" id="phone" name="phone"
                   value="{{ old('phone', $user->phone) }}" dir="ltr" required>
            <x-input-error class="mt-1" :messages="$errors->get('phone')"/>
        </div>

        @include('partials.email-field', ['user' => $user])
    </div>

    <div class="mt-4">
        <button type="submit" class="btn btn-primary">
            <i class="bx bx-save me-1"></i> Saqlash
        </button>
    </div>
</form>
