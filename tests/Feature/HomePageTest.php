<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_home_page_renders_the_home_component(): void
    {
        $this->get(route('home'))->assertInertia(
            fn ($page) => $page->component('Home')
        );
    }

    public function test_the_home_page_renders_the_home_component_for_every_locale(): void
    {
        $this->get(route('en.home'))->assertInertia(
            fn ($page) => $page->component('Home')->where('locale', 'en')
        );
    }
}
