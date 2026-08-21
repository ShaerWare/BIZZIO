{{-- #181 Каркас новой главной (эталон Bizzio_Dashboard_v26).

     Отдельный layout, а не layouts/app: перенесённая вёрстка живёт на своём CSS
     (resources/css/v26.css) и не должна смешиваться с Tailwind-темой остальных страниц. --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Bizzio.ru — соединяя бизнес (В2В бизнес-сеть)')</title>

    {{-- #152 SEO-разметка главной (Open Graph, Twitter Card, canonical, JSON-LD) — общий партиал --}}
    @include('partials.seo')

    <link rel="icon" href="/images/favicon.svg" type="image/svg+xml">
    <link rel="apple-touch-icon" href="/images/apple-touch-icon.png">

    @vite(['resources/css/v26.css', 'resources/js/v26.js'])

    {{-- Яндекс.Метрика: сюда же уходят события интереса к будущим сервисам (#181) --}}
    <script type="text/javascript">
        (function(m,e,t,r,i,k,a){m[i]=m[i]||function(){(m[i].a=m[i].a||[]).push(arguments)};
        m[i].l=1*new Date();
        for (var j = 0; j < document.scripts.length; j++) {if (document.scripts[j].src === r) { return; }}
        k=e.createElement(t),a=e.getElementsByTagName(t)[0],k.async=1,k.src=r,a.parentNode.insertBefore(k,a)})
        (window, document,'script','https://mc.yandex.ru/metrika/tag.js?id=106718528', 'ym');
        ym(106718528, 'init', {ssr:true, webvisor:true, clickmap:true, ecommerce:"dataLayer", referrer: document.referrer, url: location.href, accurateTrackBounce:true, trackLinks:true});
    </script>
    <noscript><div><img src="https://mc.yandex.ru/watch/106718528" style="position:absolute; left:-9999px;" alt="" /></div></noscript>
</head>
<body>
    @include('partials.v26.icons')

    @yield('content')

    @stack('scripts')
</body>
</html>
