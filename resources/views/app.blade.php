<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" >
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        {{-- Inline style to set the HTML background color (the light theme in app.css) --}}
        <style>
            html {
                background-color: #f7f5f0;
            }
        </style>

        {{-- Every language this page exists in, so search engines serve the
             right one and do not read them as duplicate content. --}}
        <link rel="canonical" href="{{ $localeAlternates[app()->getLocale()] }}">
        @foreach ($localeAlternates as $alternateLocale => $alternateUrl)
            <link rel="alternate" hreflang="{{ $alternateLocale }}" href="{{ $alternateUrl }}">
        @endforeach
        <link rel="alternate" hreflang="x-default" href="{{ $localeAlternates[$defaultLocale] }}">

        @include('partials.seo')

        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">

        @fonts

        @vite(['resources/css/app.css', 'resources/js/app.ts', "resources/js/pages/{$page['component']}.vue"])
        <x-inertia::head>
            <title>{{ $seo['title'] ? $seo['title'].' - ' : '' }}{{ config('app.name', 'Laravel') }}</title>
        </x-inertia::head>
    </head>
    <body class="font-sans antialiased">
        <x-inertia::app />
    </body>
</html>
