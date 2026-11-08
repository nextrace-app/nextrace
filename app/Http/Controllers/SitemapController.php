<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Post;
use App\Support\Locale;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    /**
     * Every public page in every locale: home, the events and posts lists, and each published event and post.
     *
     * There is none while the site is not indexable, as it would invite crawlers in.
     */
    public function __invoke(): Response
    {
        abort_unless(config()->boolean('site.indexable'), 404);

        $pages = [
            ['paths' => $this->paths('home'), 'lastmod' => null],
            ['paths' => $this->paths('events.index'), 'lastmod' => null],
            ['paths' => $this->paths('posts.index'), 'lastmod' => null],
        ];

        foreach (Event::query()->published()->orderBy('id')->get() as $event) {
            $pages[] = ['paths' => $this->paths('events.show', $event), 'lastmod' => $event->updated_at];
        }

        foreach (Post::query()->published()->orderBy('id')->get() as $post) {
            $pages[] = ['paths' => $this->paths('posts.show', $post), 'lastmod' => $post->updated_at];
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
