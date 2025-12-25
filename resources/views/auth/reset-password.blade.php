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
            <h4 class="mb-2 fw-bold text-center">Reset Your Password</h4>
            <p class="mb-4 text-start">
                Set your new password for your account.
            </p>

            <form method="POST" action="{{ route('password.store') }}">
                @csrf

                <!-- Password Reset Token -->
                <input type="hidden" name="token" value="{{ $request->route('token') }}">

                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="email" name="email" placeholder="Enter your email" autofocus value="{{ old('email', $request->email) }}" required />
                    <x-input-error :messages="$errors->get('email')" class="mt-2" />
                </div>

                <div class="mb-3 form-password-toggle">
                    <label class="form-label" for="password">New Password</label>
                    <div class="input-group input-group-merge">
                        <input type="password" id="password" class="form-control" name="password" placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;" aria-describedby="password" required autocomplete="new-password" />
                        <span class="input-group-text cursor-pointer"><i class="bx bx-hide"></i></span>
                    </div>
                    <x-input-error :messages="$errors->get('password')" class="mt-2" />
                </div>

                <div class="mb-3 form-password-toggle">
                    <label class="form-label" for="password_confirmation">Confirm Password</label>
                    <div class="input-group input-group-merge">
                        <input type="password" id="password_confirmation" class="form-control" name="password_confirmation" placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;" aria-describedby="password_confirmation" required autocomplete="new-password" />
                        <span class="input-group-text cursor-pointer"><i class="bx bx-hide"></i></span>
                    </div>
                    <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
                </div>

                <button type="submit" class="btn btn-primary d-grid w-100">Reset Password</button>
            </form>
        </div>
    </div>
</x-guest-layout>