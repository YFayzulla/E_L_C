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
            <h4 class="mb-2 fw-bold text-center">Forgot Your Password?</h4>
            <p class="mb-4 text-start">
                No problem. Just let us know your email address and we will email you a password reset link that will allow you to choose a new one.
            </p>

            <!-- Session Status -->
            <x-auth-session-status class="mb-4" :status="session('status')" />

            <form method="POST" action="{{ route('password.email') }}">
                @csrf

                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="email" name="email" placeholder="Enter your email" autofocus value="{{ old('email') }}" required />
                    <x-input-error :messages="$errors->get('email')" class="mt-2" />
                </div>

                <button type="submit" class="btn btn-primary d-grid w-100">Email Password Reset Link</button>
            </form>

            <p class="text-center mt-3">
                <a href="{{ route('login') }}">
                    <i class="bx bx-chevron-left scaleX-n1-rtl"></i>
                    Back to login
                </a>
            </p>
        </div>
    </div>
</x-guest-layout>