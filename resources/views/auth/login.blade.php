<x-guest-layout>
    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('Password')" />

            <x-text-input id="password" class="block mt-1 w-full"
                            type="password"
                            name="password"
                            required autocomplete="current-password" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Remember Me -->
        <div class="block mt-4">
            <label for="remember_me" class="inline-flex items-center">
                <input id="remember_me" type="checkbox" class="rounded border-gray-300 text-emerald-600 shadow-sm focus:ring-emerald-500" name="remember">
                <span class="ms-2 text-sm text-gray-600">{{ __('Remember me') }}</span>
            </label>
        </div>

        <div class="flex items-center justify-end mt-4">
            @if (Route::has('password.request'))
                <a class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500" href="{{ route('password.request') }}">
                    {{ __('Forgot your password?') }}
                </a>
            @endif

            <x-primary-button class="ms-3">
                {{ __('Log in') }}
            </x-primary-button>
        </div>
    </form>

    <!-- OAuth кнопки -->
    <div class="mt-6 space-y-2">
        <a href="{{ route('socialite.redirect', 'google') }}" 
           class="w-full flex items-center justify-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
            <svg class="w-5 h-5 mr-2" viewBox="0 0 24 24">
                <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
            </svg>
            Войти через Google
        </a>

        <a href="{{ route('socialite.redirect', 'yandex') }}"
           class="w-full flex items-center justify-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-emerald-600 hover:bg-emerald-700">
            <svg class="w-5 h-5 mr-2" viewBox="0 0 24 24" fill="none">
                <path d="M13.32 7.67h-.67c-1.55 0-2.37.88-2.37 2.18 0 1.47.67 2.16 2.04 3.12l1.12.78-3.25 5.25H7.85l2.88-4.58C9.2 13.27 8.18 12.05 8.18 10.08c0-2.63 1.83-4.42 5.07-4.42h3.22V19h-2.15V7.67h-1z" fill="currentColor"/>
            </svg>
            Войти через Яндекс
        </a>

        <a href="{{ route('socialite.redirect', 'vkontakte') }}"
           class="w-full flex items-center justify-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">
            <svg class="w-5 h-5 mr-2" viewBox="0 0 24 24" fill="none">
                <path d="M12.77 19.15h1.18s.36-.04.54-.23c.17-.18.16-.51.16-.51s-.02-1.56.7-1.79c.71-.23 1.63 1.53 2.6 2.2.73.51 1.29.4 1.29.4l2.59-.04s1.35-.08.71-1.14c-.05-.09-.37-.78-1.92-2.21-1.62-1.5-1.4-1.26.55-3.86 1.19-1.58 1.66-2.55 1.51-2.96-.14-.39-1.02-.29-1.02-.29l-2.91.02s-.22-.03-.38.07c-.15.09-.25.31-.25.31s-.45 1.2-1.05 2.22c-1.27 2.16-1.77 2.27-1.98 2.14-.49-.31-.36-1.24-.36-1.9 0-2.07.31-2.93-.61-3.15-.31-.07-.53-.12-1.31-.13-.99-.01-1.83 0-2.31.24-.31.15-.56.5-.41.52.18.02.6.11.82.41.28.39.27 1.25.27 1.25s.16 2.43-.38 2.73c-.37.21-.88-.21-1.97-2.11-.56-.97-.98-2.05-.98-2.05s-.08-.2-.23-.3c-.18-.13-.43-.17-.43-.17l-2.77.02s-.41.01-.57.19c-.13.16-.01.5-.01.5s2.12 4.97 4.53 7.48c2.2 2.3 4.7 2.15 4.7 2.15z" fill="currentColor"/>
            </svg>
            Войти через VK
        </a>
    </div>
</x-guest-layout>