<?php

namespace Tests\Feature;

use Tests\TestCase;

class RobotsTxtTest extends TestCase
{
    /**
     * The committed robots.txt must keep every crawler out until the site launches.
     */
    public function test_committed_robots_txt_disallows_all_crawlers(): void
    {
        $lines = file(public_path('robots.txt'), FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        $this->assertSame(['User-agent: *', 'Disallow: /'], $lines);
    }
}
