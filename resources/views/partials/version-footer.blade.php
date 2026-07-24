{{-- #184 Версия приложения (различима на тесте и проде) --}}
@php
    $appEnv = app()->environment();
    $isProd = $appEnv === 'production';
@endphp
<footer class="mt-8 py-4 border-t border-gray-200">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center text-xs text-gray-400">
        Bizzio.ru · версия <span class="font-mono">{{ config('app.version', 'dev') }}</span>
        @unless($isProd)
            · <span class="inline-flex items-center px-1.5 py-0.5 rounded bg-yellow-100 text-yellow-700 font-medium uppercase">{{ $appEnv }}</span>
        @endunless
    </div>
</footer>
