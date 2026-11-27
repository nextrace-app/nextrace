<?php

namespace Tests\Feature;

use Tests\TestCase;

class MaintenancePageTest extends TestCase
{
    protected function tearDown(): void
    {
        $this->app->maintenanceMode()->deactivate();

        parent::tearDown();
    }

    /**
     * While the site is down every page shows the branded "coming soon" page.
     */
    public function test_maintenance_mode_shows_the_coming_soon_page(): void
    {
        $this->app->maintenanceMode()->activate(['retry' => 3600]);

        $response = $this->get('/');

        $response->assertStatus(503);
        $response->assertHeader('Retry-After', '3600');
        $response->assertSee('<html lang="el">', false);
        $response->assertSee('<meta name="robots" content="noindex, nofollow">', false);
        $response->assertSee('Σύντομα κοντά σας - NextRace');
        $response->assertSee('Το NextRace είναι ακόμη υπό κατασκευή.');
    }

    /**
     * The page does not depend on the Vite build, so it works even without one.
     */
    public function test_coming_soon_page_does_not_load_vite_assets(): void
    {
        $this->app->maintenanceMode()->activate([]);

        $this->get('/')->assertDontSee('/build/assets/', false);
    }
}
