<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Post;
use App\Support\Features;
use App\Support\Locale;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    /**
     * Every public page in every locale the site is served in: home, the events list and each published event, plus the posts list and each published post while posts are switched on.
     *
     * There is none while the site is not indexable, as it would invite crawlers in.
     */
    public function __invoke(): Response
    {
        abort_unless(config()->boolean('site.indexable'), 404);

        $pages = [
            ['paths' => $this->paths('home'), 'lastmod' => null],
            ['paths' => $this->paths('events.index'), 'lastmod' => null],
        ];

        foreach (Event::query()->published()->orderBy('id')->get() as $event) {
            $pages[] = ['paths' => $this->paths('events.show', $event), 'lastmod' => $event->updated_at];
        }

        if (Features::active(Post::FEATURE)) {
            $pages[] = ['paths' => $this->paths('posts.index'), 'lastmod' => null];

            foreach (Post::query()->published()->orderBy('id')->get() as $post) {
                $pages[] = ['paths' => $this->paths('posts.show', $post), 'lastmod' => $post->updated_at];
            }
        }

        return response()
            ->view('sitemap', ['pages' => $pages, 'defaultLocale' => Locale::default()])
            ->header('Content-Type', 'application/xml; charset=utf-8');
    }

    /**
     * The absolute URL of a route under every locale the site is served in.
     *
     * @return array<string, string>
     */
    private function paths(string $route, mixed $parameters = []): array
    {
        $urls = [];

        foreach (Locale::served() as $locale) {
            $urls[$locale] = route(Locale::routeName($route, $locale), $parameters);
        }

        return $urls;
    }
}
