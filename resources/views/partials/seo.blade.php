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
{{-- An error page is not a page of the site, so it has no picture to share. --}}
@php($shareImage = $page['component'] === 'Error' ? null : $seo['image'])
@if ($shareImage)
    <meta property="og:image" content="{{ $shareImage }}">
@endif
<meta name="theme-color" content="{{ $seo['themeColor'] }}">
<meta name="twitter:card" content="{{ $shareImage ? $seo['twitterCard'] : 'summary' }}">
<meta name="twitter:title" content="{{ $seo['title'] ?? $seo['siteName'] }}">
<meta name="twitter:description" content="{{ $seo['description'] }}">
@if ($shareImage)
    <meta name="twitter:image" content="{{ $shareImage }}">
@endif
@foreach ($seo['jsonLd'] as $jsonLd)
    <script type="application/ld+json">{!! $jsonLd !!}</script>
@endforeach
