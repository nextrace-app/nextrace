<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Support\Locale;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    /**
     * Every public page in every locale: home, the events list and each published event.
     */
    public function __invoke(): Response
    {
        $pages = [
            ['paths' => $this->paths('home'), 'lastmod' => null],
            ['paths' => $this->paths('events.index'), 'lastmod' => null],
        ];

        foreach (Event::query()->published()->orderBy('id')->get() as $event) {
            $pages[] = ['paths' => $this->paths('events.show', $event), 'lastmod' => $event->updated_at];
        }

        return response()
            ->view('sitemap', ['pages' => $pages, 'defaultLocale' => Locale::default()])
            ->header('Content-Type', 'application/xml; charset=utf-8');
    }

    /**
     * The absolute URL of a route under every supported locale.
     *
     * @return array<string, string>
     */
    private function paths(string $route, mixed $parameters = []): array
    {
        $urls = [];

        foreach (config('app.supported_locales') as $locale) {
            $urls[$locale] = route(Locale::routeName($route, $locale), $parameters);
        }

        return $urls;
    }
}
