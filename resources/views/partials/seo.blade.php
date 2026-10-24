{{-- Search and social metadata for the current page; see App\Support\PageMeta. --}}
<meta name="description" content="{{ $seo['description'] }}">
<meta name="robots" content="{{ $seo['robots'] }}">
<meta property="og:site_name" content="{{ $seo['siteName'] }}">
<meta property="og:type" content="{{ $seo['type'] }}">
<meta property="og:title" content="{{ $seo['title'] ?? $seo['siteName'] }}">
<meta property="og:description" content="{{ $seo['description'] }}">
<meta property="og:url" content="{{ $localeAlternates[app()->getLocale()] }}">
<meta property="og:locale" content="{{ $seo['locale'] }}">
@foreach ($seo['localeAlternates'] as $alternateOgLocale)
    <meta property="og:locale:alternate" content="{{ $alternateOgLocale }}">
@endforeach
@if ($seo['image'])
    <meta property="og:image" content="{{ $seo['image'] }}">
@endif
<meta name="twitter:card" content="{{ $seo['twitterCard'] }}">
<meta name="twitter:title" content="{{ $seo['title'] ?? $seo['siteName'] }}">
<meta name="twitter:description" content="{{ $seo['description'] }}">
@if ($seo['image'])
    <meta name="twitter:image" content="{{ $seo['image'] }}">
@endif
@if ($seo['jsonLd'])
    <script type="application/ld+json">{!! $seo['jsonLd'] !!}</script>
@endif
