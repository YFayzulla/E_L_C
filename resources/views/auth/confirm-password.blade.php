<x-guest-layout>
    <div class="card">
        <div class="card-body">
            <div class="app-brand justify-content-center mb-4 mt-2">
                <a href="/" class="app-brand-link gap-2">
                    <span class="app-brand-logo demo">
                        <img src="{{ asset('logos/SymbolRed.svg') }}" alt="Logo" width="40">
                    </span>
                    <span class="app-brand-text demo text-body fw-bold">SpeakUp</span>
                </a>
            </div>
            <h4 class="mb-2 fw-bold text-center">Confirm Your Password</h4>
            <p class="mb-4 text-start">
                This is a secure area of the application. Please confirm your password before continuing.
            </p>

            <form method="POST" action="{{ route('password.confirm') }}">
                @csrf

                <div class="mb-3 form-password-toggle">
                    <label class="form-label" for="password">Password</label>
                    <div class="input-group input-group-merge">
                        <input type="password" id="password" class="form-control" name="password" placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;" aria-describedby="password" required autocomplete="current-password" />
                        <span class="input-group-text cursor-pointer"><i class="bx bx-hide"></i></span>
                    </div>
                    <x-input-error :messages="$errors->get('password')" class="mt-2" />
                </div>

                <button type="submit" class="btn btn-primary d-grid w-100">Confirm</button>
            </form>
        </div>
    </div>
</x-guest-layout>