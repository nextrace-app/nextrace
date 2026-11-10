<?php

namespace Tests;

use App\Models\FeatureFlag;
use App\Models\Post;
use App\Support\Locale;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Laravel\Fortify\Features;

abstract class TestCase extends BaseTestCase
{
    /**
     * Tests run against the whole site, as it is meant to be: the flags that
     * hide parts of it start off in a fresh database.
     */
    protected function setUp(): void
    {
        parent::setUp();

        if (in_array(RefreshDatabase::class, class_uses_recursive($this), true)) {
            $this->switchLocalization(true);
            $this->switchFeature(Post::FEATURE, true);
        }
    }

    /**
     * Serve the site in every supported locale, or in the default one only.
     */
    protected function switchLocalization(bool $on): void
    {
        $this->switchFeature(Locale::FEATURE, $on);
    }

    /**
     * Switch a feature flag the application ships with on or off.
     */
    protected function switchFeature(string $key, bool $on): void
    {
        FeatureFlag::query()->where('key', $key)->update(['is_active' => $on]);

        request()->attributes->remove('features.active');
    }

    protected function skipUnlessFortifyHas(string $feature, ?string $message = null): void
    {
        if (! Features::enabled($feature)) {
            $this->markTestSkipped($message ?? "Fortify feature [{$feature}] is not enabled.");
        }
    }
}
