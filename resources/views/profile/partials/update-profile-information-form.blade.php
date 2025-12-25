<form id="send-verification" method="post" action="{{ route('verification.send') }}">
    @csrf
</form>

<form method="post" action="{{ route('profile.update') }}" class="mt-2">
    @csrf
    @method('patch')

    <div class="row">
        <div class="mb-3 col-md-6">
            <label for="name" class="form-label">Name</label>
            <input class="form-control" type="text" id="name" name="name" value="{{ old('name', $user->name) }}" autofocus required />
            <x-input-error class="mt-2" :messages="$errors->get('name')" />
        </div>
        <div class="mb-3 col-md-6">
            <label for="phone" class="form-label">Phone Number</label>
            <input class="form-control" type="text" name="phone" id="phone" value="{{ old('phone', $user->phone) }}" required />
            <x-input-error class="mt-2" :messages="$errors->get('phone')" />
        </div>
    </div>
    <div class="mt-2">
        <button type="submit" class="btn btn-primary me-2">Save changes</button>
        @if (session('status') === 'profile-updated')
            <p
                x-data="{ show: true }"
                x-show="show"
                x-transition
                x-init="setTimeout(() => show = false, 2000)"
                class="text-sm text-muted mt-2"
            >{{ __('Saved.') }}</p>
        @endif
    </div>
</form>