{!! '<'.'?xml version="1.0" encoding="UTF-8"?'.'>' !!}
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">
@foreach ($pages as $page)
@foreach ($page['paths'] as $locale => $url)
    <url>
        <loc>{{ $url }}</loc>
@foreach ($page['paths'] as $alternateLocale => $alternateUrl)
        <xhtml:link rel="alternate" hreflang="{{ $alternateLocale }}" href="{{ $alternateUrl }}"/>
@endforeach
        <xhtml:link rel="alternate" hreflang="x-default" href="{{ $page['paths'][$defaultLocale] }}"/>
@if ($page['lastmod'])
        <lastmod>{{ $page['lastmod']->toAtomString() }}</lastmod>
@endif
    </url>
@endforeach
@endforeach
</urlset>
