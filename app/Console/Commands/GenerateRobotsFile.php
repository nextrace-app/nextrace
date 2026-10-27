<?php

namespace App\Console\Commands;

use App\Support\Locale;
use App\Support\PageMeta;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * Writes public/robots.txt: crawling is allowed except for account and
 * authentication pages in every locale, and the sitemap is announced with the
 * absolute URL built from APP_URL. While the site is not indexable (see
 * config/site.php) everything is disallowed and no sitemap is announced.
 *
 * The file is static because the web server answers /robots.txt itself, without
 * reaching PHP. Run it again whenever APP_URL or the supported locales change,
 * and at deploy time so the Sitemap line carries the production URL.
 */
#[Signature('seo:robots {--path= : Where to write the file instead of public/robots.txt}')]
#[Description('Write public/robots.txt with the disallowed account pages and the sitemap URL')]
class GenerateRobotsFile extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $path = $this->option('path') ?: public_path('robots.txt');

        if (! config()->boolean('site.indexable')) {
            file_put_contents($path, $this->closedContents());

            $this->info("Wrote {$path} disallowing every crawler: the site is not indexable.");

            return self::SUCCESS;
        }

        $sitemapUrl = rtrim(config()->string('app.url'), '/').'/sitemap.xml';

        file_put_contents($path, $this->contents($sitemapUrl));

        $this->info("Wrote {$path} with the sitemap at {$sitemapUrl}.");

        return self::SUCCESS;
    }

    /**
     * The robots.txt body that keeps every crawler out.
     */
    private function closedContents(): string
    {
        return "User-agent: *\nDisallow: /\n";
    }

    /**
     * The robots.txt body for the given sitemap URL.
     */
    private function contents(string $sitemapUrl): string
    {
        $lines = ['User-agent: *', 'Allow: /'];

        foreach (PageMeta::PRIVATE_SEGMENTS as $segment) {
            foreach (config()->array('app.supported_locales') as $locale) {
                $lines[] = 'Disallow: '.Locale::path("/{$segment}", $locale);
            }
        }

        $lines[] = '';
        $lines[] = "Sitemap: {$sitemapUrl}";

        return implode("\n", $lines)."\n";
    }
}
