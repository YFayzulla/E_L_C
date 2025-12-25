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
            <h4 class="mb-2 fw-bold">Adventure starts here 🚀</h4>
            <p class="mb-4">Make your app management easy and fun!</p>

            <form id="formAuthentication" class="mb-3" method="POST" action="{{ route('register') }}">
                @csrf

                <div class="mb-3">
                    <label for="name" class="form-label">Full Name</label>
                    <input type="text" class="form-control" id="name" name="name" placeholder="Enter your name" autofocus value="{{ old('name') }}" required autocomplete="name" />
                    <x-input-error :messages="$errors->get('name')" class="mt-2" />
                </div>

                <div class="mb-3">
                    <label for="phone" class="form-label">Phone Number</label>
                    <div class="input-group input-group-merge">
                        <span class="input-group-text">+998</span>
                        <input type="tel" id="phone" name="phone" maxlength="9" placeholder="912345678" class="form-control" value="{{ old('phone') }}" required autocomplete="tel" />
                    </div>
                    <x-input-error :messages="$errors->get('phone')" class="mt-2" />
                </div>

                <div class="mb-3 form-password-toggle">
                    <label class="form-label" for="password">Password</label>
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

                <div class="mb-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="terms-conditions" name="terms">
                        <label class="form-check-label" for="terms-conditions">
                            I agree to
                            <a href="javascript:void(0);">privacy policy & terms</a>
                        </label>
                    </div>
                </div>
                <button class="btn btn-primary d-grid w-100">Sign up</button>
            </form>

            <p class="text-center">
                <span>Already have an account?</span>
                <a href="{{ route('login') }}">
                    <span>Sign in instead</span>
                </a>
            </p>

            <div class="divider my-4">
                <div class="divider-text">or</div>
            </div>

            <div class="d-flex justify-content-center">
                <a href="javascript:;" class="btn btn-icon btn-label-google-plus me-3">
                    <i class="tf-icons bx bxl-google-plus"></i>
                </a>
                <a href="javascript:;" class="btn btn-icon btn-label-facebook">
                    <i class="tf-icons bx bxl-facebook"></i>
                </a>
            </div>
        </div>
    </div>
</x-guest-layout>