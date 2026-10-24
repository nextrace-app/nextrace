<?php

namespace App\Http\Controllers;

use App\Support\Locale;
use App\Support\PageMeta;
use Illuminate\Http\Response;

class RobotsController extends Controller
{
    /**
     * Allow crawling, except for account and authentication pages in every locale.
     */
    public function __invoke(): Response
    {
        $lines = ['User-agent: *', 'Allow: /'];

        foreach (PageMeta::PRIVATE_SEGMENTS as $segment) {
            foreach (config('app.supported_locales') as $locale) {
                $lines[] = 'Disallow: '.Locale::path("/{$segment}", $locale);
            }
        }

        $lines[] = '';
        $lines[] = 'Sitemap: '.route('sitemap');

        return response(implode("\n", $lines)."\n")->header('Content-Type', 'text/plain; charset=utf-8');
    }
}
