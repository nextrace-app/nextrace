# NextRace

A calendar of running races across all of Greece. Laravel, Inertia and Vue; English (default) and Greek.

## Search engine indexing

Whether search engines may index the site is controlled by one environment variable:

| `SITE_INDEXABLE` | Effect |
| --- | --- |
| `false` (default) | `robots.txt` is `Disallow: /` without a sitemap, every response carries `X-Robots-Tag: noindex, nofollow`, pages have `<meta name="robots" content="noindex, nofollow">`, and `/sitemap.xml` returns 404. |
| `true` | `robots.txt` allows crawling except account pages and announces the sitemap, pages are `index, follow`, and the sitemap is served. |

The default is closed on purpose: a missing variable can never expose an unfinished environment. Local stays `false`. Only production is switched to `true`, at launch.

### `robots.txt` is a static file

The web server answers `/robots.txt` itself, without reaching PHP, so changing the variable does not change the file. It is written by:

```bash
php artisan seo:robots
```

Every deploy runs it (see the deployment script), so on a server changing the variable and deploying is enough. The committed `public/robots.txt` is the closed version, and a test fails if it is not.

### Testing locally

1. Set `SITE_INDEXABLE=true` or `false` in `.env`. Without a cached config (`php artisan config:clear`) it applies at once to the header, the meta tag and the sitemap.
2. Regenerate the file: `php artisan seo:robots`.
3. Check the result:

```bash
curl -s https://nextrace.test/robots.txt
curl -sI https://nextrace.test/ | grep -i x-robots-tag
curl -s -o /dev/null -w "%{http_code}\n" https://nextrace.test/sitemap.xml
```

4. Put the committed file back so the open version is never committed:

```bash
git checkout public/robots.txt
```

To write the file somewhere else instead, use `php artisan seo:robots --path=/tmp/robots.txt`.

Tests: `php artisan test --compact --filter='SiteIndexingTest|SeoTest'`. The suite forces `SITE_INDEXABLE=true` in `phpunit.xml`; tests of the closed mode set `config(['site.indexable' => false])`. If `npm run dev` is running (`public/hot` exists), the `<title>` assertions in `SeoTest` fail because of the dev-mode markup; stop it first.

## "Coming soon" page before launch

Production is closed with Laravel's maintenance mode, which answers 503 with `Retry-After` (search engines treat that as temporary) and shows the "Coming soon" page without header or footer in both languages:

```bash
php artisan down --retry=3600 --secret=<token>   # close; visit /<token> once to browse the site yourself
php artisan up                                   # open
```

The maintenance state lives in `storage`, which Forge shares between releases, so it survives deploys until `up` is run. Try it locally the same way, then run `php artisan up`.

## Launch checklist

1. Set `SITE_INDEXABLE=true` in the production environment.
2. Deploy, so `seo:robots` writes the open `robots.txt`.
3. Run `php artisan up` on production.
4. Check `/robots.txt`, `/sitemap.xml` and the `X-Robots-Tag` header, then submit the sitemap in Google Search Console.
