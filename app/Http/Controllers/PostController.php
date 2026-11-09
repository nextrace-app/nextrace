<?php

namespace App\Http\Controllers;

use App\Http\Resources\PostResource;
use App\Models\Event;
use App\Models\Post;
use App\Support\PageMeta;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PostController extends Controller
{
    /**
     * One page of the published posts, the newest first.
     *
     * Every page has an address of its own (?page=N), like the events list: the
     * first page lives at the bare URL, and a page that does not exist is a 404.
     * ?event=<slug> narrows the list to the news of one published event.
     */
    public function index(Request $request, PageMeta $meta): Response|RedirectResponse
    {
        $event = null;

        if ($request->has('event')) {
            $slug = $request->query('event');
            $event = is_string($slug) ? Event::query()->published()->where('slug', $slug)->first() : null;

            abort_if($event === null, 404);

            $meta->forEventNews($event);
        }

        $page = $request->query('page');

        if ($page !== null && (! is_string($page) || ! ctype_digit($page) || (int) $page < 1)) {
            abort(404);
        }

        if ($page !== null && (int) $page === 1) {
            return redirect()->to($request->fullUrlWithoutQuery('page'), 301);
        }

        $perPage = (int) config('posts.per_page');

        $posts = Post::query()
            ->published()
            ->when($event, fn ($query) => $query->whereHas('events', fn ($events) => $events->whereKey($event->id)))
            ->latest('published_at')
            ->latest('id')
            ->paginate($perPage, page: (int) ($page ?? 1));

        abort_if($posts->currentPage() > max(1, $posts->lastPage()), 404);

        return Inertia::render('Posts/Index', [
            'posts' => PostResource::collection($posts->getCollection())->resolve(),
            'pagination' => [
                'current' => $posts->currentPage(),
                'last' => max(1, $posts->lastPage()),
                'total' => $posts->total(),
                'perPage' => $perPage,
            ],
            'event' => $event === null ? null : [
                'slug' => $event->slug,
                'name' => $event->translated('name'),
            ],
        ]);
    }

    /**
     * The page of a single post.
     */
    public function show(Post $post, PageMeta $meta): Response
    {
        // Drafts, scheduled and archived posts have no page of their own.
        abort_unless($post->isPublished(), 404);

        $meta->forPost($post);

        return Inertia::render('Posts/Show', [
            'post' => [
                ...(new PostResource($post))->resolve(),
                'body_html' => $post->bodyHtml(),
            ],
            'events' => $post->events()
                ->published()
                ->with('mainRace')
                ->orderBy('starts_on')
                ->get()
                ->map(fn (Event $event) => [
                    'slug' => $event->slug,
                    'name' => $event->translated('name'),
                    'date' => ($event->mainRace->starts_at ?? $event->starts_on)->toDateString(),
                    'city' => $event->city,
                ])
                ->all(),
        ]);
    }
}
