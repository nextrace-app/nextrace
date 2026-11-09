<?php

namespace App\Support;

use Illuminate\Support\Str;
use League\CommonMark\Extension\ExternalLink\ExternalLinkExtension;

/**
 * Renders text written in Markdown to HTML that is safe to print as it is.
 *
 * Raw HTML in the text is dropped and unsafe links (javascript: and the like)
 * are neutralised. Links to other sites open in a new tab and are not vouched
 * for.
 */
class SafeMarkdown
{
    public static function toHtml(string $markdown): string
    {
        return Str::markdown($markdown, [
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
            'external_link' => [
                'internal_hosts' => parse_url(config()->string('app.url'), PHP_URL_HOST) ?: 'localhost',
                'open_in_new_window' => true,
                'nofollow' => 'external',
                'noopener' => 'external',
                'noreferrer' => 'external',
            ],
        ], [new ExternalLinkExtension]);
    }
}
