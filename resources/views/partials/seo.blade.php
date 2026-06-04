{{-- #152: SEO meta, Open Graph, Twitter Card, canonical и Schema.org JSON-LD --}}
@php
    $seoDefaultDescription = 'Bizzio.ru — B2B бизнес-сеть для строительной отрасли: компании, проекты, тендеры и аукционы, закупки и отраслевые новости.';
    $seoDescription = trim($__env->yieldContent('meta_description', $seoDefaultDescription));
    $seoTitle = trim($__env->yieldContent('title', config('app.name', 'Bizzio')));
    $seoFullTitle = $__env->yieldContent('title')
        ? $seoTitle.' — '.config('app.name', 'Bizzio')
        : config('app.name', 'Bizzio').' — B2B бизнес-сеть для строительной отрасли';
    $seoCanonical = url()->current();
    $seoImage = trim($__env->yieldContent('og_image', asset('images/bizzio_horizontal_logo_color_whitebg.svg')));
@endphp
<meta name="description" content="{{ $seoDescription }}">
<link rel="canonical" href="{{ $seoCanonical }}">

<meta property="og:site_name" content="{{ config('app.name', 'Bizzio') }}">
<meta property="og:type" content="@yield('og_type', 'website')">
<meta property="og:title" content="{{ $seoFullTitle }}">
<meta property="og:description" content="{{ $seoDescription }}">
<meta property="og:url" content="{{ $seoCanonical }}">
<meta property="og:image" content="{{ $seoImage }}">
<meta property="og:locale" content="ru_RU">

<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $seoFullTitle }}">
<meta name="twitter:description" content="{{ $seoDescription }}">
<meta name="twitter:image" content="{{ $seoImage }}">

<script type="application/ld+json">
{!! json_encode([
    '@context' => 'https://schema.org',
    '@graph' => [
        [
            '@type' => 'Organization',
            '@id' => url('/').'#organization',
            'name' => 'Bizzio',
            'url' => url('/'),
            'logo' => asset('images/bizzio_horizontal_logo_color_whitebg.svg'),
            'description' => $seoDefaultDescription,
            'email' => 'admin@bizzio.ru',
            'areaServed' => 'RU',
        ],
        [
            '@type' => 'WebSite',
            '@id' => url('/').'#website',
            'url' => url('/'),
            'name' => 'Bizzio',
            'description' => $seoDefaultDescription,
            'inLanguage' => 'ru-RU',
            'publisher' => ['@id' => url('/').'#organization'],
        ],
    ],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
</script>
