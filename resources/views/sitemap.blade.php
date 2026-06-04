{!! '<'.'?xml version="1.0" encoding="UTF-8"?'.'>' !!}
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
@foreach($urls as $u)
    <url>
        <loc>{{ $u['loc'] }}</loc>
@if(!empty($u['lastmod']))        <lastmod>{{ $u['lastmod'] }}</lastmod>
@endif
        <changefreq>{{ $u['changefreq'] ?? 'weekly' }}</changefreq>
        <priority>{{ $u['priority'] ?? '0.6' }}</priority>
    </url>
@endforeach
</urlset>
