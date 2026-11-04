<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnalyticsTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_umami_script_is_added_when_a_website_id_is_set(): void
    {
        config([
            'services.umami.script_url' => 'https://cloud.umami.is/script.js',
            'services.umami.website_id' => '2a82a287-f0e9-48d2-bfc8-8890b0686e91',
        ]);

        $script = '<script defer src="https://cloud.umami.is/script.js" data-website-id="2a82a287-f0e9-48d2-bfc8-8890b0686e91"></script>';

        $this->get(route('home'))->assertOk()->assertSee($script, false);
        $this->get(route('en.events.index'))->assertOk()->assertSee($script, false);
    }

    public function test_no_tracking_script_is_added_without_a_website_id(): void
    {
        config(['services.umami.website_id' => null]);

        $this->get(route('home'))
            ->assertOk()
            ->assertDontSee('data-website-id', false)
            ->assertDontSee('cloud.umami.is', false);
    }

    public function test_the_script_is_added_while_the_site_is_closed_to_search_engines(): void
    {
        config([
            'site.indexable' => false,
            'services.umami.website_id' => '2a82a287-f0e9-48d2-bfc8-8890b0686e91',
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('data-website-id="2a82a287-f0e9-48d2-bfc8-8890b0686e91"', false);
    }
}
