<x-guest-layout>
    <!-- 1. LOGO VA SARLAVHA (Tuzatilgan qism) -->
    <div class="mb-6 text-center m-2">
        <a href="/" class="flex justify-center mb-4">
            <!-- MUHIM: h-16 (64px) klassi logotipni ixcham qiladi -->
            <img src="{{ asset('logos/main.png') }}" alt="Logo" style="width: 100px"/>
        </a>
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')"/>

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <!-- 2. NAME / NUMBER INPUT -->
        <div>
            <x-input-label for="login" :value="__('Name / Number')"/>
            <x-text-input
                    id="login"
                    class="block mt-1 w-full px-4 py-2 border rounded-md focus:ring-indigo-500 focus:border-indigo-500"
                    type="text"
                    name="login"
                    :value="old('login')"
                    required
                    autofocus
                    placeholder="Username or Phone"
            />
            <x-input-error :messages="$errors->get('login')" class="mt-2"/>
        </div>

        <!-- 3. PASSWORD INPUT -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('Password')"/>

            <x-text-input
                    id="password"
                    class="block mt-1 w-full px-4 py-2 border rounded-md focus:ring-indigo-500 focus:border-indigo-500"
                    type="password"
                    name="password"
                    required
                    autocomplete="current-password"
                    placeholder="••••••••"
            />

            <x-input-error :messages="$errors->get('password')" class="mt-2"/>
        </div>


        <!-- 5. LOGIN BUTTON -->
        <div class="mt-6 mb-6">
            <x-primary-button class="w-full justify-center py-3 text-base bg-gray-800 hover:bg-gray-700">
                {{ __('Log in') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>